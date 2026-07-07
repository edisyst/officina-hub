<div
    x-data="{
        open: @entangle('open'),
        focusedIndex: -1,
        flatItems: [],
        focusedActionIndex: -1,

        init() {
            this.$watch('open', v => {
                if (v) {
                    this.$nextTick(() => this.$refs.input && this.$refs.input.focus());
                    this.focusedIndex = -1;
                    this.focusedActionIndex = -1;
                }
            });
            this.$watch('flatItems', () => {
                this.focusedIndex = -1;
                this.focusedActionIndex = -1;
            });
        },

        computeFlat(results) {
            let items = [];
            (results || []).forEach(group => {
                (group.items || []).forEach(item => items.push(item));
            });
            this.flatItems = items;
        },

        handleKey(e) {
            if (!this.open) return;
            if (e.key === 'Escape') { this.open = false; return; }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.focusedActionIndex = -1;
                this.focusedIndex = Math.min(this.focusedIndex + 1, this.flatItems.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.focusedActionIndex = -1;
                this.focusedIndex = Math.max(this.focusedIndex - 1, -1);
            } else if (e.key === 'Tab' && this.focusedIndex >= 0) {
                e.preventDefault();
                let actions = (this.flatItems[this.focusedIndex] || {}).quick_actions || [];
                if (actions.length) {
                    this.focusedActionIndex = (this.focusedActionIndex + 1) % actions.length;
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.focusedIndex >= 0) {
                    let item = this.flatItems[this.focusedIndex];
                    if (!item) return;
                    if (this.focusedActionIndex >= 0) {
                        let action = (item.quick_actions || [])[this.focusedActionIndex];
                        if (action) window.location.href = action.url;
                    } else {
                        @this.recordSelection(item.url, item.label, '');
                    }
                }
            }
        }
    }"
    @keydown.window="handleKey($event)"
    @open-command-palette.window="open = true"
