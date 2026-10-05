<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Subscription;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use RuntimeException;

/**
 * Renderiza los correos de la secuencia de refuerzo a partir de las vistas Blade
 * `resources/views/emails/crm/reinforcement/dia-NN.blade.php`.
 *
 * Es el único lugar que conoce el nombre de esas vistas. Un día sin vista sigue
 * saliendo por su plantilla de Brevo (ver SendReinforcementEmail).
 */
class ReinforcementEmail
{
    private const string VIEW_PREFIX = 'emails.crm.reinforcement.dia-';

    public function viewName(int $day): string
    {
        return self::VIEW_PREFIX.sprintf('%02d', $day);
    }

    public function exists(int $day): bool
    {
        return $day >= 1 && View::exists($this->viewName($day));
    }

    /**
     * Días (1..99) que ya tienen su vista.
     *
     * @return list<int>
     */
    public function availableDays(): array
    {
        return array_values(array_filter(range(1, 99), fn (int $day) => $this->exists($day)));
    }

    /**
     * Renderiza el correo de un día y devuelve su asunto (definido en la vista con `@section('asunto')`)
     * y el HTML final (el asunto sale del `<title>` del layout).
     *
     * @param  array{nombre: string, nombreCompleto: string, empresa: string, curso: string, total: int, bajaUrl: string}  $data
     * @return array{subject: string, html: string}
     */
    public function render(int $day, array $data): array
    {
        if (! $this->exists($day)) {
            throw new RuntimeException("El día {$day} no tiene vista de correo.");
        }

        $html = View::make($this->viewName($day), $data + ['dia' => $day, 'siguiente' => $day < $data['total'] ? $day + 1 : null])->render();

        // El layout pone el asunto (`@section('asunto')`) en el <title>.
        preg_match('/<title>(.*?)<\/title>/s', $html, $title);
        $subject = trim(html_entity_decode(strip_tags($title[1] ?? '')));

        if ($subject === '') {
            throw new RuntimeException("La vista del día {$day} no define su asunto (@section('asunto', …)).");
        }

        return ['subject' => $subject, 'html' => $html];
    }

    /**
     * Datos de ejemplo para la vista previa y las pruebas.
     *
     * @return array{nombre: string, nombreCompleto: string, empresa: string, curso: string, total: int, bajaUrl: string}
     */
    public function sampleData(int $total, string $name = 'María López'): array
    {
        return [
            'nombre' => explode(' ', $name)[0],
            'nombreCompleto' => $name,
            'empresa' => 'Empresa de Ejemplo',
            'curso' => 'Curso de ventas B2B',
            'total' => $total,
            'bajaUrl' => url('/'),
        ];
    }

    /**
     * Datos reales de una suscripción.
     *
     * @return array{nombre: string, nombreCompleto: string, empresa: string, curso: string, total: int, bajaUrl: string}
     */
    public function dataFor(Subscription $subscription, int $total): array
    {
        $contact = $subscription->contact;

        return [
            'nombre' => explode(' ', trim($contact->name))[0],
            'nombreCompleto' => $contact->name,
            'empresa' => $subscription->course->company->name,
            'curso' => $subscription->course->title,
            'total' => $total,
            'bajaUrl' => $this->unsubscribeUrl($subscription),
        ];
    }

    /**
     * Enlace de baja firmado (sin caducidad: tiene que servir mientras el correo exista).
     */
    public function unsubscribeUrl(Subscription $subscription): string
    {
        return URL::signedRoute('crm.unsubscribe.show', ['subscription' => $subscription->id]);
    }
}
