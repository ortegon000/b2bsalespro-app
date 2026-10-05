<?php

use App\Domain\Crm\Actions\ActivateReinforcement;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Jobs\SendReinforcementEmail;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Models\SequenceStep;
use App\Domain\Crm\Models\Subscription;
use App\Domain\Crm\Models\TeamMember;
use App\Domain\Crm\Services\BrevoClient;
use App\Domain\Crm\Services\ReinforcementEmail;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'crm.brevo.api_key' => 'test-key',
        'crm.brevo.sender_name' => 'B2B Sales Pro',
        'crm.brevo.sender_email' => 'cursos@b2bsalespro.mx',
    ]);
});

/**
 * Días que ya tienen su vista en el repo, leídos del disco para poder usarlos como dataset.
 *
 * @return array<string, array{int}>
 */
function emailDays(): array
{
    $days = [];

    foreach (glob(__DIR__.'/../../../resources/views/emails/crm/reinforcement/dia-*.blade.php') ?: [] as $file) {
        preg_match('/dia-(\d+)\.blade\.php$/', $file, $match);
        $days["day {$match[1]}"] = [(int) $match[1]];
    }

    return $days;
}

/**
 * Envío del día 1 (que tiene vista propia) listo para que lo procese el job.
 */
function viewSend(int $day = 1, ?int $template = null): Send
{
    $course = Course::factory()->create(['title' => 'Curso de ventas B2B']);
    $contact = Contact::factory()->for($course->company)->create(['name' => 'Rosa Elena Díaz', 'email' => 'rosa@acme.test']);
    $subscription = Subscription::factory()->for($course)->for($contact)->create();
    $sequence = Sequence::factory()->create();

    // Secuencia de 2 pasos: el día 1 (con vista propia) y el día 100 (sin vista).
    $steps = [
        1 => SequenceStep::factory()->for($sequence)->create(['day' => 1, 'brevo_template_id' => null]),
        100 => SequenceStep::factory()->for($sequence)->create(['day' => 100, 'brevo_template_id' => null]),
    ];
    $steps[$day]->update(['brevo_template_id' => $template]);

    return Send::factory()->for($subscription)->for($steps[$day], 'step')->status(SendStatus::Queued)->create();
}

function runSendJob(Send $send): void
{
    (new SendReinforcementEmail($send->id))->handle(app(BrevoClient::class), app(ReinforcementEmail::class));
}

describe('views', function () {
    test('every day with a view renders a complete email', function (int $day) {
        $emails = app(ReinforcementEmail::class);
        $mail = $emails->render($day, $emails->sampleData(30));

        expect($mail['subject'])->not->toBeEmpty()
            ->and($mail['html'])->toStartWith('<!DOCTYPE html>')
            ->and($mail['html'])->toContain('<title>'.e($mail['subject'], false))
            ->and($mail['html'])->toContain('María')
            ->and($mail['html'])->toContain(url('/'))
            ->and($mail['html'])->toContain(config('crm.email_logo_url'))
            ->and($mail['html'])->not->toContain('{{')
            ->and($mail['html'])->not->toContain('@yield')
            ->and($mail['html'])->not->toContain('@section')
            ->and($mail['html'])->not->toContain('pexels')
            ->and(substr_count($mail['html'], '<img'))->toBe(1);
    })->with(fn () => emailDays());

    test('day one keeps the content of the original email and fills the variables', function () {
        $emails = app(ReinforcementEmail::class);
        $mail = $emails->render(1, ['nombre' => 'Rosa', 'total' => 30] + $emails->sampleData(30));

        expect($mail['subject'])->toBe('Actividad 1 de 30: Define tu Norte')
            ->and($mail['html'])->toContain('¡Hola Rosa!')
            ->and($mail['html'])->toContain('día 1 de 30')
            ->and($mail['html'])->toContain('La Misión de Hoy: Define tu Norte')
            ->and($mail['html'])->toContain('DEFINIDA:')
            ->and($mail['html'])->toContain('Nos vemos en el correo #2.');
    });

    test('the last day has no "see you in the next email" line and values are escaped', function () {
        $emails = app(ReinforcementEmail::class);
        $mail = $emails->render(1, ['nombre' => '<b>Rosa</b>', 'total' => 1] + $emails->sampleData(1));

        expect($mail['html'])->not->toContain('Nos vemos en el correo')
            ->and($mail['html'])->toContain('¡Hola &lt;b&gt;Rosa&lt;/b&gt;!')
            ->and($mail['html'])->toContain('día 1 de 1');
    });

    test('knows which days have a view', function () {
        $emails = app(ReinforcementEmail::class);

        expect($emails->exists(1))->toBeTrue()
            ->and($emails->exists(0))->toBeFalse()
            ->and($emails->exists(100))->toBeFalse()
            ->and($emails->availableDays())->toContain(1)
            ->and($emails->viewName(7))->toBe('emails.crm.reinforcement.dia-07');
    });

    test('rendering a day without a view or without a subject is a clear error', function () {
        $emails = app(ReinforcementEmail::class);

        expect(fn () => $emails->render(100, $emails->sampleData(30)))->toThrow(RuntimeException::class, 'no tiene vista');

        $path = resource_path('views/emails/crm/reinforcement/dia-98.blade.php');
        File::put($path, "@extends('emails.crm.reinforcement.layout')\n@section('titulo', 'Sin asunto')\n@section('contenido')<p>Hola</p>@endsection\n");

        try {
            expect(fn () => $emails->render(98, $emails->sampleData(30)))->toThrow(RuntimeException::class, 'no define su asunto');
        } finally {
            File::delete($path);
        }
    });
});

