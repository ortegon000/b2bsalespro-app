<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Actions\UnsubscribeContact;
use App\Domain\Crm\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Baja con un clic desde el enlace firmado de los correos de refuerzo.
 *
 * El GET solo muestra la confirmación: los escáneres de correo siguen los enlaces y no deben
 * dar de baja a nadie. El POST (botón de la página o `List-Unsubscribe-Post` del cliente de
 * correo) es el que registra la baja; no usa token CSRF porque lo protege la firma de la URL.
 */
class UnsubscribeController extends Controller
{
    public function show(Subscription $subscription): View
    {
        return view('crm.unsubscribe', [
            'course' => $subscription->course->title,
            'done' => $subscription->contact->unsubscribed_at !== null,
        ]);
    }

    public function destroy(Subscription $subscription, UnsubscribeContact $unsubscribeContact): View
    {
        $unsubscribeContact->handle($subscription->contact);

        return view('crm.unsubscribe', ['course' => $subscription->course->title, 'done' => true]);
    }
}
