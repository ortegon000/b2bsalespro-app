<?php

use App\Domain\ObjecionCero\Models\PreguntaGrupo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('120 preguntas')] class extends Component {
    #[Computed]
    public function grupos()
    {
        return PreguntaGrupo::orderBy('sort_order')->get();
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 4 · Banco de preguntas</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">120 preguntas listas para copiar</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">La pregunta correcta destapa la objeción real. Roba estas.</p>

    @foreach ($this->grupos as $g)
        <div style="margin-bottom:34px">
            <div style="display:inline-block;font:600 12px 'IBM Plex Mono',monospace;letter-spacing:.1em;text-transform:uppercase;color:oklch(0.80 0.13 82);padding:6px 12px;background:oklch(0.72 0.14 78 / .1);border-radius:7px;margin-bottom:16px">{{ $g->title }}</div>
            <div style="columns:2;column-gap:32px">
                @foreach ($g->items as $i => $q)
                    <div style="break-inside:avoid;display:flex;gap:11px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05)">
                        <span style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#4a5567;flex:none;padding-top:2px">{{ $i + 1 }}</span>
                        <span style="font-size:13.5px;line-height:1.5;color:#c3ccd6">{{ $q }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</section>
