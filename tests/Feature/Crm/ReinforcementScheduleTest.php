<?php

use App\Domain\Crm\Actions\ActivateReinforcement;
use App\Domain\Crm\Actions\EnrollContacts;
use App\Domain\Crm\Actions\MarkCourseDelivered;
use App\Domain\Crm\Actions\PauseSubscription;
use App\Domain\Crm\Actions\ResumeSubscription;
use App\Domain\Crm\Actions\RetryFailedSends;
use App\Domain\Crm\Actions\SendMissedEmails;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

const MX = 'America/Mexico_City';

/**
 * Curso con secuencia completa y $contacts inscritos, sin activar el refuerzo.
 */
function courseWithEnrolled(int $contacts = 2, bool $templates = true): Course
{
    $sequence = Sequence::factory()->withSteps(30, $templates)->create();
    $course = Course::factory()->delivered()->for($sequence)->create();
    $course->contacts()->attach(Contact::factory()->count($contacts)->for($course->company)->create());

    return $course;
}

function activate(Course $course, string $startsOn = '2026-11-09'): int
{
    return app(ActivateReinforcement::class)->handle($course, $startsOn);
}

beforeEach(function () {
    // Domingo 8 de noviembre de 2026, 10:00 hora de la Ciudad de México.
    $this->travelTo(CarbonImmutable::parse('2026-11-08 10:00', MX));
});

test('activating schedules 30 emails per recipient at noon Mexico City time, one per day', function () {
    $course = courseWithEnrolled(2);

    expect(activate($course))->toBe(2);

    $first = Send::query()->whereRelation('step', 'day', 1)->first();
    $last = Send::query()->whereRelation('step', 'day', 30)->first();

    expect(Send::count())->toBe(60)
        ->and(Send::where('status', SendStatus::Pending)->count())->toBe(60)
        ->and($first->scheduled_for->timezone(MX)->format('Y-m-d H:i'))->toBe('2026-11-09 12:00')
        ->and($first->scheduled_for->utc()->format('H:i'))->toBe('18:00')
        ->and($last->scheduled_for->timezone(MX)->format('Y-m-d H:i'))->toBe('2026-12-08 12:00')
        ->and($course->fresh()->isReinforcementActive())->toBeTrue();
});

test('contacts that unsubscribed or bounced are not scheduled', function () {
    $course = courseWithEnrolled(3);
    $course->contacts()->first()->update(['unsubscribed_at' => now()]);
    $course->contacts()->latest('crm_contact_course.id')->first()->update(['bounced_at' => now()]);

    expect(activate($course))->toBe(1)
        ->and(Subscription::count())->toBe(1);
});

test('activation is refused when something is missing', function (Closure $arrange, string $expected) {
    $course = $arrange();

    expect(fn () => activate($course))->toThrow(ValidationException::class, $expected);
    expect(Send::count())->toBe(0);
})->with([
    'a step without a template' => [fn () => courseWithEnrolled(templates: false), 'plantilla de Brevo'],
    'no enrolled contacts' => [fn () => courseWithEnrolled(contacts: 0), 'No hay contactos'],
    'no sequence' => [fn () => Course::factory()->create(), 'plantilla de Brevo'],
]);

test('activation is refused for a start date in the past or for an already active course', function () {
    $course = courseWithEnrolled();

    expect(fn () => activate($course, '2026-11-07'))->toThrow(ValidationException::class, 'anterior a hoy');

    activate($course->fresh());

    expect(fn () => activate($course->fresh()))->toThrow(ValidationException::class, 'ya está activo');
});

test('today is a valid start date', function () {
    expect(activate(courseWithEnrolled(1), '2026-11-08'))->toBe(1);
});

test('enrolling later aligns the contact to the group and skips the days already gone', function () {
    $course = courseWithEnrolled(1);
    activate($course);

    $this->travelTo(CarbonImmutable::parse('2026-11-13 15:00', MX));

    $late = Contact::factory()->for($course->company)->create();
    app(EnrollContacts::class)->handle($course->fresh(), [$late->id]);

    $sends = Subscription::firstWhere('contact_id', $late->id)->sends();

    expect($sends->where('status', SendStatus::Skipped)->count())->toBe(5)
        ->and(Subscription::firstWhere('contact_id', $late->id)->sends()->where('status', SendStatus::Pending)->count())->toBe(25);
});

