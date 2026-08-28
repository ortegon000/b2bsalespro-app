<?php

use App\Domain\ObjecionCero\Models\WhatsappScript;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Scripts de WhatsApp')] class extends Component {
    public string $query = '';

    #[Computed]
    public function scripts()
    {
        $scripts = WhatsappScript::orderBy('sort_order')->get();

        if ($this->query === '') {
            return $scripts;
        }

        $needle = mb_strtolower($this->query);

        return $scripts
            ->filter(function (WhatsappScript $w) use ($needle) {
                if (str_contains(mb_strtolower($w->title), $needle)) {
                    return true;
                }

                foreach ($w->messages as $m) {
                    if (str_contains(mb_strtolower($m['t']), $needle)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    #[Computed]
    public function total(): int
    {
        return WhatsappScript::count();
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 7 · Scripts de WhatsApp</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">{{ $this->total }} conversaciones listas para copiar</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">Sustituye los [corchetes] por tus datos. Mantén uno o dos mensajes por burbuja.</p>

    <div style="position:relative;margin-bottom:24px">
        <span style="position:absolute;left:18px;top:50%;transform:translateY(-50%);font-size:18px;color:#5a6472">⌕</span>
        <input
            wire:model.live.debounce.300ms="query"
            placeholder="Busca por tema o por texto dentro de la conversación…"
            style="width:100%;background:#141a24;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:16px 18px 16px 48px;color:#e7ebf0;font:400 15px 'IBM Plex Sans';outline:none"
        >
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">
        @forelse ($this->scripts as $i => $w)
            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden">
                <div style="padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.07);background:#0b0f16">
                    <span style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:oklch(0.80 0.13 82);letter-spacing:.06em">CONVERSACIÓN {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div style="font-size:13.5px;font-weight:600;color:#fff;margin-top:4px;line-height:1.3">{{ $w->title }}</div>
                </div>
                <div style="padding:16px 16px;display:flex;flex-direction:column;gap:7px;background:#10151d">
                    @foreach ($w->messages as $m)
                        @php $you = $m['who'] === 't'; @endphp
                        <div style="display:flex;justify-content:{{ $you ? 'flex-end' : 'flex-start' }}">
                            <div style="max-width:82%;padding:9px 13px;font-size:12.5px;line-height:1.5;border-radius:{{ $you ? '13px 13px 4px 13px' : '13px 13px 13px 4px' }};background:{{ $you ? 'oklch(0.55 0.11 155 / .15)' : '#1c2431' }};color:{{ $you ? '#d3e9dd' : '#b8c2ce' }};border:1px solid {{ $you ? 'oklch(0.55 0.11 155 / .22)' : 'rgba(255,255,255,.06)' }}">{{ $m['t'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div style="grid-column:1 / -1;text-align:center;padding:60px 0;color:#6b7684;font-size:14px">Ningún script coincide. Prueba con otras palabras.</div>
        @endforelse
    </div>
</section>