describe('readiness', function () {
    test('a day is ready with its own email even without a brevo template, and not ready with neither', function () {
        $sequence = Sequence::factory()->create();
        SequenceStep::factory()->for($sequence)->create(['day' => 1, 'brevo_template_id' => null]);

        expect($sequence->isReady())->toBeTrue();

        $late = SequenceStep::factory()->for($sequence)->create(['day' => 100, 'brevo_template_id' => null]);

        expect($sequence->isReady())->toBeFalse();

        $late->update(['brevo_template_id' => 55]);

        expect($sequence->isReady())->toBeTrue();
    });

    test('a sequence without steps is never ready', function () {
        expect(Sequence::factory()->create()->isReady())->toBeFalse();
    });

    test('the reinforcement can be activated relying on the own email of the day', function () {
        $sequence = Sequence::factory()->create();
        SequenceStep::factory()->for($sequence)->create(['day' => 1, 'brevo_template_id' => null]);
        $course = Course::factory()->for($sequence)->create();
        $course->contacts()->attach(Contact::factory()->for($course->company)->create());

        expect(app(ActivateReinforcement::class)->handle($course, now()->addDay()->toDateString()))->toBe(1)
            ->and(Send::count())->toBe(1);
    });
});

describe('sending', function () {
    test('a day with its own email is sent as html from the configured sender with tags and unsubscribe headers', function () {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);
        $send = viewSend();

        runSendJob($send);

        Http::assertSent(function (Request $request) use ($send) {
            $unsubscribe = URL::signedRoute('crm.unsubscribe.show', ['subscription' => $send->subscription_id]);

            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-key')
                && $request['sender'] === ['name' => 'B2B Sales Pro', 'email' => 'cursos@b2bsalespro.mx']
                && $request['to'] === [['email' => 'rosa@acme.test', 'name' => 'Rosa Elena Díaz']]
                && $request['subject'] === 'Actividad 1 de 2: Define tu Norte'
                && str_contains($request['htmlContent'], '¡Hola Rosa!')
                && str_contains($request['htmlContent'], 'Nos vemos en el correo #2.')
                && str_contains($request['htmlContent'], e($unsubscribe))
                && $request['tags'] === ['crm-refuerzo', 'dia-1']
                && $request['headers'] === ['List-Unsubscribe' => "<{$unsubscribe}>", 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']
                && ! isset($request['templateId']);
        });

        expect($send->fresh()->status)->toBe(SendStatus::Sent)
            ->and($send->fresh()->brevo_message_id)->toBe('<abc@brevo>');
    });

    test('the unsubscribe link of the email is signed and points to its subscription', function () {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);
        $send = viewSend();

        runSendJob($send);

        Http::assertSent(function (Request $request) use ($send) {
            $header = trim($request['headers']['List-Unsubscribe'], '<>');

            return URL::hasValidSignature(Illuminate\Http\Request::create($header))
                && str_contains($header, "/crm/baja/{$send->subscription_id}");
        });
    });

    test('a day without its own email still goes out with its brevo template', function () {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);
        $send = viewSend(day: 100, template: 4321);

        runSendJob($send);

        Http::assertSent(fn (Request $request) => $request['templateId'] === 4321 && ! isset($request['htmlContent']));
        expect($send->fresh()->status)->toBe(SendStatus::Sent);
    });

    test('a day with neither an email nor a template fails with a clear message', function () {
        Http::fake();
        $send = viewSend(day: 100);

        runSendJob($send);

        Http::assertNothingSent();
        expect($send->fresh()->status)->toBe(SendStatus::Failed)
            ->and($send->fresh()->error)->toContain('El día 100 no tiene correo');
    });

    test('the client omits tags and headers when there are none', function () {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<x>'], 201)]);

        expect(app(BrevoClient::class)->sendHtml('Asunto', '<p>Hola</p>', 'a@b.test', 'Ana'))->toBe('<x>');

        Http::assertSent(fn (Request $request) => $request['htmlContent'] === '<p>Hola</p>' && ! isset($request['tags']) && ! isset($request['headers']));
    });
});

