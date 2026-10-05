<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Models\Send;
use Illuminate\Database\Eloquent\Builder;

/**
 * Métricas de entrega de los correos de la secuencia, calculadas desde `crm_sends`.
 *
 * Las tasas se calculan sobre los correos enviados. Las aperturas pueden estar infladas por la
 * protección de privacidad de Apple Mail, que abre los correos por su cuenta.
 */
class SendMetrics
{
    /**
     * @param  Builder<Send>  $sends
     * @return array{sent: int, delivered: int, opened: int, clicked: int, failed: int}
     */
    public function summary(Builder $sends): array
    {
        $row = $sends->toBase()
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as sent', [SendStatus::Sent->value])
            ->selectRaw('sum(case when delivered_at is not null then 1 else 0 end) as delivered')
            ->selectRaw('sum(case when opened_at is not null then 1 else 0 end) as opened')
            ->selectRaw('sum(case when clicked_at is not null then 1 else 0 end) as clicked')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as failed', [SendStatus::Failed->value])
            ->first();

        return [
            'sent' => (int) ($row->sent ?? 0),
            'delivered' => (int) ($row->delivered ?? 0),
            'opened' => (int) ($row->opened ?? 0),
            'clicked' => (int) ($row->clicked ?? 0),
            'failed' => (int) ($row->failed ?? 0),
        ];
    }

    /**
     * Porcentaje entero de $part sobre $total, o null si no hay base para calcularlo.
     */
    public function rate(int $part, int $total): ?int
    {
        return $total > 0 ? (int) round($part / $total * 100) : null;
    }
}
