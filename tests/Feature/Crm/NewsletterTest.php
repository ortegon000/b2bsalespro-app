<?php

use App\Domain\Crm\Actions\EnrollContacts;
use App\Domain\Crm\Actions\MarkCourseDelivered;
use App\Domain\Crm\Actions\RecordBrevoEvent;
use App\Domain\Crm\Actions\SyncNewsletter;
use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Jobs\SyncContactToNewsletter;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\NewsletterCampaign;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Domain\Crm\Services\BrevoClient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    config(['crm.brevo.api_key' => 'test-key', 'crm.brevo.newsletter_list' => 'newsletter']);
});

function fakeBrevo(): void
{
    Http::fake([
        'api.brevo.com/v3/contacts/lists*' => Http::response(['lists' => [['id' => 3, 'name' => 'Clientes'], ['id' => 7, 'name' => 'Newsletter']], 'count' => 2]),
        'api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
        'api.brevo.com/v3/emailCampaigns' => Http::response(['id' => 55], 201),
        'api.brevo.com/v3/emailCampaigns/*/sendNow' => Http::response(null, 204),
    ]);
}

/**
 * Curso con $count inscritos; impartido salvo que se indique lo contrario.
 */
function enrolledCourse(int $count = 2, bool $delivered = true): Course
{
    $course = $delivered ? Course::factory()->delivered()->create() : Course::factory()->create();
    $course->contacts()->attach(Contact::factory()->count($count)->for($course->company)->create());

    return $course;
}

test('the newsletter list is found by name ignoring case, across pages, and cached', function () {
    Http::fake([
        'api.brevo.com/v3/contacts/lists*' => Http::sequence()
            ->push(['lists' => [['id' => 3, 'name' => 'Clientes']], 'count' => 51])
            ->push(['lists' => [['id' => 7, 'name' => 'NEWSLETTER']], 'count' => 51]),
    ]);

    expect(app(BrevoClient::class)->newsletterListId())->toBe(7)
        ->and(app(BrevoClient::class)->newsletterListId())->toBe(7);

    Http::assertSentCount(2);
});

test('a missing list raises a clear error and is not cached', function () {
    Http::fake(['api.brevo.com/v3/contacts/lists*' => Http::response(['lists' => [['id' => 3, 'name' => 'Clientes']], 'count' => 1])]);

    expect(fn () => app(BrevoClient::class)->newsletterListId())->toThrow(BrevoException::class, 'No existe la lista "newsletter"');
    expect(fn () => app(BrevoClient::class)->newsletterListId())->toThrow(BrevoException::class);

    Http::assertSentCount(2);
});

test('only contacts that took a delivered course and can receive email are pending', function () {
    $delivered = enrolledCourse(1);
    $undelivered = enrolledCourse(1, delivered: false);
    $unsubscribed = Contact::factory()->for($delivered->company)->create(['unsubscribed_at' => now()]);
    $bounced = Contact::factory()->for($delivered->company)->create(['bounced_at' => now()]);
    $synced = Contact::factory()->for($delivered->company)->create(['newsletter_synced_at' => now()]);
    $delivered->contacts()->attach([$unsubscribed->id, $bounced->id, $synced->id]);

    $pending = app(SyncNewsletter::class)->pendingQuery()->pluck('id')->all();

    expect($pending)->toBe([$delivered->contacts()->first()->id])
        ->and($pending)->not->toContain($undelivered->contacts()->first()->id);
});

test('syncing queues one job per pending contact and does nothing without brevo configured', function () {
    Queue::fake();
    $course = enrolledCourse(3);

    expect(app(SyncNewsletter::class)->handle($course))->toBe(3);
    Queue::assertPushed(SyncContactToNewsletter::class, 3);

    Queue::fake();
    config(['crm.brevo.api_key' => null]);

    expect(app(SyncNewsletter::class)->handle($course))->toBe(0);
    Queue::assertNothingPushed();
});

