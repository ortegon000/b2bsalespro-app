{{--
    Imagen de una actividad, centrada y con esquinas redondeadas (como en el correo original).
    Se usa dentro de `contenido`:

        @include('emails.crm.reinforcement.imagen', ['archivo' => 'dia-01.jpg', 'alt' => 'Descripción de la imagen'])

    El archivo vive en public/img/actividades/ y se sirve desde config('crm.email_images_url');
    solo en local, config('crm.email_images_override_url') lo reemplaza por una URL pública.
--}}
<p style="margin: 0 0 16px 0; text-align: center; font-size: 0; line-height: 0;">
    <img src="{{ config('crm.email_images_override_url') ?: rtrim(config('crm.email_images_url'), '/').'/'.$archivo }}" width="570" alt="{{ $alt }}" style="display: block; width: 100%; max-width: 570px; height: auto; margin: 0 auto; border-radius: 12px;">
</p>
