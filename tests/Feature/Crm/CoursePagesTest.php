<?php

use App\Domain\Crm\Enums\CourseModality;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\Subscription;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(TeamMember::factory()->create()->user);
    $this->travelTo(CarbonImmutable::parse('2026-11-08 10:00', 'America/Mexico_City'));
});

function coursePage(Course $course)
{
    return Livewire::test('pages::crm.course', ['course' => $course]);
}

test('course and sequence pages are closed to users outside the team', function () {
    $course = Course::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('crm.courses.show', $course))->assertForbidden();
    $this->get(route('crm.courses.edit', $course))->assertForbidden();
    $this->get(route('crm.companies.courses.create', $course->company))->assertForbidden();
    $this->get(route('crm.sequences.edit', Sequence::factory()->create()))->assertForbidden();
});

test('the company page lists its courses', function () {
    $company = Company::factory()->create();
    Course::factory()->for($company)->create(['title' => 'Ventas consultivas']);

    $this->get(route('crm.companies.show', $company))->assertOk()->assertSee('Ventas consultivas');
});

test('a course is created for a company with the first sequence preselected', function () {
    $company = Company::factory()->create();
    $sequence = Sequence::factory()->create();

    Livewire::test('pages::crm.course-form', ['company' => $company])
        ->assertSet('sequenceId', $sequence->id)
        ->set('title', 'Ventas B2B')
        ->set('modality', CourseModality::Online->value)
        ->set('hours', 12)
        ->set('startsOn', '2026-10-01')
        ->set('endsOn', '2026-10-03')
        ->call('save')
        ->assertHasNoErrors();

    $course = $company->courses()->sole();

    expect($course->title)->toBe('Ventas B2B')
        ->and($course->sequence_id)->toBe($sequence->id)
        ->and($course->starts_on->toDateString())->toBe('2026-10-01');
});

test('a course needs a title and cannot end before it starts', function () {
    Livewire::test('pages::crm.course-form', ['company' => Company::factory()->create()])
        ->set('title', '')
        ->set('startsOn', '2026-10-03')
        ->set('endsOn', '2026-10-01')
        ->call('save')
        ->assertHasErrors(['title' => 'required', 'endsOn' => 'after_or_equal']);
});

test('the sequence of a course cannot change once the reinforcement is active', function () {
    $original = Sequence::factory()->create();
    $other = Sequence::factory()->create();
    $course = Course::factory()->for($original)->create(['reinforcement_activated_at' => now()]);

    Livewire::test('pages::crm.course-form', ['course' => $course])
        ->set('sequenceId', $other->id)
        ->set('title', 'Título nuevo')
        ->call('save');

    expect($course->fresh()->sequence_id)->toBe($original->id)
        ->and($course->fresh()->title)->toBe('Título nuevo');
});

test('only contacts of the company that are not enrolled yet can be enrolled from the page', function () {
    $course = Course::factory()->create();
    $enrolled = Contact::factory()->for($course->company)->create();
    $available = Contact::factory()->for($course->company)->create(['name' => 'Disponible Uno']);
    $course->contacts()->attach($enrolled);

    coursePage($course)
        ->call('openEnroll')
        ->assertSee('Disponible Uno')
        ->set('selectedContacts', [$available->id])
        ->call('enroll')
        ->assertHasNoErrors();

    expect($course->contacts()->count())->toBe(2);
});

test('enrolling requires choosing at least one contact', function () {
    coursePage(Course::factory()->create())->call('enroll')->assertHasErrors('selectedContacts');
});

test('enrolled contacts can be removed before the reinforcement is activated but not after', function () {
    $course = Course::factory()->for(Sequence::factory()->withSteps())->create();
    $contacts = Contact::factory()->count(2)->for($course->company)->create();
    $course->contacts()->attach($contacts);

    coursePage($course)->call('unenroll', $contacts[0]->id);
    expect($course->contacts()->count())->toBe(1);

    coursePage($course)->set('startsOn', '2026-11-09')->call('activate');
    coursePage($course->fresh())->call('unenroll', $contacts[1]->id);

    expect($course->contacts()->count())->toBe(1);
});

test('the activation date defaults to the next monday', function () {
    coursePage(Course::factory()->create())->assertSet('startsOn', '2026-11-09');
});

