<?php

namespace App\Domain\Crm\Jobs;

use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Services\BrevoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SyncContactToNewsletter implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $contactId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(BrevoClient $brevo): void
    {
        $contact = Contact::find($this->contactId);

        if ($contact === null || $contact->newsletter_synced_at !== null || ! $contact->canReceiveEmail()) {
            return;
        }

        try {
            $brevo->upsertContact(
                $contact->email,
                Str::before($contact->name, ' '),
                Str::contains($contact->name, ' ') ? Str::after($contact->name, ' ') : '',
                $brevo->newsletterListId(),
            );
        } catch (BrevoException $exception) {
            if ($exception->retryable) {
                throw $exception;
            }

            // Error permanente (clave inválida, lista inexistente…): el contacto queda pendiente.
            $this->fail($exception);

            return;
        }

        $contact->update(['newsletter_synced_at' => now()]);
    }
}
