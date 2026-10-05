<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Exceptions\BrevoException;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo de la API de Brevo: envío transaccional por plantilla, la lista del newsletter
 * y campañas.
 *
 * @throws BrevoException con `retryable` en true para errores temporales (red, 429, 5xx)
 */
class BrevoClient
{
    public function isConfigured(): bool
    {
        return filled(config('crm.brevo.api_key'));
    }

    /**
     * Envía una plantilla de Brevo a un destinatario y devuelve el messageId.
     *
     * @param  array<string, string|int|null>  $params  Variables disponibles en la plantilla como {{ params.NOMBRE }}
     */
    public function sendTemplate(int $templateId, string $email, string $name, array $params = []): string
    {
        $response = $this->request('post', '/smtp/email', [
            'templateId' => $templateId,
            'to' => [['email' => $email, 'name' => $name]],
            'params' => $params,
        ]);

        return (string) $response->json('messageId');
    }

    /**
     * ID de la lista del newsletter en Brevo, buscada por nombre (sin importar mayúsculas).
     * Se guarda en caché una hora; si la lista no existe no se cachea nada.
     */
    public function newsletterListId(): int
    {
        $name = (string) config('crm.brevo.newsletter_list');

        return Cache::remember('crm.brevo.list-id.'.mb_strtolower($name), 3600, function () use ($name): int {
            $offset = 0;

            do {
                $json = $this->request('get', '/contacts/lists', ['limit' => 50, 'offset' => $offset])->json();

                foreach ($json['lists'] ?? [] as $list) {
                    if (mb_strtolower((string) ($list['name'] ?? '')) === mb_strtolower($name)) {
                        return (int) $list['id'];
                    }
                }

                $offset += 50;
            } while ($offset < (int) ($json['count'] ?? 0));

            throw new BrevoException("No existe la lista \"{$name}\" en Brevo. Créala en Brevo (Contactos → Listas) y vuelve a intentar.");
        });
    }

    /**
     * Crea el contacto en Brevo o lo actualiza si ya existe, y lo suma a la lista.
     */
    public function upsertContact(string $email, string $firstName, string $lastName, int $listId): void
    {
        $this->request('post', '/contacts', [
            'email' => $email,
            'attributes' => ['FIRSTNAME' => $firstName, 'LASTNAME' => $lastName],
            'listIds' => [$listId],
            'updateEnabled' => true,
        ]);
    }

    /**
     * Crea una campaña de email a partir de una plantilla para una lista y devuelve su ID.
     * Con `$scheduledAt` Brevo la deja programada; sin él hay que llamar a sendCampaignNow().
     */
    public function createCampaign(string $name, int $templateId, int $listId, ?CarbonInterface $scheduledAt = null): int
    {
        $payload = [
            'name' => $name,
            'templateId' => $templateId,
            'recipients' => ['listIds' => [$listId]],
        ];

        if ($scheduledAt !== null) {
            $payload['scheduledAt'] = $scheduledAt->toIso8601String();
        }

        return (int) $this->request('post', '/emailCampaigns', $payload)->json('id');
    }

    /**
     * Estadísticas globales de una campaña, normalizadas. `opened` son aperturas únicas y
     * `clicked` las personas que hicieron clic.
     *
     * @return array{sent: int, delivered: int, opened: int, clicked: int, unsubscribed: int, bounced: int, complaints: int}
     */
    public function campaignStats(int $campaignId): array
    {
        $stats = $this->request('get', "/emailCampaigns/{$campaignId}", ['statistics' => 'globalStats'])->json('statistics.globalStats') ?? [];

        return [
            'sent' => (int) ($stats['sent'] ?? 0),
            'delivered' => (int) ($stats['delivered'] ?? 0),
            'opened' => (int) ($stats['uniqueViews'] ?? 0),
            'clicked' => (int) ($stats['clickers'] ?? 0),
            'unsubscribed' => (int) ($stats['unsubscriptions'] ?? 0),
            'bounced' => (int) ($stats['hardBounces'] ?? 0) + (int) ($stats['softBounces'] ?? 0),
            'complaints' => (int) ($stats['complaints'] ?? 0),
        ];
    }

    public function sendCampaignNow(int $campaignId): void
    {
        $this->request('post', "/emailCampaigns/{$campaignId}/sendNow");
    }

    /**
     * @param  'get'|'post'  $method
     * @param  array<string, mixed>  $data  Query string en GET, cuerpo JSON en POST
     */
    private function request(string $method, string $path, array $data = []): Response
    {
        $apiKey = config('crm.brevo.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new BrevoException('Falta configurar BREVO_API_KEY.');
        }

        try {
            $pending = Http::withHeaders(['api-key' => $apiKey, 'accept' => 'application/json'])->timeout(15);
            $url = config('crm.brevo.base_url').$path;
            $response = $method === 'get' ? $pending->get($url, $data) : $pending->post($url, $data);
        } catch (ConnectionException $exception) {
            throw new BrevoException('No se pudo conectar con Brevo: '.$exception->getMessage(), retryable: true);
        }

        if ($response->successful()) {
            return $response;
        }

        $status = $response->status();

        throw new BrevoException(
            "Brevo respondió {$status}: ".($response->json('message') ?? $response->reason()),
            retryable: $status === 429 || $status >= 500,
        );
    }
}