test('the job creates the contact in the list with split names and marks it as synced', function () {
    fakeBrevo();
    $contact = Contact::factory()->create(['name' => 'Rosa Elena Díaz', 'email' => 'rosa@acme.test']);

    (new SyncContactToNewsletter($contact->id))->handle(app(BrevoClient::class));

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/contacts'
        && $request['email'] === 'rosa@acme.test'
        && $request['attributes'] === ['FIRSTNAME' => 'Rosa', 'LASTNAME' => 'Elena Díaz']
        && $request['listIds'] === [7]
        && $request['updateEnabled'] === true);

    expect($contact->fresh()->newsletter_synced_at)->not->toBeNull();
});

test('the job skips contacts that are synced, unsubscribed or bounced', function (array $state) {
    fakeBrevo();
    $contact = Contact::factory()->create($state);

    (new SyncContactToNewsletter($contact->id))->handle(app(BrevoClient::class));

    Http::assertNothingSent();
})->with([
    'synced' => [['newsletter_synced_at' => '2026-01-01 00:00:00']],
    'unsubscribed' => [['unsubscribed_at' => '2026-01-01 00:00:00']],
    'bounced' => [['bounced_at' => '2026-01-01 00:00:00']],
]);

test('a temporary brevo error is retried and a permanent one leaves the contact pending', function () {
    $contact = Contact::factory()->create();
    Http::fake([
        'api.brevo.com/v3/contacts/lists*' => Http::response(['lists' => [['id' => 7, 'name' => 'newsletter']], 'count' => 1]),
        'api.brevo.com/v3/contacts' => Http::sequence()->push([], 503)->push(['message' => 'Invalid'], 400),
    ]);
    $job = new SyncContactToNewsletter($contact->id);

    expect(fn () => $job->handle(app(BrevoClient::class)))->toThrow(BrevoException::class);
    $job->handle(app(BrevoClient::class));

    expect($contact->fresh()->newsletter_synced_at)->toBeNull();
});

test('marking a course as delivered queues its enrolled contacts for the newsletter', function () {
    Queue::fake();
    Stage::factory()->won()->create();
    $course = enrolledCourse(2, delivered: false);

    app(MarkCourseDelivered::class)->handle($course);

    Queue::assertPushed(SyncContactToNewsletter::class, 2);
});

test('enrolling in a delivered course queues only the new contacts, and an undelivered one queues none', function () {
    Queue::fake();
    $delivered = enrolledCourse(1);
    $newContact = Contact::factory()->for($delivered->company)->create();

    app(EnrollContacts::class)->handle($delivered, [$newContact->id]);

    Queue::assertPushed(SyncContactToNewsletter::class, fn ($job) => $job->contactId === $newContact->id);

    Queue::fake();
    $undelivered = enrolledCourse(0, delivered: false);
    app(EnrollContacts::class)->handle($undelivered, [Contact::factory()->for($undelivered->company)->create()->id]);

    Queue::assertNothingPushed();
});

test('the course page can queue the newsletter sync for its enrolled contacts', function () {
    Queue::fake();
    $this->actingAs(TeamMember::factory()->create()->user);
    $course = enrolledCourse(2);

    Livewire::test('pages::crm.course', ['course' => $course])->call('syncNewsletter');

    Queue::assertPushed(SyncContactToNewsletter::class, 2);
});

test('events from brevo marketing campaigns also block the contact', function (string $event, string $column) {
    $contact = Contact::factory()->create();

    app(RecordBrevoEvent::class)->handle($event, $contact->email);

    expect($contact->fresh()->{$column})->not->toBeNull();
})->with([['unsubscribe', 'unsubscribed_at'], ['hardBounce', 'bounced_at']]);

