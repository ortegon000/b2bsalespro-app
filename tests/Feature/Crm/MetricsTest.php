<?php

use App\Domain\Crm\Actions\RecordSendEvent;
use App\Domain\Crm\Actions\SyncCampaignStats;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\NewsletterCampaign;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Models\SequenceStep;
use App\Domain\Crm\Models\Subscription;
use App\Domain\Crm\Models\TeamMember;
use App\Domain\Crm\Services\BrevoClient;
use App\Domain\Crm\Services\SendMetrics;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['crm.brevo.api_key' => 'test-key', 'crm.brevo.webhook_token' => 'hook-token', 'crm.brevo.newsletter_list' => 'newsletter']);
});

function sentSend(array $attributes = []): Send
{
    return Send::factory()->status(SendStatus::Sent)->create(['sent_at' => now(), 'brevo_message_id' => '<202610@smtp-relay.mailin.fr>'] + $attributes);
}

describe('delivery events', function () {
    test('delivered marks the delivery once and keeps the first time', function () {
        $send = sentSend();

        app(RecordSendEvent::class)->handle('delivered', '<202610@smtp-relay.mailin.fr>', 1_700_000_000);
        app(RecordSendEvent::class)->handle('delivered', '<202610@smtp-relay.mailin.fr>', 1_800_000_000);

        expect($send->fresh()->delivered_at->timestamp)->toBe(1_700_000_000);
    });

    test('opened counts every open and keeps the first time, while unique_opened only marks the first', function () {
        $send = sentSend();

        app(RecordSendEvent::class)->handle('unique_opened', '<202610@smtp-relay.mailin.fr>', 1_700_000_000);
        expect($send->fresh()->opened_at->timestamp)->toBe(1_700_000_000)->and($send->fresh()->opens_count)->toBe(0);

        app(RecordSendEvent::class)->handle('opened', '<202610@smtp-relay.mailin.fr>', 1_800_000_000);
        app(RecordSendEvent::class)->handle('opened', '<202610@smtp-relay.mailin.fr>', 1_800_000_100);

        expect($send->fresh()->opened_at->timestamp)->toBe(1_700_000_000)->and($send->fresh()->opens_count)->toBe(2);
    });

    test('a click counts, marks the click and implies the email was opened', function () {
        $send = sentSend();

        app(RecordSendEvent::class)->handle('click', '<202610@smtp-relay.mailin.fr>', 1_700_000_000);
        app(RecordSendEvent::class)->handle('click', '<202610@smtp-relay.mailin.fr>', 1_700_000_500);

        $send->refresh();

        expect($send->clicked_at->timestamp)->toBe(1_700_000_000)
            ->and($send->clicks_count)->toBe(2)
            ->and($send->opened_at->timestamp)->toBe(1_700_000_000)
            ->and($send->opens_count)->toBe(0);
    });

    test('event names are accepted in snake_case and camelCase, and proxy opens are ignored', function (string $event, bool $applies) {
        $send = sentSend();

        expect(app(RecordSendEvent::class)->handle($event, '<202610@smtp-relay.mailin.fr>'))->toBe($applies);
    })->with([
        'unique_opened' => ['unique_opened', true],
        'uniqueOpened' => ['uniqueOpened', true],
        'opened' => ['opened', true],
        'delivered' => ['delivered', true],
        'click' => ['click', true],
        'proxyOpen' => ['proxyOpen', false],
        'uniqueProxyOpen' => ['uniqueProxyOpen', false],
        'proxy_open' => ['proxy_open', false],
    ]);

    test('the message id matches with or without angle brackets', function (string $given) {
        $send = sentSend();

        expect(app(RecordSendEvent::class)->handle('delivered', $given))->toBeTrue()
            ->and($send->fresh()->delivered_at)->not->toBeNull();
    })->with(['with brackets' => '<202610@smtp-relay.mailin.fr>', 'without brackets' => '202610@smtp-relay.mailin.fr']);

    test('unknown messages, blank ids and other events change nothing', function (string $event, ?string $messageId) {
        $send = sentSend();

        expect(app(RecordSendEvent::class)->handle($event, $messageId))->toBeFalse();

        expect($send->fresh()->only(['delivered_at', 'opened_at', 'clicked_at']))->each->toBeNull();
    })->with([
        'unknown message' => ['delivered', '<otro@id>'],
        'blank id' => ['delivered', ''],
        'no id' => ['opened', null],
        'request' => ['request', '<202610@smtp-relay.mailin.fr>'],
        'soft bounce' => ['soft_bounce', '<202610@smtp-relay.mailin.fr>'],
    ]);

    test('the webhook endpoint records engagement events using the event timestamp', function () {
        $send = sentSend();

        $this->postJson(route('crm.brevo.webhook'), [
            'event' => 'click', 'email' => 'x@y.test', 'message-id' => '<202610@smtp-relay.mailin.fr>', 'ts_event' => 1_700_000_000,
        ], ['Authorization' => 'Bearer hook-token'])->assertNoContent();

        expect($send->fresh()->clicked_at->timestamp)->toBe(1_700_000_000)
            ->and($send->fresh()->clicks_count)->toBe(1);
    });

    test('a blocking event that carries a message id still blocks the contact', function () {
        $send = sentSend();
        $contact = $send->subscription->contact;

        $this->postJson(route('crm.brevo.webhook'), [
            'event' => 'unsubscribed', 'email' => $contact->email, 'message-id' => '<202610@smtp-relay.mailin.fr>',
        ], ['Authorization' => 'Bearer hook-token'])->assertNoContent();

        expect($contact->fresh()->unsubscribed_at)->not->toBeNull();
    });
});

