<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token de entrada de leads
    |--------------------------------------------------------------------------
    |
    | Token Bearer que deben enviar los formularios, landings y el sitio web al
    | endpoint POST /api/crm/leads. Si está vacío, el endpoint rechaza todo.
    |
    */

    'intake_token' => env('CRM_INTAKE_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Zona horaria del negocio
    |--------------------------------------------------------------------------
    |
    | Las fechas se guardan en UTC; el CRM las muestra y programa en esta zona.
    |
    */

    'timezone' => 'America/Mexico_City',

    /*
    |--------------------------------------------------------------------------
    | Hora de envío de la secuencia de refuerzo
    |--------------------------------------------------------------------------
    |
    | Hora local (en la zona horaria del negocio) a la que sale cada correo.
    |
    */

    'send_hour' => 12,

    /*
    |--------------------------------------------------------------------------
    | Brevo
    |--------------------------------------------------------------------------
    |
    | api_key: clave de la API de Brevo (envío transaccional por plantilla).
    | webhook_token: token Bearer que Brevo envía en su webhook de eventos
    | (rebotes, bajas, quejas) hacia POST /api/crm/brevo/webhook.
    |
    */

    'brevo' => [
        'base_url' => 'https://api.brevo.com/v3',
        'api_key' => env('BREVO_API_KEY'),
        'webhook_token' => env('CRM_BREVO_WEBHOOK_TOKEN'),
    ],

];
