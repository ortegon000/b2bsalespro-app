<?php

use App\Domain\ObjecionCero\Enums\TipoObjecion;
use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Ficha;
use App\Domain\ObjecionCero\Models\PreguntaGrupo;
use App\Domain\ObjecionCero\Models\UsoItem;
use App\Domain\ObjecionCero\Models\WhatsappScript;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Inicio')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        return [
            ['k' => Ficha::count(), 'v' => 'fichas de objeción'],
            ['k' => Categoria::count(), 'v' => 'categorías'],
            ['k' => PreguntaGrupo::all()->sum(fn ($g) => count($g->items)), 'v' => 'preguntas listas'],
            ['k' => WhatsappScript::count(), 'v' => 'scripts de WhatsApp'],
        ];
    }

    #[Computed]
    public function uso()
    {
        return UsoItem::orderBy('orden')->get();
    }

    #[Computed]
    public function tipos(): array
    {
        return TipoObjecion::cases();
    }
}; ?>

<section style="animation:ocfade .4s ease both">
    <div style="padding:76px 0 40px;border-bottom:1px solid rgba(255,255,255,.07)">
        <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.26em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Manual de cierre · Ventas B2B</div>
        <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:78px;line-height:.9;letter-spacing:-.03em;color:#fff;margin:20px 0 0">Que ninguna<br>objeción vuelva<br>a tomarte<br><span style="color:oklch(0.72 0.14 78)">por sorpresa.</span></h1>
        <p style="font-size:17px;line-height:1.6;color:#aeb8c4;max-width:560px;margin:26px 0 0">30 objeciones, cada una con su respuesta escrita: qué significa, qué nunca decir, el diálogo palabra por palabra y el cierre. Consúltalo antes de cada reunión.</p>
        <div style="display:flex;gap:12px;margin-top:32px;flex-wrap:wrap">
            <a href="{{ route('objecion-cero.banco') }}" wire:navigate style="display:inline-flex;align-items:center;gap:9px;background:oklch(0.72 0.14 78);color:#1a1205;border:0;border-radius:9px;padding:14px 22px;font:600 14px 'IBM Plex Sans'">⌕ &nbsp;Buscar mi objeción</a>
            <a href="{{ route('objecion-cero.preguntas') }}" wire:navigate style="display:inline-flex;align-items:center;gap:9px;background:#161c26;color:#e7ebf0;border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:14px 22px;font:600 14px 'IBM Plex Sans'">? &nbsp;Ver las 120 preguntas</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.07);border-radius:12px;overflow:hidden;margin-top:40px">
        @foreach ($this->stats as $s)
            <div style="background:#0f1319;padding:24px 22px">
                <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:38px;color:#fff;line-height:1">{{ $s['k'] }}</div>
                <div style="font:500 11px 'IBM Plex Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:#6b7684;margin-top:8px">{{ $s['v'] }}</div>
            </div>
        @endforeach
    </div>

    <h2 style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:26px;color:#fff;margin:56px 0 20px;letter-spacing:-.01em">Cómo se usa</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        @foreach ($this->uso as $u)
            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:11px;padding:22px 24px">
                <div style="font:600 12px 'IBM Plex Mono',monospace;color:oklch(0.80 0.13 82);text-transform:uppercase;letter-spacing:.08em">{{ $u->titulo }}</div>
                <div style="font-size:14px;line-height:1.6;color:#aeb8c4;margin-top:9px">{{ $u->descripcion }}</div>
            </div>
        @endforeach
    </div>

    <h2 style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:26px;color:#fff;margin:56px 0 20px;letter-spacing:-.01em">Los tres tipos de objeción</h2>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
        @foreach ($this->tipos as $t)
            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-top:3px solid {{ $t->dotColor() }};border-radius:11px;padding:20px 22px">
                <div style="display:inline-flex;align-items:center;gap:8px;padding:5px 12px;border-radius:20px;background:{{ $t->bgColor() }};color:{{ $t->textColor() }};font:700 12px 'IBM Plex Sans';text-transform:uppercase;letter-spacing:.05em"><span style="width:8px;height:8px;border-radius:50%;background:{{ $t->dotColor() }};display:inline-block"></span>{{ $t->short() }}</div>
                <div style="font-size:13.5px;line-height:1.55;color:#aeb8c4;margin-top:13px">{{ $t->descripcion() }}</div>
                <div style="font-size:13px;line-height:1.5;color:#e7ebf0;margin-top:10px;font-weight:500">→ {{ $t->accion() }}</div>
            </div>
        @endforeach
    </div>
</section>
