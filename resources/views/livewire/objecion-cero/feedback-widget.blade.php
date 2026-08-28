<div style="position:fixed;right:20px;bottom:20px;z-index:60;font-family:'IBM Plex Sans',sans-serif" x-data>
    @if ($open)
        <div style="width:300px;max-width:calc(100vw - 40px);background:#141a24;border:1px solid rgba(255,255,255,.1);border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.45);padding:18px;margin-bottom:12px;animation:ocfade .18s ease both">
            @if ($sent)
                <div style="text-align:center;padding:14px 4px">
                    <div style="font-size:26px;margin-bottom:8px">✓</div>
                    <div style="font-size:13.5px;color:#dbe3ec;line-height:1.5">¡Gracias! Tu feedback nos ayuda a mejorar Objeción Cero.</div>
                    <button type="button" wire:click="toggle" style="margin-top:14px;background:transparent;border:1px solid rgba(255,255,255,.14);color:#8b95a3;border-radius:8px;padding:7px 14px;font-size:12px;cursor:pointer">Cerrar</button>
                </div>
            @else
                <div style="font:600 10px 'IBM Plex Mono',monospace;letter-spacing:.12em;text-transform:uppercase;color:oklch(0.80 0.13 82);margin-bottom:8px">Dejar feedback</div>
                <div style="font-size:12.5px;color:#8b95a3;line-height:1.5;margin-bottom:12px">¿Algo que no funciona, falta, o te gustaría ver? Cuéntanos.</div>
                <form wire:submit="send">
                    <textarea
                        wire:model="message"
                        rows="4"
                        placeholder="Escribe aquí…"
                        style="width:100%;background:#0b0f16;border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:10px 12px;color:#e7ebf0;font:400 13.5px 'IBM Plex Sans';outline:none;resize:vertical"
                    ></textarea>
                    @error('message')
                        <div style="color:oklch(0.72 0.15 30);font-size:11.5px;margin-top:6px">{{ $message }}</div>
                    @enderror
                    <div style="display:flex;gap:8px;margin-top:12px">
                        <button type="submit" style="flex:1;background:oklch(0.72 0.14 78);color:#1a1205;border:none;border-radius:8px;padding:9px 0;font-size:12.5px;font-weight:700;cursor:pointer">Enviar</button>
                        <button type="button" wire:click="toggle" style="background:transparent;border:1px solid rgba(255,255,255,.14);color:#8b95a3;border-radius:8px;padding:9px 14px;font-size:12.5px;cursor:pointer">Cancelar</button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    <button
        type="button"
        wire:click="toggle"
        aria-label="Dejar feedback"
        style="display:flex;align-items:center;gap:8px;background:#161c26;border:1px solid rgba(255,255,255,.1);color:#c3ccd6;border-radius:24px;padding:11px 16px;font-size:12.5px;font-weight:600;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.35)"
    >💬 Feedback</button>
</div>
