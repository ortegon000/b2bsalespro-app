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

];
