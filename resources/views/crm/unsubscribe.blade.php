<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Darme de baja · B2B Sales Pro</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #020617; font-family: Arial, Helvetica, sans-serif; color: #1f2d3d; }
        main { width: 100%; max-width: 440px; margin: 16px; padding: 32px; background: #fff; border-radius: 12px; text-align: center; box-sizing: border-box; }
        h1 { margin: 0 0 12px; font-size: 22px; }
        p { margin: 0 0 20px; line-height: 1.5; color: #3b3f44; }
        button { padding: 12px 24px; border: 0; border-radius: 8px; background: #ff6567; color: #fff; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <main>
        @if ($done)
            <h1>Listo, ya no recibirás estos correos</h1>
            <p>Te dimos de baja del refuerzo de «{{ $course }}». Gracias por haber participado.</p>
        @else
            <h1>¿Quieres dejar de recibir estos correos?</h1>
            <p>Dejarás de recibir las actividades diarias de refuerzo de «{{ $course }}».</p>
            <form method="POST" action="{{ request()->fullUrl() }}">
                <button type="submit">Sí, darme de baja</button>
            </form>
        @endif
    </main>
</body>
</html>
