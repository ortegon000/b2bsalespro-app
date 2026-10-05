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
    | sender_name / sender_email: remitente de los correos de la secuencia (debe estar
    | verificado en Brevo).
    | newsletter_list: nombre de la lista de Brevo a la que se suman quienes tomaron un curso.
    |
    */

    'brevo' => [
        'base_url' => 'https://api.brevo.com/v3',
        'api_key' => env('BREVO_API_KEY'),
        'webhook_token' => env('CRM_BREVO_WEBHOOK_TOKEN'),
        'sender_name' => env('CRM_BREVO_SENDER_NAME', 'B2B Sales Pro'),
        'sender_email' => env('CRM_BREVO_SENDER_EMAIL', 'cursos@b2bsalespro.mx'),
        'newsletter_list' => env('CRM_BREVO_NEWSLETTER_LIST', 'newsletter'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo de los correos
    |--------------------------------------------------------------------------
    |
    | URL pública del logo (versión blanca, para la banda oscura) de los correos de la
    | secuencia. Por defecto es public/img/logo_white.png servido desde APP_URL, así que
    | solo carga en los clientes de correo cuando el CRM está en un dominio público. En
    | local (.test) se puede apuntar a otra URL con CRM_EMAIL_LOGO_URL para ver el logo
    | en las pruebas.
    |
    */

    'email_logo_url' => env('CRM_EMAIL_LOGO_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/img/logo_white.png'),

    /*
    |--------------------------------------------------------------------------
    | Imágenes de las actividades
    |--------------------------------------------------------------------------
    |
    | URL base de las imágenes de los correos de la secuencia (public/img/actividades,
    | una por día: dia-01.jpg … dia-30.jpg). Igual que el logo, solo cargan en los
    | clientes de correo si el CRM está en un dominio público; en local se puede
    | apuntar a otra URL con CRM_EMAIL_IMAGES_URL.
    |
    */

    'email_images_url' => env('CRM_EMAIL_IMAGES_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/img/actividades'),

];