>
    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.self="open = false"
        style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9990;display:flex;align-items:flex-start;justify-content:center;padding-top:10vh"
        role="dialog"
        aria-modal="true"
        aria-label="Command palette — ricerca globale"
        x-cloak
    >
        <div
            @click.stop
            style="background:#fff;border-radius:8px;width:100%;max-width:640px;box-shadow:0 25px 60px rgba(0,0,0,.3);overflow:hidden;margin:0 16px"
        >
            {{-- Search input --}}
            <div style="display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid #e9ecef">
                <i class="fas fa-search" style="color:#aaa;margin-right:10px"></i>
                <input
                    x-ref="input"
                    wire:model.live.debounce.250ms="query"
                    @input="computeFlat($wire.results)"
                    type="text"
                    placeholder="Cerca targa, cliente, telefono, codice ricambio, numero OdL…"
                    autocomplete="off"
                    style="flex:1;border:none;outline:none;font-size:16px;background:transparent"
                    aria-label="Campo di ricerca"
                >
                <kbd style="font-size:11px;background:#f1f3f5;border:1px solid #dee2e6;border-radius:4px;padding:2px 6px;color:#666">Esc</kbd>
            </div>

            {{-- Results --}}
            <div style="max-height:480px;overflow-y:auto" wire:loading.class="opacity-50">

                {{-- Recenti (query vuota) --}}
                @if(trim($query) === '' && count($recenti) > 0)
                    <div style="padding:8px 16px 4px;font-size:11px;font-weight:600;color:#868e96;text-transform:uppercase;letter-spacing:.05em">
                        Recenti
                    </div>
                    @foreach($recenti as $r)
                        <a
                            href="{{ $r['url'] }}"
                            style="display:flex;align-items:center;padding:10px 16px;text-decoration:none;color:#212529"
                            onmouseenter="this.style.background='#f8f9fa'"
                            onmouseleave="this.style.background='transparent'"
                        >
                            <i class="fas fa-clock" style="color:#aaa;margin-right:10px;width:14px"></i>
                            <span>{{ $r['label'] }}</span>
                        </a>
                    @endforeach
                @endif

                {{-- Suggerimenti (query vuota, nessun recente) --}}
                @if(trim($query) === '' && count($recenti) === 0)
                    <div style="padding:32px 16px;text-align:center;color:#868e96">
                        <i class="fas fa-search" style="font-size:28px;display:block;margin-bottom:12px;opacity:.3"></i>
                        <div style="font-size:14px">Cerca per:</div>
                        <div style="margin-top:8px;font-size:13px">
                            Targa &bull; Cliente &bull; Telefono &bull; Codice ricambio &bull; Numero OdL
                        </div>
                    </div>
                @endif

                {{-- Nessun risultato --}}
                @if(trim($query) !== '' && count($results) === 0)
                    <div style="padding:32px 16px;text-align:center;color:#868e96;font-size:14px">
                        Nessun risultato per "{{ $query }}"
                    </div>
                @endif

                {{-- Risultati per gruppo --}}
                @php $flatIdx = 0; @endphp
                @foreach($results as $group)
                    <div style="padding:8px 16px 4px;font-size:11px;font-weight:600;color:#868e96;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px">
                        <i class="{{ $group['icon'] }}"></i>
                        {{ $group['label'] }}
                    </div>
                    @foreach($group['items'] as $item)
                        @php $currentIdx = $flatIdx++; @endphp
                        <div
                            x-bind:style="focusedIndex === {{ $currentIdx }} ? 'background:#e8f4ff' : ''"
                            @mouseenter="focusedIndex = {{ $currentIdx }}; focusedActionIndex = -1"
                            style="padding:10px 16px;cursor:pointer"
                        >
                            <div style="display:flex;align-items:center;justify-content:space-between">
                                <div
                                    style="flex:1;min-width:0"
                                    wire:click="recordSelection('{{ addslashes($item['url']) }}', '{{ addslashes($item['label']) }}', '{{ $group['tipo'] }}')"
                                    style="cursor:pointer"
                                >
                                    <div style="font-size:14px;font-weight:500;color:#212529;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        {{ $item['label'] }}
                                    </div>
                                    @if($item['secondary'])
                                        <div style="font-size:12px;color:#868e96;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            {{ $item['secondary'] }}
                                        </div>
                                    @endif
                                </div>
                                {{-- Quick actions --}}
                                <div
                                    x-show="focusedIndex === {{ $currentIdx }}"
                                    style="display:flex;gap:4px;margin-left:8px;flex-shrink:0"
                                >
                                    @foreach($item['quick_actions'] as $ai => $action)
                                        <a
                                            href="{{ $action['url'] }}"
                                            x-bind:style="focusedActionIndex === {{ $ai }} ? 'background:#0d6efd;color:#fff' : 'background:#e9ecef;color:#495057'"
                                            title="{{ $action['label'] }}"
                                            style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:4px;font-size:11px;text-decoration:none;white-space:nowrap;transition:background .1s"
                                            @mouseenter="focusedActionIndex = {{ $ai }}"
                                            @mouseleave="focusedActionIndex = -1"
                                        >
                                            <i class="{{ $action['icon'] }}"></i>
                                            {{ $action['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- Footer --}}
            <div style="padding:8px 16px;border-top:1px solid #e9ecef;font-size:11px;color:#aaa;display:flex;gap:16px">
                <span><kbd style="background:#f1f3f5;border:1px solid #dee2e6;border-radius:3px;padding:1px 4px">↑↓</kbd> naviga</span>
                <span><kbd style="background:#f1f3f5;border:1px solid #dee2e6;border-radius:3px;padding:1px 4px">↵</kbd> apri</span>
                <span><kbd style="background:#f1f3f5;border:1px solid #dee2e6;border-radius:3px;padding:1px 4px">Tab</kbd> azioni rapide</span>
                <span><kbd style="background:#f1f3f5;border:1px solid #dee2e6;border-radius:3px;padding:1px 4px">Esc</kbd> chiudi</span>
            </div>
        </div>
    </div>

    {{-- Ctrl+K global listener (outside overlay so it works always) --}}
    <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('open-command-palette'));
            }
        });
    </script>
</div>
