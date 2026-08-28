<?php

use App\Domain\ObjecionCero\Models\Cierre;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Selector de cierres')] class extends Component {
    public string $query = '';

    #[Computed]
    public function cierres()
    {
        return Cierre::query()
            ->when($this->query, fn ($q) => $q->where(function ($q) {
                $q->where('objection', 'like', "%{$this->query}%")
                    ->orWhere('name', 'like', "%{$this->query}%")
                    ->orWhere('script', 'like', "%{$this->query}%");
            }))
            ->orderBy('sort_order')
            ->get();
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 6 · Selector de cierres</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">Elige el cierre correcto</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">Primero clasifica, luego responde, y solo entonces cierra. Cerrar antes de responder es cerrar en falso.</p>

    <div style="position:relative;margin-bottom:24px">
        <span style="position:absolute;left:18px;top:50%;transform:translateY(-50%);font-size:18px;color:#5a6472">⌕</span>
        <input
            wire:model.live.debounce.300ms="query"
            placeholder="Busca por objeción, nombre del cierre o script…"
            style="width:100%;background:#141a24;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:16px 18px 16px 48px;color:#e7ebf0;font:400 15px 'IBM Plex Sans';outline:none"
        >
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
        @forelse ($this->cierres as $c)
            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:20px 24px">
                <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:12px;margin-bottom:12px">
                    <span style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:17px;color:#fff">{{ $c->name }}</span>
                    <span style="font-size:12px;font-family:'IBM Plex Mono',monospace;color:#7a8494;padding:3px 9px;background:#0b0f16;border-radius:6px">{{ $c->objection }}</span>
                </div>
                <div style="font-size:14px;line-height:1.6;color:#dbe3ec;padding:13px 16px;background:oklch(0.72 0.14 78 / .09);border-left:3px solid oklch(0.72 0.14 78);border-radius:0 8px 8px 0;margin-bottom:14px">“{{ $c->script }}”</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:oklch(0.80 0.12 155);margin-bottom:6px">✓ Cuándo usarlo</div>
                        <div style="font-size:12.5px;line-height:1.5;color:#9aa4b2">{{ $c->usage }}</div>
                    </div>
                    <div>
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:oklch(0.72 0.15 30);margin-bottom:6px">✕ Cuándo NO usarlo</div>
                        <div style="font-size:12.5px;line-height:1.5;color:#9aa4b2">{{ $c->avoid }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:60px 0;color:#6b7684;font-size:14px">Ningún cierre coincide. Prueba con otras palabras.</div>
        @endforelse
    </div>
</section>
