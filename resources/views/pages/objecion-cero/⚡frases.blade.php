<?php

use App\Domain\ObjecionCero\Models\Frase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('100 frases')] class extends Component {
    #[Computed]
    public function grupos()
    {
        return Frase::orderBy('orden')->get();
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 5 · Frases de transición</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">100 frases para conectar y avanzar</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">Recibir, profundizar, reformular, cerrar. El pegamento entre objeción y respuesta.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        @foreach ($this->grupos as $g)
            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:20px 22px">
                <div style="font:600 13px 'Space Grotesk';color:#fff;margin-bottom:14px">{{ $g->titulo }}</div>
                @foreach ($g->items as $frase)
                    <div style="font-size:13px;line-height:1.5;color:#aeb8c4;padding:7px 0;border-top:1px solid rgba(255,255,255,.05)">{{ $frase }}</div>
                @endforeach
            </div>
        @endforeach
    </div>
</section>
