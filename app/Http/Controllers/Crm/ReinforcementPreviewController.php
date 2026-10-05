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
        $html = $emails->render($day, $emails->sampleData($total))['html'];

        // El correo real lleva la URL pública del logo (APP_URL); en la vista previa se usa la del
        // sitio desde el que se mira, para que cargue también en local (.test por http).
        $logo = (string) config('crm.email_logo_url');

        if (str_ends_with($logo, '/img/logo_white.png')) {
            $html = str_replace($logo, asset('img/logo_white.png'), $html);
        }

        return response($html);
    }
}
