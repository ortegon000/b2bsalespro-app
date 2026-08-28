<?php

use App\Domain\ObjecionCero\Models\Frase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('100 frases')] class extends Component {
    public string $query = '';

    #[Computed]
    public function grupos()
    {
        $grupos = Frase::orderBy('sort_order')->get();

        if ($this->query === '') {
            return $grupos;
        }

        $needle = mb_strtolower($this->query);

        return $grupos
            ->map(function (Frase $g) use ($needle) {
                $matchesTitle = str_contains(mb_strtolower($g->title), $needle);

                $items = $matchesTitle
                    ? $g->items
                    : array_values(array_filter(
                        $g->items,
                        fn ($frase) => str_contains(mb_strtolower($frase), $needle)
                    ));

                if ($items === []) {
                    return null;
                }

                $g->items = $items;

                return $g;
            })
            ->filter()
            ->values();
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 5 · Frases de transición</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">100 frases para conectar y avanzar</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">Recibir, profundizar, reformular, cerrar. El pegamento entre objeción y respuesta.</p>

    <div style="position:relative;margin-bottom:24px">
        <span style="position:absolute;left:18px;top:50%;transform:translateY(-50%);font-size:18px;color:#5a6472">⌕</span>
        <input
            wire:model.live.debounce.300ms="query"
            placeholder="Busca una frase o un momento de la conversación…"
            style="width:100%;background:#141a24;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:16px 18px 16px 48px;color:#e7ebf0;font:400 15px 'IBM Plex Sans';outline:none"
        >
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        @forelse ($this->grupos as $g)
            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:20px 22px">
                <div style="font:600 13px 'Space Grotesk';color:#fff;margin-bottom:14px">{{ $g->title }}</div>
                @foreach ($g->items as $frase)
                    <div style="font-size:13px;line-height:1.5;color:#aeb8c4;padding:7px 0;border-top:1px solid rgba(255,255,255,.05)">{{ $frase }}</div>
                @endforeach
            </div>
        @empty
            <div style="grid-column:1 / -1;text-align:center;padding:60px 0;color:#6b7684;font-size:14px">Ninguna frase coincide. Prueba con otras palabras.</div>
        @endforelse
    </div>
</section>
