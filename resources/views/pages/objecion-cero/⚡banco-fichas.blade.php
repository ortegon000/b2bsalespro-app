<?php

use App\Domain\ObjecionCero\Enums\TipoObjecion;
use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Ficha;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Banco de fichas')] class extends Component {
    public string $query = '';

    public ?string $catFilter = null;

    public ?string $tipoFilter = null;

    public ?int $fichaId = null;

    #[Computed]
    public function categorias()
    {
        return Categoria::withCount('fichas')->orderBy('label')->get();
    }

    #[Computed]
    public function tipos(): array
    {
        return TipoObjecion::cases();
    }

    #[Computed]
    public function fichas()
    {
        return Ficha::query()
            ->with('categoria')
            ->when($this->catFilter, fn ($q) => $q->where('categoria_id', $this->catFilter))
            ->when($this->tipoFilter, fn ($q) => $q->where('tipo', $this->tipoFilter))
            ->when($this->query, fn ($q) => $q->where(function ($q) {
                $q->where('objecion', 'like', "%{$this->query}%")
                    ->orWhere('significa', 'like', "%{$this->query}%")
                    ->orWhere('consejo', 'like', "%{$this->query}%");
            }))
            ->orderBy('numero')
            ->get();
    }

    #[Computed]
    public function ficha(): ?Ficha
    {
        return $this->fichaId ? Ficha::with('categoria')->find($this->fichaId) : null;
    }

    public function chipStyle(TipoObjecion $tipo): string
    {
        return "display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10.5px;font-weight:700;font-family:'IBM Plex Sans',sans-serif;text-transform:uppercase;letter-spacing:.05em;background:{$tipo->bgColor()};color:{$tipo->textColor()};white-space:nowrap;border:none";
    }

    public function dotStyle(TipoObjecion $tipo): string
    {
        return "width:7px;height:7px;border-radius:50%;background:{$tipo->dotColor()};display:inline-block;flex:none";
    }

    public function toggleCat(string $id): void
    {
        $this->catFilter = $this->catFilter === $id ? null : $id;
    }

    public function toggleTipo(string $id): void
    {
        $this->tipoFilter = $this->tipoFilter === $id ? null : $id;
    }

    public function open(int $numero): void
    {
        $this->fichaId = Ficha::where('numero', $numero)->value('id');
    }

    public function close(): void
    {
        $this->fichaId = null;
    }
}; ?>

