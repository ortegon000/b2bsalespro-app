<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ filled($title ?? null) ? $title.' · Objeción Cero' : 'Objeción Cero' }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:#0f1319}
        ::selection{background:oklch(0.72 0.14 78 / .3);color:#fff}
        a{color:oklch(0.80 0.13 82);text-decoration:none}
        a:hover{color:oklch(0.88 0.12 85)}
        ::-webkit-scrollbar{width:10px;height:10px}
        ::-webkit-scrollbar-thumb{background:#232c3a;border-radius:8px;border:2px solid #0f1319}
        ::-webkit-scrollbar-track{background:transparent}
        input::placeholder,textarea::placeholder{color:#5a6472}
        button{font-family:inherit}
        @keyframes ocfade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
        @keyframes ocdrawer{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:none}}
        @keyframes ocback{from{opacity:0}to{opacity:1}}
        [x-cloak]{display:none!important}
        .oc-topbar{display:none}
        @media (max-width:860px){
            .oc-shell{flex-direction:column}
            .oc-topbar{display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:30;padding:14px 18px;background:#0b0f16;border-bottom:1px solid rgba(255,255,255,.07)}
            .oc-sidebar{position:fixed!important;top:0;left:0;z-index:41;width:82vw!important;max-width:320px;transform:translateX(-100%);transition:transform .22s ease}
            .oc-sidebar.oc-sidebar-open{transform:translateX(0)}
            .oc-main{height:auto!important}
            .oc-main-inner{padding:0 18px 60px!important}
        }
        @media (max-width:520px){
            .oc-main-inner{padding:0 16px 50px!important}
        }
    </style>

    @livewireStyles
</head>
<body style="margin:0">

<div x-data="{ navOpen: false }" class="oc-shell" style="display:flex;min-height:100vh;background:#0f1319;color:#e7ebf0;font-family:'IBM Plex Sans',system-ui,sans-serif;-webkit-font-smoothing:antialiased">

    {{-- MOBILE TOPBAR --}}
    <div class="oc-topbar">
        <span style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:17px;color:#fff">OBJECIÓN <span style="color:oklch(0.72 0.14 78)">CERO</span></span>
        <button type="button" @click="navOpen = !navOpen" aria-label="Abrir menú" style="background:transparent;border:1px solid rgba(255,255,255,.14);border-radius:8px;color:#e7ebf0;width:38px;height:38px;font-size:18px;cursor:pointer">☰</button>
    </div>

    {{-- MOBILE BACKDROP --}}
    <div x-cloak x-show="navOpen" @click="navOpen = false" style="position:fixed;inset:0;background:rgba(6,9,14,.6);z-index:40"></div>

    {{-- SIDEBAR --}}
    <aside class="oc-sidebar" :class="{ 'oc-sidebar-open': navOpen }" style="width:266px;flex:none;position:sticky;top:0;height:100vh;overflow-y:auto;background:#0b0f16;border-right:1px solid rgba(255,255,255,.07);padding:26px 16px 22px;display:flex;flex-direction:column">
        <a href="{{ route('objecion-cero.inicio') }}" wire:navigate @click="navOpen = false" style="display:flex;flex-direction:column;gap:2px;padding:4px 8px 0">
            <span style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.24em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Manual de cierre B2B</span>
            <span style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:22px;line-height:1.05;letter-spacing:-.02em;color:#fff;margin-top:4px">OBJECIÓN<br><span style="color:oklch(0.72 0.14 78)">CERO</span></span>
        </a>

        <div style="margin-top:22px;display:flex;flex-direction:column;gap:3px;flex:1">
            @php
                $navGroups = [
                    ['label' => null, 'items' => [
                        ['route' => 'objecion-cero.inicio', 'label' => 'Inicio', 'icon' => '◆'],
                    ]],
                    ['label' => 'El método', 'items' => [
                        ['route' => 'objecion-cero.banco', 'label' => 'Banco de fichas', 'icon' => '▤'],
                    ]],
                    ['label' => 'Recursos de conversación', 'items' => [
                        ['route' => 'objecion-cero.preguntas', 'label' => '120 preguntas', 'icon' => '?'],
                        ['route' => 'objecion-cero.frases', 'label' => '100 frases', 'icon' => '❞'],
                        ['route' => 'objecion-cero.cierres', 'label' => 'Selector de cierres', 'icon' => '⇥'],
                        ['route' => 'objecion-cero.whatsapp', 'label' => 'Scripts de WhatsApp', 'icon' => '◗'],
                    ]],
                    ['label' => 'Ejecución', 'items' => [
                        ['route' => 'objecion-cero.checklists', 'label' => 'Checklists', 'icon' => '☑'],
                        ['route' => 'objecion-cero.plantilla', 'label' => 'Plantilla personal', 'icon' => '✎'],
                    ]],
                    ['label' => null, 'items' => [
                        ['route' => 'objecion-cero.como-usar', 'label' => 'Cómo usar · Términos', 'icon' => 'ⓘ'],
                    ]],
                ];
            @endphp

            @foreach ($navGroups as $g)
                @if ($g['label'])
                    <div style="font:600 9.5px 'IBM Plex Mono',monospace;letter-spacing:.16em;text-transform:uppercase;color:#495467;padding:15px 10px 6px">{{ $g['label'] }}</div>
                @endif
                @foreach ($g['items'] as $it)
                    @php $active = request()->routeIs($it['route']); @endphp
                    <a
                        href="{{ route($it['route']) }}"
                        wire:navigate
                        @click="navOpen = false"
                        style="display:flex;align-items:center;gap:11px;padding:9px 11px;border-radius:8px;cursor:pointer;font-family:'IBM Plex Sans',sans-serif;font-size:13.5px;font-weight:500;width:100%;text-align:left;transition:all .14s;background:{{ $active ? '#1a2231' : 'transparent' }};color:{{ $active ? '#ffffff' : '#8b95a3' }};border:1px solid {{ $active ? 'rgba(255,255,255,.08)' : 'transparent' }}"
                    >
                        <span style="font-family:'IBM Plex Mono',monospace;font-size:13px;width:16px;text-align:center;flex:none;color:{{ $active ? 'oklch(0.72 0.14 78)' : '#556072' }}">{{ $it['icon'] }}</span>
                        <span>{{ $it['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </div>

        <div style="margin-top:18px;padding:13px 13px;background:oklch(0.72 0.14 78 / .08);border:1px solid oklch(0.72 0.14 78 / .2);border-radius:9px">
            <div style="font:600 9.5px 'IBM Plex Mono',monospace;letter-spacing:.12em;text-transform:uppercase;color:oklch(0.80 0.13 82);margin-bottom:6px">Regla de oro</div>
            <div style="font-size:12px;line-height:1.5;color:#aeb8c4">Nunca improvises una respuesta que ya existe escrita aquí.</div>
        </div>

        <div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 11px">
            <span style="font-size:12px;color:#6b7684;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:transparent;border:0;color:#6b7684;font-size:11px;font-family:'IBM Plex Mono',monospace;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;padding:0">Salir</button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="oc-main" style="flex:1;height:100vh;overflow-y:auto">
        <div class="oc-main-inner" style="max-width:1120px;margin:0 auto;padding:0 48px 90px">
            {{ $slot }}
        </div>
    </main>

</div>

<livewire:objecion-cero-feedback />

@livewireScripts
</body>
</html>