describe('metrics', function () {
    test('summarizes sent, delivered, opened, clicked and failed sends', function () {
        sentSend(['delivered_at' => now(), 'opened_at' => now(), 'clicked_at' => now(), 'brevo_message_id' => 'a']);
        sentSend(['delivered_at' => now(), 'opened_at' => now(), 'brevo_message_id' => 'b']);
        sentSend(['delivered_at' => now(), 'brevo_message_id' => 'c']);
        sentSend(['brevo_message_id' => 'd']);
        Send::factory()->status(SendStatus::Failed)->create();
        Send::factory()->create();

        expect(app(SendMetrics::class)->summary(Send::query()))->toBe(['sent' => 4, 'delivered' => 3, 'opened' => 2, 'clicked' => 1, 'failed' => 1]);
    });

    test('rates are whole percentages and null without a base', function () {
        $metrics = app(SendMetrics::class);

        expect($metrics->rate(1, 3))->toBe(33)
            ->and($metrics->rate(2, 3))->toBe(67)
            ->and($metrics->rate(0, 5))->toBe(0)
            ->and($metrics->rate(0, 0))->toBeNull();
    });

    test('the course page shows the results of its reinforcement and per-person engagement', function () {
        $this->actingAs(TeamMember::factory()->create()->user);
        $course = Course::factory()->create(['reinforcement_activated_at' => now(), 'reinforcement_starts_on' => now()->toDateString()]);
        $subscription = Subscription::factory()->for($course)->create();
        $course->contacts()->attach($subscription->contact_id);
        $other = Subscription::factory()->create();

        foreach (range(1, 4) as $day) {
            Send::factory()->for($subscription)->status(SendStatus::Sent)->create([
                'delivered_at' => now(),
                'opened_at' => $day <= 2 ? now() : null,
                'clicked_at' => $day === 1 ? now() : null,
            ]);
        }
        Send::factory()->for($other)->status(SendStatus::Sent)->create(['opened_at' => now()]);

        $this->get(route('crm.courses.show', $course))
            ->assertOk()
            ->assertSee('Resultados del refuerzo')
            ->assertSeeInOrder(['Enviados', '4', 'Entregados', '4', '100%', 'Abiertos', '2', '50%', 'Con clic', '1', '25%'])
            ->assertSee('2 abiertos')
            ->assertSee('1 clic');
    });

    test('the course page has no results block before anything is sent', function () {
        $this->actingAs(TeamMember::factory()->create()->user);
        $course = Course::factory()->create(['reinforcement_activated_at' => now(), 'reinforcement_starts_on' => now()->toDateString()]);
        Send::factory()->for(Subscription::factory()->for($course))->create();

        $this->get(route('crm.courses.show', $course))->assertDontSee('Resultados del refuerzo');
    });

    test('the sequence page shows how each day performs across all courses', function () {
        $this->actingAs(TeamMember::factory()->create()->user);
        $sequence = Sequence::factory()->create();
        $day1 = SequenceStep::factory()->for($sequence)->create(['day' => 1]);
        SequenceStep::factory()->for($sequence)->create(['day' => 2]);

        foreach (range(1, 4) as $i) {
            Send::factory()->for($day1, 'step')->status(SendStatus::Sent)->create([
                'opened_at' => $i <= 3 ? now() : null,
                'clicked_at' => $i === 1 ? now() : null,
            ]);
        }

        $results = Livewire::test('pages::crm.sequence', ['sequence' => $sequence])->instance()->results;

        expect($results[1])->toBe(['sent' => 4, 'opened_rate' => 75, 'clicked_rate' => 25])
            ->and($results[2])->toBe(['sent' => 0, 'opened_rate' => null, 'clicked_rate' => null]);

        $this->get(route('crm.sequences.edit', $sequence))->assertSee('4 env. · 75% abiertos · 25% clics');
    });
});

