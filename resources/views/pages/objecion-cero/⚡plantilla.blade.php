<?php

use App\Domain\ObjecionCero\Models\PlantillaPaso;
use App\Domain\ObjecionCero\Models\PlantillaRespuesta;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Plantilla personal')] class extends Component {
    public array $valores = [];

    public function mount(): void
    {
        foreach (PlantillaRespuesta::where('user_id', auth()->id())->get() as $r) {
            $this->valores[$r->template_step_id][$r->field_index] = $r->value;
        }
    }

    #[Computed]
    public function pasos()
    {
        return PlantillaPaso::orderBy('sort_order')->get();
    }

    public function guardar(int $pasoId, int $campoIndex): void
    {
        PlantillaRespuesta::updateOrCreate(
            ['user_id' => auth()->id(), 'template_step_id' => $pasoId, 'field_index' => $campoIndex],
            ['value' => $this->valores[$pasoId][$campoIndex] ?? ''],
        );
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 11 · Plantilla personal</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">Tu adaptador universal</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">Llénalo una vez y todos los scripts hablan tu idioma. Se guarda automáticamente en tu cuenta.</p>

    @foreach ($this->pasos as $p)
        <div style="margin-bottom:26px">
            <div style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:18px;color:oklch(0.80 0.13 82);margin-bottom:14px">Paso {{ $p->title }}</div>
            <div style="display:flex;flex-direction:column;gap:14px">
                @foreach ($p->fields as $fi => $campo)
                    <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:11px;padding:16px 18px">
                        <label style="display:block;font-size:13px;font-weight:600;color:#e7ebf0;margin-bottom:9px">{{ $campo['label'] }}</label>
                        <textarea
                            wire:model="valores.{{ $p->id }}.{{ $fi }}"
                            wire:blur="guardar({{ $p->id }}, {{ $fi }})"
                            rows="2"
                            placeholder="Escribe aquí…"
                            style="width:100%;background:#0b0f16;border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:11px 13px;color:#e7ebf0;font:400 13.5px 'IBM Plex Sans';outline:none;resize:vertical;line-height:1.5"
                        >{{ $valores[$p->id][$fi] ?? '' }}</textarea>
                        @if (!empty($campo['ej']))
                            <div style="font-size:12px;color:oklch(0.72 0.09 155);margin-top:8px;font-style:italic">Ej: {{ $campo['ej'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div style="padding:18px 22px;background:oklch(0.72 0.14 78 / .08);border:1px solid oklch(0.72 0.14 78 / .2);border-radius:12px">
        <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:oklch(0.80 0.13 82);margin-bottom:7px">Tu prueba de fuego</div>
        <div style="font-size:14px;line-height:1.6;color:#c3ccd6">Toma la Ficha 1 y reescríbela sustituyendo cada [corchete] con tus respuestas. Léela en voz alta. Si suena a ti hablando —no a un guion—, la plantilla está bien llenada. Repite con una ficha por día: en un mes, todo el manual habla tu idioma.</div>
    </div>
</section>
