@props([
    'text',
    'label' => 'Copiar',
])

<button
    type="button"
    class="oc-copy-button"
    x-data="{
        state: 'idle',
        resetTimer: null,
        async copyText(value) {
            let temporaryTextarea = null;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    temporaryTextarea = document.createElement('textarea');
                    temporaryTextarea.value = value;
                    temporaryTextarea.style.position = 'fixed';
                    temporaryTextarea.style.opacity = '0';
                    document.body.appendChild(temporaryTextarea);
                    temporaryTextarea.select();

                    if (!document.execCommand('copy')) {
                        throw new Error('Copy command failed');
                    }
                }

                this.state = 'copied';
            } catch (error) {
                this.state = 'error';
            } finally {
                temporaryTextarea?.remove();
                clearTimeout(this.resetTimer);
                this.resetTimer = setTimeout(() => this.state = 'idle', 1800);
            }
        },
    }"
    @click="copyText($el.dataset.copyText)"
    data-copy-text="{{ $text }}"
    aria-live="polite"
    style="flex:none;min-height:34px;background:#1b2430;border:1px solid rgba(255,255,255,.13);border-radius:8px;padding:7px 11px;color:#dbe3ec;font:600 10.5px 'IBM Plex Sans',sans-serif;cursor:pointer;white-space:nowrap"
    {{ $attributes }}
>
    <span x-show="state === 'idle'">{{ $label }}</span>
    <span x-cloak x-show="state === 'copied'" style="color:oklch(0.80 0.12 155)">Copiado ✓</span>
    <span x-cloak x-show="state === 'error'" style="color:oklch(0.78 0.15 30)">Reintentar</span>
</button>
