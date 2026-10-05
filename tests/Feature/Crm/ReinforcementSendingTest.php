<?php

use App\Domain\Crm\Actions\DispatchDueSends;
use App\Domain\Crm\Actions\RecordBrevoEvent;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Jobs\SendReinforcementEmail;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\SequenceStep;
use App\Domain\Crm\Models\Subscription;
use App\Domain\Crm\Services\BrevoClient;
use App\Domain\Crm\Services\ReinforcementEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'crm.brevo.api_key' => 'test-key',
        'crm.brevo.webhook_token' => 'hook-token',
    ]);
});

/**
 * Envío de la secuencia listo para que lo procese el job.
 */
function queuedSend(array $send = [], array $contact = []): Send
{
    $course = Course::factory()->create(['title' => 'Curso de ventas B2B']);
    $subscription = Subscription::factory()->for($course)->for(Contact::factory()->for($course->company)->create(['name' => 'Rosa Díaz'] + $contact))->create();
    $step = SequenceStep::factory()->create(['day' => 7, 'brevo_template_id' => 4321]);

    return Send::factory()->for($subscription)->for($step, 'step')->status(SendStatus::Queued)->create($send);
}

test('the job sends the brevo template with the contact data and records the message id', function () {
    Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);
    $send = queuedSend();

    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));

    Http::assertSent(fn ($request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
        && $request->hasHeader('api-key', 'test-key')
        && $request['templateId'] === 4321
        && $request['to'][0]['email'] === $send->subscription->contact->email
        && $request['params']['NOMBRE'] === 'Rosa'
        && $request['params']['EMPRESA'] === $send->subscription->course->company->name
        && $request['params']['CURSO'] === 'Curso de ventas B2B'
        && $request['params']['DIA'] === 7);

    expect($send->fresh()->status)->toBe(SendStatus::Sent)
        ->and($send->fresh()->brevo_message_id)->toBe('<abc@brevo>')
        ->and($send->fresh()->sent_at)->not->toBeNull();
});

test('a permanent brevo error marks the send as failed without retrying', function () {
    Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Template not found'], 400)]);
    $send = queuedSend();

    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));

    expect($send->fresh()->status)->toBe(SendStatus::Failed)
        ->and($send->fresh()->error)->toContain('400')->toContain('Template not found');
});

test('a temporary brevo error is retried and only fails when attempts run out', function (int $status) {
    Http::fake(['api.brevo.com/*' => Http::response([], $status)]);
    $send = queuedSend();
    $job = new SendReinforcementEmail($send->id);

    expect(fn () => $job->handle(app(BrevoClient::class), app(ReinforcementEmail::class)))->toThrow(BrevoException::class);
    expect($send->fresh()->status)->toBe(SendStatus::Queued);

    $job->failed(new BrevoException('Brevo respondió '.$status));

    expect($send->fresh()->status)->toBe(SendStatus::Failed)
        ->and($send->fresh()->error)->toContain((string) $status);
})->with([429, 503]);

test('a missing api key fails the send with a clear message', function () {
    config(['crm.brevo.api_key' => null]);
    Http::fake();
    $send = queuedSend();

    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));

    Http::assertNothingSent();
    expect($send->fresh()->error)->toContain('BREVO_API_KEY');
});

test('the job never sends something that is not queued', function (SendStatus $status) {
    Http::fake();
    $send = queuedSend(['status' => $status]);

    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));

    Http::assertNothingSent();
    expect($send->fresh()->status)->toBe($status);
})->with([SendStatus::Sent, SendStatus::Pending, SendStatus::Cancelled, SendStatus::Failed]);

test('the job cancels the send when the contact can no longer receive email', function (string $column) {
    Http::fake();
    $send = queuedSend(contact: [$column => now()]);

    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));

    Http::assertNothingSent();
    expect($send->fresh()->status)->toBe(SendStatus::Cancelled);
})->with(['unsubscribed_at', 'bounced_at']);

test('the job cancels the send when the subscription is paused', function () {
    Http::fake();
    $send = queuedSend();
    $send->subscription->update(['status' => SubscriptionStatus::Paused]);

    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));

    expect($send->fresh()->status)->toBe(SendStatus::Cancelled);
});

