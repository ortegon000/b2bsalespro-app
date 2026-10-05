{{--
    Armazón común de los correos de la secuencia de refuerzo (diseño tomado del correo del día 1 de Brevo).

    Cada día extiende este layout y define las secciones:
      - asunto     (obligatoria) asunto del correo
      - titulo     título grande y centrado del cuerpo
      - preheader  (opcional) texto de vista previa en la bandeja
      - contenido  cuerpo del correo (usa <p>, <h3>, <ol>, <strong>… sin estilos en línea).
                   Una imagen se agrega con @include('emails.crm.reinforcement.imagen', ['archivo' => 'dia-NN.jpg', 'alt' => '…']).

    Variables disponibles: $nombre, $nombreCompleto, $empresa, $curso, $dia, $total, $siguiente, $bajaUrl.
--}}
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('asunto')</title>
    <!--[if mso]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <style type="text/css">
        body { width: 100% !important; margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; }
        .contenido p, .contenido ol, .contenido ul, .contenido h3 { margin: 0 0 16px 0; }
        .contenido h3 { color: #1f2d3d; font-family: arial, helvetica, sans-serif; font-size: 24px; font-weight: 400; line-height: 1.3; }
        .contenido ol, .contenido ul { padding-left: 24px; }
        .contenido li { margin: 0 0 6px 0; }
        .contenido a { color: #0092ff; text-decoration: underline; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: inherit !important; }
        @media (max-width: 600px) {
            .contenedor { width: 100% !important; }
            .padding { padding-left: 15px !important; padding-right: 15px !important; }
        }
    </style>
</head>
<body bgcolor="#ffffff" style="background-color: #ffffff; margin: 0; padding: 0;">
    @hasSection('preheader')
        <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: #ffffff;">@yield('preheader')</div>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; width: 100%;">
        <tr>
            <td>
                {{-- Banda superior con el logo --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
                    <tr>
                        <td bgcolor="#020617" align="center" style="background-color: #020617; padding: 15px 0;">
                            <img src="{{ config('crm.email_logo_url') }}" width="160" alt="B2B Sales Pro" style="display: block; width: 160px; height: auto;">
                        </td>
                    </tr>
                </table>

                {{-- Contenedor del mensaje --}}
                <table role="presentation" class="contenedor" width="600" align="center" cellspacing="0" cellpadding="0" border="0" style="width: 600px; max-width: 100%;">
                    <tr>
                        <td class="padding" style="padding: 35px 15px 10px 15px; text-align: center; font-family: arial, helvetica, sans-serif;">
                            <span style="font-size: 28px; font-weight: bold; color: rgb(255, 101, 103); line-height: 1.3;">@yield('titulo')</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="padding contenido" style="padding: 15px 15px 20px 15px; font-family: arial, helvetica, sans-serif; font-size: 16px; line-height: 1.5; color: #3b3f44; word-break: break-word;">
                            @yield('contenido')
                        </td>
                    </tr>
                    <tr>
                        <td class="padding" style="padding: 20px 15px 35px 15px; border-top: 1px solid #e5e7eb; font-family: arial, helvetica, sans-serif; font-size: 12px; line-height: 1.5; color: #6b7280; text-align: center;">
                            Recibes este correo porque participaste en un curso de B2B Sales Pro.<br>
                            Si ya no quieres recibirlo, puedes <a href="{{ $bajaUrl }}" style="color: #6b7280; text-decoration: underline;">darte de baja aquí</a>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
