<?php

use App\Domain\ObjecionCero\Models\Checklist;
use App\Domain\ObjecionCero\Models\ChecklistProgress;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Checklists')] class extends Component {
    #[Computed]
    public function checklists()
    {
        return Checklist::orderBy('orden')->get();
    }

    #[Computed]
    public function checked(): array
    {
        return ChecklistProgress::where('user_id', auth()->id())
            ->get()
            ->map(fn ($p) => "{$p->checklist_id}:{$p->item_key}")
            ->flip()
            ->all();
    }

    public function isChecked(int $checklistId, string $itemKey): bool
    {
        return isset($this->checked["{$checklistId}:{$itemKey}"]);
    }

    public function toggleCheck(int $checklistId, string $itemKey): void
    {
        $progress = ChecklistProgress::where('user_id', auth()->id())
            ->where('checklist_id', $checklistId)
            ->where('item_key', $itemKey)
            ->first();

        if ($progress) {
            $progress->delete();
        } else {
            ChecklistProgress::create([
                'user_id' => auth()->id(),
                'checklist_id' => $checklistId,
                'item_key' => $itemKey,
                'checked_at' => now(),
            ]);
        }
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Secciones 8–10 · Checklists</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 8px">Antes, durante y después</h1>
    <p style="font-size:15px;color:#8b95a3;margin:0 0 34px">Palomea a medida que avanzas. Tu progreso se guarda en tu cuenta.</p>

    @foreach ($this->checklists as $cl)
        <div style="margin-bottom:36px;background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden">
            <div style="padding:22px 26px;border-bottom:1px solid rgba(255,255,255,.07)">
                <div style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:22px;color:#fff">{{ $cl->titulo }}</div>
                @if ($cl->sub)
                    <div style="font-size:13.5px;color:#8b95a3;margin-top:5px">{{ $cl->sub }}</div>
                @endif
            </div>
            <div style="padding:8px 26px 20px">
                @foreach ($cl->bloques as $bi => $b)
                    <div style="padding:16px 0">
                        <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:oklch(0.80 0.13 82);margin-bottom:10px">{{ $b['h'] }}</div>
                        @foreach ($b['items'] as $ii => $item)
                            @php
                                $key = "{$bi}-{$ii}";
                                $on = $this->isChecked($cl->id, $key);
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleCheck({{ $cl->id }}, '{{ $key }}')"
                                style="display:flex;gap:13px;align-items:flex-start;width:100%;text-align:left;background:transparent;border:0;cursor:pointer;padding:7px 0"
                            >
                                <span style="flex:none;width:20px;height:20px;border-radius:6px;margin-top:1px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:{{ $on ? 'oklch(0.55 0.11 155)' : 'transparent' }};color:#0f1319;border:1.5px solid {{ $on ? 'oklch(0.55 0.11 155)' : 'rgba(255,255,255,.2)' }};transition:all .14s">{{ $on ? '✓' : '' }}</span>
                                <span style="font-size:14px;line-height:1.55;color:{{ $on ? '#6b7684' : '#c3ccd6' }};text-decoration:{{ $on ? 'line-through' : 'none' }}">{{ $item }}</span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
            @if ($cl->nota)
                <div style="padding:14px 26px;background:oklch(0.72 0.14 78 / .07);border-top:1px solid oklch(0.72 0.14 78 / .18);font-size:13px;line-height:1.55;color:#c3ccd6">{{ $cl->nota }}</div>
            @endif
        </div>
    @endforeach
</section>