describe('unsubscribe link', function () {
    function subscriptionToUnsubscribe(): Subscription
    {
        $subscription = Subscription::factory()->create();
        Send::factory()->for($subscription)->create();
        Send::factory()->for($subscription)->status(SendStatus::Sent)->create();

        return $subscription;
    }

    test('only a valid signature opens the page or unsubscribes', function () {
        $subscription = subscriptionToUnsubscribe();
        $url = route('crm.unsubscribe.show', $subscription);

        $this->get($url)->assertForbidden();
        $this->post($url)->assertForbidden();
        $this->get(URL::signedRoute('crm.unsubscribe.show', ['subscription' => $subscription->id]).'x')->assertForbidden();

        expect($subscription->contact->fresh()->unsubscribed_at)->toBeNull();
    });

    test('opening the link only asks for confirmation and does not unsubscribe anyone', function () {
        $subscription = subscriptionToUnsubscribe();

        $this->get(URL::signedRoute('crm.unsubscribe.show', ['subscription' => $subscription->id]))
            ->assertOk()
            ->assertSee('¿Quieres dejar de recibir estos correos?')
            ->assertSee($subscription->course->title)
            ->assertSee('Sí, darme de baja');

        expect($subscription->contact->fresh()->unsubscribed_at)->toBeNull()
            ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    });

    test('confirming unsubscribes the contact and cancels what was pending', function () {
        $subscription = subscriptionToUnsubscribe();
        $other = Subscription::factory()->for($subscription->contact)->create();
        $url = URL::signedRoute('crm.unsubscribe.show', ['subscription' => $subscription->id]);

        $this->post($url)->assertOk()->assertSee('ya no recibirás estos correos');

        expect($subscription->contact->fresh()->unsubscribed_at)->not->toBeNull()
            ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Unsubscribed)
            ->and($other->fresh()->status)->toBe(SubscriptionStatus::Unsubscribed)
            ->and($subscription->sends()->where('status', SendStatus::Cancelled)->count())->toBe(1)
            ->and($subscription->sends()->where('status', SendStatus::Sent)->count())->toBe(1);
    });

    test('confirming twice changes nothing more and the page then says it is done', function () {
        $subscription = subscriptionToUnsubscribe();
        $url = URL::signedRoute('crm.unsubscribe.show', ['subscription' => $subscription->id]);

        $this->post($url);
        $firstTime = $subscription->contact->fresh()->unsubscribed_at;
        $this->travel(5)->minutes();
        $this->post($url)->assertOk();

        expect($subscription->contact->fresh()->unsubscribed_at->equalTo($firstTime))->toBeTrue();

        $this->get($url)->assertSee('ya no recibirás estos correos');
    });
});

describe('preview and test email', function () {
    beforeEach(function () {
        $this->member = TeamMember::factory()->create();
        $this->actingAs($this->member->user);
        $this->sequence = Sequence::factory()->create();
        SequenceStep::factory()->for($this->sequence)->create(['day' => 1]);
        SequenceStep::factory()->for($this->sequence)->create(['day' => 100]);
    });

    test('the preview shows the email with sample data', function () {
        $this->get(route('crm.sequences.preview', [$this->sequence, 1]))
            ->assertOk()
            ->assertSee('¡Hola María!', false)
            ->assertSee('Actividad 1 de 2', false);
    });

    test('the preview is closed to users outside the crm team', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('crm.sequences.preview', [$this->sequence, 1]))->assertForbidden();
    });

    test('the preview is not found for a day without a view or outside the sequence', function () {
        $this->get(route('crm.sequences.preview', [$this->sequence, 100]))->assertNotFound();
        $this->get(route('crm.sequences.preview', [$this->sequence, 2]))->assertNotFound();
    });

    test('the sequence page lists each day with its email, subject and preview link', function () {
        $this->get(route('crm.sequences.edit', $this->sequence))
            ->assertOk()
            ->assertSee('Correo propio')
            ->assertSee('Actividad 1 de 2: Define tu Norte')
            ->assertSee(route('crm.sequences.preview', [$this->sequence, 1]));
    });

    test('a test email goes to the current user with sample data, marked as a test, without creating sends', function () {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<t>'], 201)]);

        Livewire::test('pages::crm.sequence', ['sequence' => $this->sequence])->call('sendTest', 1);

        Http::assertSent(fn (Request $request) => $request['to'][0]['email'] === $this->member->user->email
            && $request['to'][0]['name'] === $this->member->user->name
            && $request['subject'] === '[PRUEBA] Actividad 1 de 2: Define tu Norte'
            && str_contains($request['htmlContent'], '¡Hola '.explode(' ', $this->member->user->name)[0].'!')
            && $request['tags'] === ['crm-prueba']
            && ! isset($request['headers']));

        expect(Send::count())->toBe(0)->and(Subscription::count())->toBe(0);
    });

    test('a failing test email is reported without breaking the page', function () {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Sender not valid'], 400)]);

        Livewire::test('pages::crm.sequence', ['sequence' => $this->sequence])
            ->call('sendTest', 1)
            ->assertDispatched('toast-show');
    });

    test('a test cannot be sent for a day without its own email', function () {
        Http::fake();

        Livewire::test('pages::crm.sequence', ['sequence' => $this->sequence])->call('sendTest', 100)->assertStatus(404);

        Http::assertNothingSent();
    });
});
