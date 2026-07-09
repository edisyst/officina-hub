<span
    x-data="{
        editing: false,
        val: @js($value),
        orig: @js($value),
        save() {
            this.editing = false;
            if (this.val !== this.orig) {
                $wire.{{ $saveMethod }}({{ $recordId }}, '{{ $field }}', this.val)
                    .then(ok => { if (ok) { this.orig = this.val; } else { this.val = this.orig; } })
                    .catch(() => { this.val = this.orig; });
            }
        },
        cancel() { this.val = this.orig; this.editing = false; },
        startEdit() { this.editing = true; this.$nextTick(() => this.$refs.inp.select()); }
    }"
    class="d-inline-block w-100"
>
    <span
        x-show="!editing"
        x-on:click="startEdit()"
        class="inline-edit-display {{ $displayClass }}"
        style="cursor:pointer;border-bottom:1px dashed #aaa;display:inline-block;min-width:40px"
        title="Clicca per modificare"
        x-text="val !== null && val !== '' ? val : '—'"
    ></span>

    <span x-show="editing" x-cloak>
        <input
            x-ref="inp"
            x-model="val"
            type="{{ $type }}"
            @if($step) step="{{ $step }}" @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="form-control form-control-sm d-inline-block"
            style="width:auto;min-width:80px"
            x-on:keydown.enter.prevent="save()"
            x-on:keydown.escape.prevent="cancel()"
            x-on:keydown.tab.prevent="save(); $nextTick(() => { let next = document.querySelector('[data-inline-next=\'{{ $recordId }}-{{ $field }}\']'); if(next) next.click(); })"
            x-on:blur="save()"
        >
        @error('inlineEdit.' . $recordId . '.' . $field)
        <small class="text-danger d-block">{{ $message }}</small>
        @enderror
    </span>
</span>
