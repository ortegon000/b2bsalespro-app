<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Services\ReinforcementEmail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ReinforcementPreviewController extends Controller
{
    /**
     * Muestra el correo de un día de la secuencia con datos de ejemplo.
     */
    public function __invoke(Sequence $sequence, int $day, ReinforcementEmail $emails): Response
    {
        abort_unless($sequence->steps()->where('day', $day)->exists() && $emails->exists($day), 404);

        $total = $sequence->steps()->count();

        return response($emails->render($day, $emails->sampleData($total))['html']);
    }
}
