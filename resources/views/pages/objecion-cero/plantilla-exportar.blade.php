<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi plantilla personal · Objeción Cero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:light}
        *{box-sizing:border-box}
        body{margin:0;background:#eef0f3;color:#18202b;font-family:'IBM Plex Sans',sans-serif;line-height:1.5}
        .toolbar{position:sticky;top:0;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 24px;background:#0f1319;color:#fff}
        .toolbar-actions{display:flex;align-items:center;gap:10px}
        .toolbar a,.toolbar button{border-radius:8px;padding:9px 14px;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
        .toolbar a{border:1px solid rgba(255,255,255,.18);color:#e7ebf0}
        .toolbar button{background:#dcae50;border:1px solid #dcae50;color:#1a1205}
        .toolbar a:focus-visible,.toolbar button:focus-visible{outline:2px solid #dcae50;outline-offset:3px}
        .sheet{width:min(816px,calc(100% - 32px));margin:32px auto;background:#fff;padding:56px 64px;box-shadow:0 18px 50px rgba(15,19,25,.14)}
        .brand{font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#956c1b}
        h1{margin:8px 0 4px;font-size:32px;line-height:1.15}
        .owner{margin:0 0 36px;color:#657080;font-size:13px}
        .step{break-inside:avoid;margin-top:30px}
        .step h2{margin:0 0 14px;padding-bottom:9px;border-bottom:1px solid #dfe3e8;font-size:18px}
        .field{break-inside:avoid;margin-top:18px}
        .field h3{margin:0 0 5px;font-size:12px;line-height:1.4;color:#657080;text-transform:uppercase;letter-spacing:.04em}
        .field p{margin:0;white-space:pre-wrap;overflow-wrap:anywhere;font-size:14px}
        .empty{padding:30px 0;color:#657080}
        @page{size:A4;margin:18mm}
        @media print{
            body{background:#fff}
            .toolbar{display:none}
            .sheet{width:auto;margin:0;padding:0;box-shadow:none}
        }
        @media(max-width:640px){
            .toolbar{align-items:flex-start;padding:12px 16px}
            .toolbar-actions{flex-direction:column;align-items:stretch}
            .sheet{margin:16px auto;padding:34px 24px}
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Vista lista para exportar</span>
        <div class="toolbar-actions">
            <a href="{{ route('objecion-cero.plantilla') }}">Volver a editar</a>
            <button type="button" onclick="window.print()">Guardar como PDF</button>
        </div>
    </div>

    <main class="sheet">
        <div class="brand">Objeción Cero · Plantilla personal</div>
        <h1>Mi adaptador universal</h1>
        <p class="owner">Preparado por {{ $user->name }} · {{ now()->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p>

        @forelse ($sections as $section)
            <section class="step">
                <h2>Paso {{ $section['title'] }}</h2>

                @foreach ($section['fields'] as $field)
                    <div class="field">
                        <h3>{{ $field['label'] }}</h3>
                        <p>{{ $field['value'] }}</p>
                    </div>
                @endforeach
            </section>
        @empty
            <p class="empty">Tu plantilla todavía no tiene respuestas. Vuelve a editarla, llena al menos un campo y exporta de nuevo.</p>
        @endforelse
    </main>
</body>
</html>