test('the dispatcher queues only due pending sends of active subscriptions, once', function () {
    Queue::fake();
    $active = Subscription::factory()->create();
    $paused = Subscription::factory()->create(['status' => SubscriptionStatus::Paused]);

    $due = Send::factory()->for($active)->create(['scheduled_for' => now()->subMinute()]);
    Send::factory()->for($active)->create(['scheduled_for' => now()->addHour()]);
    Send::factory()->for($paused)->create(['scheduled_for' => now()->subMinute()]);
    Send::factory()->for($active)->status(SendStatus::Skipped)->create(['scheduled_for' => now()->subMinute()]);

    expect(app(DispatchDueSends::class)->handle())->toBe(1)
        ->and(app(DispatchDueSends::class)->handle())->toBe(0);

    Queue::assertPushed(SendReinforcementEmail::class, 1);
    Queue::assertPushed(SendReinforcementEmail::class, fn ($job) => $job->sendId === $due->id);
    expect($due->fresh()->status)->toBe(SendStatus::Queued);
});

test('the scheduled command dispatches the due sends', function () {
    Queue::fake();
    Send::factory()->create(['scheduled_for' => now()->subMinute()]);

    $this->artisan('crm:dispatch-sends')->expectsOutputToContain('Envíos encolados: 1')->assertSuccessful();

    Queue::assertPushed(SendReinforcementEmail::class);
});

test('an unsubscribe event blocks the contact and cancels everything pending', function () {
    $subscription = Subscription::factory()->create();
    $sent = Send::factory()->for($subscription)->status(SendStatus::Sent)->create();
    $pending = Send::factory()->for($subscription)->create();
    $skipped = Send::factory()->for($subscription)->status(SendStatus::Skipped)->create();
    $email = $subscription->contact->email;

    expect(app(RecordBrevoEvent::class)->handle('unsubscribed', $email))->toBeTrue();

    expect($subscription->contact->fresh()->unsubscribed_at)->not->toBeNull()
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Unsubscribed)
        ->and($pending->fresh()->status)->toBe(SendStatus::Cancelled)
        ->and($skipped->fresh()->status)->toBe(SendStatus::Cancelled)
        ->and($sent->fresh()->status)->toBe(SendStatus::Sent);
});

test('blocking events mark the contact as unsubscribed or bounced', function (string $event, string $column, SubscriptionStatus $status) {
    $subscription = Subscription::factory()->create();

    app(RecordBrevoEvent::class)->handle($event, $subscription->contact->email);

    expect($subscription->contact->fresh()->{$column})->not->toBeNull()
        ->and($subscription->fresh()->status)->toBe($status);
})->with([
    ['spam', 'unsubscribed_at', SubscriptionStatus::Unsubscribed],
    ['hard_bounce', 'bounced_at', SubscriptionStatus::Bounced],
    ['blocked', 'bounced_at', SubscriptionStatus::Bounced],
    ['invalid_email', 'bounced_at', SubscriptionStatus::Bounced],
]);

test('harmless events and unknown emails change nothing', function (string $event, ?string $email) {
    $subscription = Subscription::factory()->create();
    $send = Send::factory()->for($subscription)->create();

    expect(app(RecordBrevoEvent::class)->handle($event, $email ?? $subscription->contact->email))->toBeFalse();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($send->fresh()->status)->toBe(SendStatus::Pending);
})->with([
    'delivered' => ['delivered', null],
    'opened' => ['opened', null],
    'soft bounce' => ['soft_bounce', null],
    'unknown contact' => ['unsubscribed', 'nadie@nada.test'],
]);

test('the webhook endpoint requires its token', function (?string $token) {
    $contact = Contact::factory()->create();

    $this->postJson(route('crm.brevo.webhook'), ['event' => 'unsubscribed', 'email' => $contact->email], $token ? ['Authorization' => "Bearer {$token}"] : [])
        ->assertUnauthorized();

    expect($contact->fresh()->unsubscribed_at)->toBeNull();
})->with(['no token' => [null], 'wrong token' => ['otro']]);

test('the webhook endpoint applies the event ignoring email case', function () {
    $contact = Contact::factory()->create(['email' => 'rosa@acme.test']);

    $this->postJson(route('crm.brevo.webhook'), ['event' => 'hard_bounce', 'email' => 'ROSA@Acme.test'], ['Authorization' => 'Bearer hook-token'])
        ->assertNoContent();

    expect($contact->fresh()->bounced_at)->not->toBeNull();
});

test('the webhook endpoint answers no content to payloads it does not understand', function () {
    $this->postJson(route('crm.brevo.webhook'), ['foo' => 'bar'], ['Authorization' => 'Bearer hook-token'])->assertNoContent();
});