<div>
<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Sección 1 · Banco de fichas</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">Las {{ Ficha::count() }} fichas de objeción</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 26px">Busca por lo que te dijo el cliente o filtra por categoría y tipo. Toca cualquier ficha para ver el diálogo, la pregunta estratégica y el cierre.</p>

    <div style="position:relative;margin-bottom:20px">
        <span style="position:absolute;left:18px;top:50%;transform:translateY(-50%);font-size:18px;color:#5a6472">⌕</span>
        <input
            wire:model.live.debounce.300ms="query"
            placeholder="Escribe lo que te dijo el cliente… «está caro», «lo pienso», «ya tengo proveedor»"
            style="width:100%;background:#141a24;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:16px 18px 16px 48px;color:#e7ebf0;font:400 15px 'IBM Plex Sans';outline:none"
        >
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        @foreach ($this->categorias as $cat)
            @php $active = $catFilter === (string) $cat->id; @endphp
            <button
                type="button"
                wire:click="toggleCat('{{ $cat->id }}')"
                style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:9px;cursor:pointer;font-family:'IBM Plex Sans',sans-serif;font-size:12.5px;font-weight:600;transition:all .14s;background:{{ $active ? 'oklch(0.72 0.14 78)' : '#161c26' }};color:{{ $active ? '#1a1205' : '#c3ccd6' }};border:1px solid {{ $active ? 'oklch(0.72 0.14 78)' : 'rgba(255,255,255,.08)' }}"
            ><span style="opacity:.7">{{ $cat->icon }}</span> {{ $cat->label }} <span style="opacity:.55;font-family:'IBM Plex Mono',monospace">{{ $cat->fichas_count }}</span></button>
        @endforeach
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px">
        @foreach ($this->tipos as $t)
            @php $active = $tipoFilter === $t->value; @endphp
            <button
                type="button"
                wire:click="toggleTipo('{{ $t->value }}')"
                style="display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border-radius:9px;cursor:pointer;font-family:'IBM Plex Sans',sans-serif;font-size:12.5px;font-weight:600;transition:all .14s;background:{{ $active ? $t->bgColor() : '#161c26' }};color:{{ $active ? $t->textColor() : '#c3ccd6' }};border:1px solid {{ $active ? $t->dotColor() : 'rgba(255,255,255,.08)' }}"
            ><span style="width:8px;height:8px;border-radius:50%;background:{{ $t->dotColor() }};display:inline-block"></span> {{ $t->short() }}</button>
        @endforeach
    </div>

    <div style="font:500 12px 'IBM Plex Mono',monospace;color:#6b7684;margin-bottom:14px">{{ $this->fichas->count() }} objeciones</div>

    <div style="display:flex;flex-direction:column;gap:10px">
        @forelse ($this->fichas as $f)
            <button
                type="button"
                wire:click="open({{ $f->numero }})"
                style="display:flex;align-items:center;gap:18px;width:100%;text-align:left;background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:11px;padding:16px 20px;cursor:pointer"
            >
                <span style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:16px;color:#4a5567;flex:none;width:26px">{{ str_pad($f->numero, 2, '0', STR_PAD_LEFT) }}</span>
                <span style="flex:1;min-width:0">
                    <span style="display:block;font-size:16px;font-weight:600;color:#fff">{{ $f->objecion }}</span>
                    <span style="display:block;font-size:12.5px;color:#8b95a3;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $f->significa }}</span>
                </span>
                <span style="{{ $this->chipStyle($f->tipo) }}"><span style="{{ $this->dotStyle($f->tipo) }}"></span>{{ $f->tipo->short() }}</span>
                <span style="color:#4a5567;font-size:18px;flex:none">→</span>
            </button>
        @empty
            <div style="text-align:center;padding:60px 0;color:#6b7684;font-size:14px">Ninguna objeción coincide. Prueba con otras palabras o quita los filtros.</div>
        @endforelse
    </div>
</section>