describe('newsletter page', function () {
    beforeEach(function () {
        $this->actingAs(TeamMember::factory()->create()->user);
    });

    test('is closed to users outside the crm team', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('crm.newsletter'))->assertForbidden();
    });

    test('shows how many contacts are in the list and how many are pending', function () {
        enrolledCourse(2);
        Contact::factory()->count(3)->create(['newsletter_synced_at' => now()]);

        $this->get(route('crm.newsletter'))->assertOk()->assertSee('3 contactos en la lista')->assertSee('2 pendientes de sumar');
    });

    test('warns when brevo is not configured', function () {
        config(['crm.brevo.api_key' => null]);

        $this->get(route('crm.newsletter'))->assertSee('Brevo no está configurado');
    });

    test('queues the pending contacts from the sync button', function () {
        Queue::fake();
        enrolledCourse(2);

        Livewire::test('pages::crm.newsletter')->call('sync');

        Queue::assertPushed(SyncContactToNewsletter::class, 2);
    });

    test('creates the campaign for the newsletter list and sends it right away', function () {
        fakeBrevo();

        Livewire::test('pages::crm.newsletter')
            ->set('name', 'Newsletter de octubre')
            ->set('templateId', 42)
            ->call('sendCampaign')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/emailCampaigns'
            && $request['name'] === 'Newsletter de octubre'
            && $request['templateId'] === 42
            && $request['recipients'] === ['listIds' => [7]]
            && ! isset($request['scheduledAt']));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/emailCampaigns/55/sendNow');

        $campaign = NewsletterCampaign::sole();

        expect($campaign->brevo_campaign_id)->toBe(55)
            ->and($campaign->brevo_template_id)->toBe(42)
            ->and($campaign->scheduled_for)->toBeNull()
            ->and($campaign->user_id)->toBe(auth()->id());
    });

    test('schedules the campaign in brevo using the business time zone and does not send it', function () {
        fakeBrevo();
        $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00', 'America/Mexico_City'));

        Livewire::test('pages::crm.newsletter')
            ->set('name', 'Newsletter de diciembre')
            ->set('templateId', 42)
            ->set('scheduledAt', '2026-12-01T09:00')
            ->call('sendCampaign')
            ->assertHasNoErrors();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/emailCampaigns'
            && CarbonImmutable::parse($request['scheduledAt'])->equalTo(CarbonImmutable::parse('2026-12-01 15:00', 'UTC')));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'sendNow'));

        expect(NewsletterCampaign::sole()->scheduled_for->utc()->format('Y-m-d H:i'))->toBe('2026-12-01 15:00');
    });

    test('validates the campaign form', function (array $input, string $field) {
        fakeBrevo();
        $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00', 'America/Mexico_City'));

        Livewire::test('pages::crm.newsletter')
            ->set('name', $input['name'] ?? 'Campaña')
            ->set('templateId', array_key_exists('templateId', $input) ? $input['templateId'] : 42)
            ->set('scheduledAt', $input['scheduledAt'] ?? '')
            ->call('sendCampaign')
            ->assertHasErrors($field);

        expect(NewsletterCampaign::count())->toBe(0);
        Http::assertNothingSent();
    })->with([
        'no name' => [['name' => ''], 'name'],
        'no template' => [['templateId' => null], 'templateId'],
        'schedule too soon' => [['scheduledAt' => '2026-11-01T10:05'], 'scheduledAt'],
        'schedule in the past' => [['scheduledAt' => '2026-10-01T10:00'], 'scheduledAt'],
    ]);

    test('shows the brevo error and records nothing when the campaign cannot be created', function () {
        Http::fake([
            'api.brevo.com/v3/contacts/lists*' => Http::response(['lists' => [['id' => 7, 'name' => 'newsletter']], 'count' => 1]),
            'api.brevo.com/v3/emailCampaigns' => Http::response(['message' => 'Template not found'], 400),
        ]);

        Livewire::test('pages::crm.newsletter')
            ->set('name', 'Campaña')
            ->set('templateId', 999)
            ->call('sendCampaign')
            ->assertHasErrors('campaign')
            ->assertSee('Template not found');

        expect(NewsletterCampaign::count())->toBe(0);
    });

    test('lists the campaigns sent from the crm', function () {
        NewsletterCampaign::factory()->create(['name' => 'Newsletter de septiembre']);

        $this->get(route('crm.newsletter'))->assertSee('Newsletter de septiembre');
    });
});