describe('campaign stats', function () {
    function fakeCampaignStats(array $global = []): void
    {
        Http::fake(['api.brevo.com/v3/emailCampaigns/*' => Http::response(['statistics' => ['globalStats' => $global + [
            'sent' => 100, 'delivered' => 96, 'uniqueViews' => 40, 'clickers' => 12, 'unsubscriptions' => 2, 'hardBounces' => 3, 'softBounces' => 1, 'complaints' => 0,
        ]]])]);
    }

    test('the client normalizes the statistics of a campaign', function () {
        fakeCampaignStats();

        expect(app(BrevoClient::class)->campaignStats(55))->toBe([
            'sent' => 100, 'delivered' => 96, 'opened' => 40, 'clicked' => 12, 'unsubscribed' => 2, 'bounced' => 4, 'complaints' => 0,
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/emailCampaigns/55') && $request['statistics'] === 'globalStats');
    });

    test('a campaign without statistics yet is read as zeros', function () {
        Http::fake(['api.brevo.com/v3/emailCampaigns/*' => Http::response(['id' => 55])]);

        expect(app(BrevoClient::class)->campaignStats(55))->each->toBe(0);
    });

    test('syncing stores the statistics of recent sent campaigns only', function () {
        fakeCampaignStats();
        $recent = NewsletterCampaign::factory()->create();
        $pending = NewsletterCampaign::factory()->create(['scheduled_for' => now()->addDay()]);
        $old = NewsletterCampaign::factory()->create(['created_at' => now()->subDays(40)]);

        expect(app(SyncCampaignStats::class)->handle())->toBe(1);

        expect($recent->fresh()->stats['opened'])->toBe(40)
            ->and($recent->fresh()->stats_synced_at)->not->toBeNull()
            ->and($pending->fresh()->stats)->toBeNull()
            ->and($old->fresh()->stats)->toBeNull();
    });

    test('an old campaign can still be refreshed on demand', function () {
        fakeCampaignStats();
        $old = NewsletterCampaign::factory()->create(['created_at' => now()->subDays(40)]);

        expect(app(SyncCampaignStats::class)->handle($old))->toBe(1)
            ->and($old->fresh()->stats['sent'])->toBe(100);
    });

    test('one failing campaign does not stop the others, and nothing runs without brevo configured', function () {
        $first = NewsletterCampaign::factory()->create(['brevo_campaign_id' => 1]);
        $second = NewsletterCampaign::factory()->create(['brevo_campaign_id' => 2]);
        Http::fake([
            'api.brevo.com/v3/emailCampaigns/1*' => Http::response(['message' => 'boom'], 500),
            'api.brevo.com/v3/emailCampaigns/2*' => Http::response(['statistics' => ['globalStats' => ['sent' => 5]]]),
        ]);

        expect(app(SyncCampaignStats::class)->handle())->toBe(1)
            ->and($first->fresh()->stats)->toBeNull()
            ->and($second->fresh()->stats['sent'])->toBe(5);

        config(['crm.brevo.api_key' => null]);
        Http::fake();

        expect(app(SyncCampaignStats::class)->handle())->toBe(0);
        Http::assertNothingSent();
    });

    test('the scheduled command syncs the statistics', function () {
        fakeCampaignStats();
        NewsletterCampaign::factory()->create();

        $this->artisan('crm:sync-campaign-stats')->expectsOutputToContain('Campañas actualizadas: 1')->assertSuccessful();
    });

    test('the newsletter page refreshes the metrics and shows them with their rates', function () {
        $this->actingAs(TeamMember::factory()->create()->user);
        fakeCampaignStats();
        $campaign = NewsletterCampaign::factory()->create(['name' => 'Newsletter de octubre']);

        Livewire::test('pages::crm.newsletter')->call('refreshStats');

        expect($campaign->fresh()->stats['delivered'])->toBe(96);

        $this->get(route('crm.newsletter'))
            ->assertSee('96 entregados')
            ->assertSee('40 abiertos (42%)')
            ->assertSee('12 con clic (13%)')
            ->assertSee('2 bajas')
            ->assertSee('4 rebotes');
    });
});