test('activating from the page schedules the sequence and refuses an incomplete one', function () {
    $incomplete = Course::factory()->for(Sequence::factory()->withSteps(30, false))->create();
    $incomplete->contacts()->attach(Contact::factory()->for($incomplete->company)->create());

    coursePage($incomplete)->assertSee('La secuencia no está lista')->call('activate')->assertHasErrors('startsOn');
    expect(Send::count())->toBe(0);

    $ready = Course::factory()->for(Sequence::factory()->withSteps())->create();
    $ready->contacts()->attach(Contact::factory()->count(2)->for($ready->company)->create());

    coursePage($ready)->call('activate')->assertHasNoErrors();

    expect(Send::count())->toBe(60)
        ->and($ready->fresh()->reinforcement_starts_on->toDateString())->toBe('2026-11-09');
});

test('a subscription can be paused, resumed and its failed sends retried from the page', function () {
    $subscription = Subscription::factory()->create();
    $failed = Send::factory()->for($subscription)->status(SendStatus::Failed)->create(['error' => 'x']);
    $page = coursePage($subscription->course);

    $page->call('pause', $subscription->id);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Paused);

    $page->call('resume', $subscription->id);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);

    $page->call('retryFailed', $subscription->id);
    expect($failed->fresh()->status)->toBe(SendStatus::Pending);
});

test('a subscription of another course cannot be controlled from this page', function () {
    $foreign = Subscription::factory()->create();

    expect(fn () => coursePage(Course::factory()->create())->call('pause', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
});

test('missed emails can be sent to one subscriber or to the whole group', function () {
    $course = Course::factory()->create();
    $first = Subscription::factory()->for($course)->create();
    $second = Subscription::factory()->for($course)->create();
    Send::factory()->count(2)->for($first)->status(SendStatus::Skipped)->create();
    Send::factory()->for($second)->status(SendStatus::Skipped)->create();

    coursePage($course)->call('sendMissed', $first->id);
    expect($first->sends()->where('status', SendStatus::Pending)->count())->toBe(2)
        ->and($second->sends()->where('status', SendStatus::Skipped)->count())->toBe(1);

    coursePage($course)->call('sendMissedToAll');
    expect($second->sends()->where('status', SendStatus::Pending)->count())->toBe(1);
});

test('marking the course as delivered moves the company to the won stage', function () {
    $open = Stage::factory()->create(['position' => 1]);
    $won = Stage::factory()->won()->create(['position' => 2]);
    $course = Course::factory()->for(Company::factory()->for($open))->create();

    coursePage($course)->call('markDelivered');

    expect($course->fresh()->delivered_at)->not->toBeNull()
        ->and($course->company->fresh()->stage_id)->toBe($won->id);
});

test('the sequence page saves the templates and treats blanks as missing', function () {
    $sequence = Sequence::factory()->withSteps(3, false)->create();

    Livewire::test('pages::crm.sequence', ['sequence' => $sequence])
        ->set('templates.1', '501')
        ->set('templates.2', '502')
        ->set('templates.3', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($sequence->steps()->pluck('brevo_template_id', 'day')->all())->toBe([1 => 501, 2 => 502, 3 => null])
        ->and($sequence->isReady())->toBeFalse();
});

test('pasting a list of ids fills the days in order', function () {
    $sequence = Sequence::factory()->withSteps(3, false)->create();

    Livewire::test('pages::crm.sequence', ['sequence' => $sequence])
        ->set('pasted', "10\n20, 30\n40")
        ->call('applyPasted')
        ->assertSet('templates.1', '10')
        ->assertSet('templates.2', '20')
        ->assertSet('templates.3', '30')
        ->assertSet('pasted', '')
        ->call('save');

    expect($sequence->isReady())->toBeTrue();
});

test('template ids must be positive numbers', function (string $value) {
    $sequence = Sequence::factory()->withSteps(1, false)->create();

    Livewire::test('pages::crm.sequence', ['sequence' => $sequence])
        ->set('templates.1', $value)
        ->call('save')
        ->assertHasErrors('templates.1');
})->with(['text' => 'abc', 'zero' => '0', 'negative' => '-4', 'decimal' => '1.5']);
