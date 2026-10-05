<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Exceptions\BrevoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo de la API transaccional de Brevo.
 */
class BrevoClient
{
    /**
     * Envía una plantilla de Brevo a un destinatario y devuelve el messageId.
     *
     * @param  array<string, string|int|null>  $params  Variables disponibles en la plantilla como {{ params.NOMBRE }}
     *
     * @throws BrevoException con `retryable` en true para errores temporales (red, 429, 5xx)
     */
    public function sendTemplate(int $templateId, string $email, string $name, array $params = []): string
    {
        $apiKey = config('crm.brevo.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new BrevoException('Falta configurar BREVO_API_KEY.');
        }

        try {
            $response = Http::withHeaders(['api-key' => $apiKey, 'accept' => 'application/json'])
                ->timeout(15)
                ->post(config('crm.brevo.base_url').'/smtp/email', [
                    'templateId' => $templateId,
                    'to' => [['email' => $email, 'name' => $name]],
                    'params' => $params,
                ]);
        } catch (ConnectionException $exception) {
            throw new BrevoException('No se pudo conectar con Brevo: '.$exception->getMessage(), retryable: true);
        }

        if ($response->successful()) {
            return (string) $response->json('messageId');
        }

        $status = $response->status();

        throw new BrevoException(
            "Brevo respondió {$status}: ".($response->json('message') ?? $response->reason()),
            retryable: $status === 429 || $status >= 500,
        );
    }
}
