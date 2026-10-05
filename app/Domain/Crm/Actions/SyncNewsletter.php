<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Jobs\SyncContactToNewsletter;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Services\BrevoClient;
use Illuminate\Database\Eloquent\Builder;

class SyncNewsletter
{
    public function __construct(private BrevoClient $brevo) {}

    /**
     * Contactos que ya tomaron un curso impartido, pueden recibir correo y aún no están en la lista.
     *
     * @param  array<int, int|string>|null  $contactIds
     * @return Builder<Contact>
     */
    public function pendingQuery(?Course $course = null, ?array $contactIds = null): Builder
    {
        return Contact::query()
            ->whereNull('newsletter_synced_at')
            ->whereNull('unsubscribed_at')
            ->whereNull('bounced_at')
            ->whereHas('courses', fn (Builder $query) => $query
                ->whereNotNull('delivered_at')
                ->when($course, fn (Builder $query) => $query->whereKey($course->id)))
            ->when($contactIds !== null, fn (Builder $query) => $query->whereKey($contactIds));
    }

    /**
     * Encola la sincronización de los contactos pendientes (de un curso o de todos).
     * Sin la API de Brevo configurada no hace nada: se puede ejecutar a mano después.
     *
     * @param  array<int, int|string>|null  $contactIds
     * @return int Cantidad de contactos encolados
     */
    public function handle(?Course $course = null, ?array $contactIds = null): int
    {
        if (! $this->brevo->isConfigured()) {
            return 0;
        }

        $ids = $this->pendingQuery($course, $contactIds)->pluck('id');

        foreach ($ids as $id) {
            SyncContactToNewsletter::dispatch($id);
        }

        return $ids->count();
    }
}
