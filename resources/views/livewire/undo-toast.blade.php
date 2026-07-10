<div
    x-data="undoToastManager()"
    x-init="init()"
    style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;max-width:380px;"
>
    @foreach($toasts as $toast)
    <div
        x-data="undoToastItem({{ $toast['windowSec'] }}, '{{ $toast['key'] }}')"
        x-init="start()"
        x-show="visible"
        x-transition
        class="toast show"
        style="min-width:320px;"
    >
        <div class="toast-header bg-dark text-white">
            <i class="fas fa-check-circle mr-2 text-success"></i>
            <strong class="mr-auto">Operazione completata</strong>
            <button type="button" class="ml-2 mb-1 close text-white" @click="dismiss">
                <span>&times;</span>
            </button>
        </div>
        <div class="toast-body d-flex justify-content-between align-items-center">
            <span>{{ $toast['message'] }}</span>
            @if($toast['activityId'])
            <div class="d-flex align-items-center gap-2 ml-2">
                <small class="text-muted" x-text="remaining + 's'"></small>
                <button
                    class="btn btn-sm btn-warning ml-1"
                    wire:click="undo({{ $toast['activityId'] }})"
                    wire:loading.attr="disabled"
                >
                    <i class="fas fa-undo"></i> Annulla
                </button>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

<script>
function undoToastManager() {
    return {};
}
function undoToastItem(windowSec, key) {
    return {
        visible: true,
        remaining: windowSec,
        _interval: null,
        start() {
            this._interval = setInterval(() => {
                this.remaining--;
                if (this.remaining <= 0) this.dismiss();
            }, 1000);
        },
        get dismiss() {
            return () => {
                clearInterval(this._interval);
                this.visible = false;
                setTimeout(() => {
                    @this.dismiss(key);
                }, 300);
            };
        },
    };
}
</script>