@if ($this->ficha)
    @teleport('body')
      <div>
        <div wire:click="close" style="position:fixed;inset:0;background:rgba(6,9,14,.7);backdrop-filter:blur(3px);z-index:50;animation:ocback .2s ease both"></div>
        <div style="position:fixed;top:0;right:0;height:100vh;width:680px;max-width:94vw;background:#0f1319;border-left:1px solid rgba(255,255,255,.1);z-index:51;overflow-y:auto;animation:ocdrawer .28s cubic-bezier(.2,.8,.2,1) both;box-shadow:-30px 0 80px rgba(0,0,0,.5)">
            <div style="position:sticky;top:0;background:{{ $this->ficha->tipo->headGradient() }};border-bottom:1px solid rgba(255,255,255,.08);padding:24px 30px;z-index:2">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
                    <div>
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.16em;color:oklch(0.80 0.13 82)">FICHA {{ str_pad($this->ficha->numero, 2, '0', STR_PAD_LEFT) }} · {{ strtoupper($this->ficha->categoria->label) }}</div>
                        <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:30px;letter-spacing:-.02em;color:#fff;margin-top:8px;line-height:1.05">“{{ $this->ficha->objecion }}”</div>
                    </div>
                    <button type="button" wire:click="close" style="flex:none;width:36px;height:36px;border-radius:9px;background:#161c26;border:1px solid rgba(255,255,255,.1);color:#98a2b0;font-size:18px;cursor:pointer">✕</button>
                </div>
                <div style="margin-top:14px"><span style="{{ $this->chipStyle($this->ficha->tipo) }}"><span style="{{ $this->dotStyle($this->ficha->tipo) }}"></span>{{ $this->ficha->tipo->label() }}</span></div>
            </div>

            <div style="padding:26px 30px 60px">
                <div style="font:600 10.5px 'IBM Plex Mono',monospace;letter-spacing:.14em;text-transform:uppercase;color:#6b7684">Lo que realmente significa</div>
                <div style="font-size:15px;line-height:1.65;color:#c3ccd6;margin:9px 0 26px">{{ $this->ficha->significa }}</div>

                @if (!empty($this->ficha->peor))
                    <div style="border-left:3px solid oklch(0.58 0.14 28);background:oklch(0.58 0.14 28 / .08);border-radius:0 9px 9px 0;padding:15px 18px;margin-bottom:14px">
                        <div style="font:700 11px 'IBM Plex Mono',monospace;letter-spacing:.08em;color:oklch(0.72 0.15 30);text-transform:uppercase;margin-bottom:10px">✕ Lo peor que puedes responder</div>
                        @foreach ($this->ficha->peor as $p)
                            <div style="font-size:13.5px;line-height:1.55;color:#c9a9a9;padding:4px 0">{{ $p }}</div>
                        @endforeach
                    </div>
                @endif

                @if (!empty($this->ficha->dialogo))
                    <div style="border-left:3px solid oklch(0.55 0.11 155);background:oklch(0.55 0.11 155 / .08);border-radius:0 9px 9px 0;padding:17px 20px;margin-bottom:24px">
                        <div style="font:700 11px 'IBM Plex Mono',monospace;letter-spacing:.08em;color:oklch(0.78 0.12 155);text-transform:uppercase;margin-bottom:14px">✓ Respuesta recomendada</div>
                        @foreach ($this->ficha->dialogo as $m)
                            @php $you = $m['who'] === 't'; @endphp
                            <div style="display:flex;gap:12px;margin-bottom:12px">
                                <span style="flex:none;width:56px;font-family:'IBM Plex Mono',monospace;font-size:11px;font-weight:600;padding-top:2px;color:{{ $you ? 'oklch(0.80 0.12 155)' : '#6b7684' }}">{{ $you ? 'Tú' : 'Cliente' }}</span>
                                <span style="font-size:14px;line-height:1.6;color:{{ $you ? '#e7ebf0' : '#b8c2ce' }}">{{ $m['t'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($this->ficha->pregunta)
                    <div style="background:oklch(0.72 0.14 78 / .1);border:1px solid oklch(0.72 0.14 78 / .22);border-radius:11px;padding:18px 20px;margin-bottom:24px">
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.14em;color:oklch(0.80 0.13 82);text-transform:uppercase">▸ Pregunta estratégica</div>
                        <div style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:20px;color:#fff;margin-top:8px;line-height:1.3">“{{ $this->ficha->pregunta }}”</div>
                    </div>
                @endif

                @if (!empty($this->ficha->ramas))
                    <div style="font:600 10.5px 'IBM Plex Mono',monospace;letter-spacing:.14em;text-transform:uppercase;color:#6b7684;margin-bottom:12px">Según cómo responda</div>
                    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:26px">
                        @foreach ($this->ficha->ramas as $r)
                            <div style="background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:15px 18px">
                                <div style="font-size:13px;font-weight:600;color:oklch(0.80 0.13 82);margin-bottom:8px">“{{ $r['r'] }}”</div>
                                <div style="font-size:13.5px;line-height:1.6;color:#c3ccd6">{{ $r['t'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($this->ficha->cierre)
                    <div style="background:#141a24;border:1px dashed rgba(255,255,255,.16);border-radius:11px;padding:18px 20px;margin-bottom:20px">
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.12em;color:#7a8494;text-transform:uppercase;margin-bottom:8px">Cierre recomendado</div>
                        <div style="font-size:15px;line-height:1.6;color:#e7ebf0">“{{ $this->ficha->cierre }}”</div>
                    </div>
                @endif

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="background:oklch(0.58 0.14 28 / .07);border-radius:10px;padding:15px 18px">
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.1em;color:oklch(0.72 0.15 30);text-transform:uppercase;margin-bottom:7px">Error más común</div>
                        <div style="font-size:13px;line-height:1.55;color:#b0a0a0">{{ $this->ficha->error }}</div>
                    </div>
                    <div style="background:oklch(0.55 0.11 155 / .07);border-radius:10px;padding:15px 18px">
                        <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.1em;color:oklch(0.78 0.12 155);text-transform:uppercase;margin-bottom:7px">Consejo rápido</div>
                        <div style="font-size:13px;line-height:1.55;color:#a0b0a6">{{ $this->ficha->consejo }}</div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    @endteleport
@endif
</div>
