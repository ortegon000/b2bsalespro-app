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

        // La vista previa muestra las imágenes reales, no la imagen única de pruebas locales.
        config(['crm.email_images_override_url' => null]);

        $total = $sequence->steps()->count();
        $html = $emails->render($day, $emails->sampleData($total))['html'];

        // El correo real lleva las URL públicas del logo y las imágenes (APP_URL); en la vista previa
        // se usan las del sitio desde el que se mira, para que carguen también en local (.test por http).
        foreach (['crm.email_logo_url' => 'img/logo_white.png', 'crm.email_images_url' => 'img/actividades'] as $key => $path) {
            $url = (string) config($key);

            if (str_ends_with($url, '/'.$path)) {
                $html = str_replace($url, asset($path), $html);
            }
        }

        return response($html);
    }
}
