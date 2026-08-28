<?php

use App\Domain\ObjecionCero\Models\UsoItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.objecion-cero')] #[Title('Cómo usar · Términos')] class extends Component {
    #[Computed]
    public function uso()
    {
        return UsoItem::orderBy('sort_order')->get();
    }
}; ?>

<section style="animation:ocfade .4s ease both;padding-top:56px;max-width:760px">
    <div style="font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.2em;text-transform:uppercase;color:oklch(0.72 0.14 78)">Cómo usar · Términos</div>
    <h1 style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:44px;letter-spacing:-.02em;color:#fff;margin:12px 0 26px">Este documento no se lee, se consulta</h1>

    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:44px">
        @foreach ($this->uso as $u)
            <div style="display:flex;gap:16px;background:#141a24;border:1px solid rgba(255,255,255,.07);border-radius:11px;padding:18px 22px">
                <div style="flex:none;width:140px;font:600 12px 'IBM Plex Mono',monospace;color:oklch(0.80 0.13 82);text-transform:uppercase;letter-spacing:.06em;padding-top:2px">{{ $u->title }}</div>
                <div style="font-size:14px;line-height:1.6;color:#aeb8c4">{{ $u->description }}</div>
            </div>
        @endforeach
    </div>

    <h2 style="font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:22px;color:#fff;margin:0 0 16px">Términos de uso</h2>
    <div style="font-size:13.5px;line-height:1.7;color:#8b95a3;display:flex;flex-direction:column;gap:14px">
        <p style="margin:0"><b style="color:#c3ccd6">Derechos de autor.</b> Este material —textos, scripts, plantillas, matrices y estructura— está protegido por las leyes de propiedad intelectual. Todos los derechos reservados a su autor.</p>
        <p style="margin:0"><b style="color:#c3ccd6">Licencia de uso personal.</b> La compra otorga una licencia individual, intransferible y de uso exclusivamente personal o interno para la actividad comercial del comprador. Queda prohibido copiar, distribuir, revender, sublicenciar o modificar el contenido para su explotación comercial.</p>
        <p style="margin:0"><b style="color:#c3ccd6">Aviso legal.</b> Este material no garantiza resultados específicos. Los resultados comerciales dependen de la preparación, la práctica, el contexto de cada negociación, el mercado y la ejecución individual del usuario.</p>
        <p style="margin:0"><b style="color:#c3ccd6">Alcance.</b> El contenido tiene fines informativos y de apoyo práctico. No sustituye asesoría profesional, legal, financiera ni fiscal. El uso de este documento implica la aceptación íntegra de estos términos.</p>
    </div>
</section>