test('only contacts of the course company can be enrolled', function () {
    $course = courseWithEnrolled(0);
    $own = Contact::factory()->for($course->company)->create();
    $foreign = Contact::factory()->create();

    expect(app(EnrollContacts::class)->handle($course, [$own->id, $foreign->id]))->toBe(1)
        ->and($course->contacts()->pluck('crm_contacts.id')->all())->toBe([$own->id]);
});

test('missed emails are rescheduled in day order, one per minute', function () {
    $course = courseWithEnrolled(1);
    activate($course);
    $this->travelTo(CarbonImmutable::parse('2026-11-12 15:00', MX));
    $late = Contact::factory()->for($course->company)->create();
    app(EnrollContacts::class)->handle($course->fresh(), [$late->id]);
    $subscription = Subscription::firstWhere('contact_id', $late->id);

    expect(app(SendMissedEmails::class)->handle($subscription))->toBe(4);

    $rescheduled = $subscription->sends()->with('step')->where('status', SendStatus::Pending)->where('scheduled_for', '<=', now()->addMinutes(10))->get()->sortBy('scheduled_for');

    expect($rescheduled->pluck('step.day')->all())->toBe([1, 2, 3, 4])
        ->and($rescheduled->map(fn (Send $s) => (int) now()->diffInMinutes($s->scheduled_for))->all())->toBe([0, 1, 2, 3])
        ->and($subscription->sends()->where('status', SendStatus::Skipped)->count())->toBe(0);
});

test('missed emails are not sent to paused, unsubscribed or bounced subscriptions', function () {
    $course = courseWithEnrolled(1);
    activate($course);
    $this->travelTo(CarbonImmutable::parse('2026-11-12 15:00', MX));
    $late = Contact::factory()->for($course->company)->create();
    app(EnrollContacts::class)->handle($course->fresh(), [$late->id]);
    $subscription = Subscription::firstWhere('contact_id', $late->id);

    app(PauseSubscription::class)->handle($subscription);
    expect(app(SendMissedEmails::class)->handle($subscription->fresh()))->toBe(0);

    app(ResumeSubscription::class)->handle($subscription->fresh());
    $late->update(['unsubscribed_at' => now()]);
    expect(app(SendMissedEmails::class)->handle($subscription->fresh()))->toBe(0);
});

test('resuming does not release everything that came due while paused', function () {
    $course = courseWithEnrolled(1);
    activate($course);
    $subscription = Subscription::sole();
    app(PauseSubscription::class)->handle($subscription);

    $this->travelTo(CarbonImmutable::parse('2026-11-11 15:00', MX));
    app(ResumeSubscription::class)->handle($subscription->fresh());

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->sends()->where('status', SendStatus::Skipped)->count())->toBe(3)
        ->and($subscription->sends()->where('status', SendStatus::Pending)->count())->toBe(27);
});

test('failed sends can be retried right away', function () {
    $subscription = Subscription::factory()->create();
    Send::factory()->for($subscription)->status(SendStatus::Failed)->create(['error' => 'Brevo respondió 400', 'scheduled_for' => now()->subDays(3)]);
    Send::factory()->for($subscription)->status(SendStatus::Sent)->create();

    expect(app(RetryFailedSends::class)->handle($subscription))->toBe(1);

    $retried = $subscription->sends()->where('status', SendStatus::Pending)->sole();

    expect($retried->error)->toBeNull()
        ->and($retried->scheduled_for->isToday())->toBeTrue();
});

test('marking a course as delivered moves an open company to the won stage', function () {
    Stage::factory()->create(['position' => 1]);
    $won = Stage::factory()->won()->create(['position' => 2]);
    $company = Company::factory()->for(Stage::first())->create();
    $course = Course::factory()->for($company)->create();

    app(MarkCourseDelivered::class)->handle($course);

    expect($course->fresh()->delivered_at)->not->toBeNull()
        ->and($company->fresh()->stage_id)->toBe($won->id);
});

test('marking a course as delivered leaves a lost company where it is', function () {
    Stage::factory()->won()->create(['position' => 2]);
    $lost = Stage::factory()->lost()->create(['position' => 3]);
    $company = Company::factory()->for($lost)->create();

    app(MarkCourseDelivered::class)->handle(Course::factory()->for($company)->create());

    expect($company->fresh()->stage_id)->toBe($lost->id);
});
