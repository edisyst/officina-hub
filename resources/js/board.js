import Sortable from 'sortablejs';

window.boardComponent = function () {
    return {
        sortables: [],

        init() {
            this.$nextTick(() => this.setupSortable());
            document.addEventListener('livewire:updated', () => {
                this.destroySortable();
                this.$nextTick(() => this.setupSortable());
            });
        },

        setupSortable() {
            this.destroySortable();
            document.querySelectorAll('[data-board-column]').forEach((el) => {
                const instance = new Sortable(el, {
                    group: 'board',
                    animation: 150,
                    ghostClass: 'board-card-ghost',
                    dragClass: 'board-card-drag',
                    onEnd: (evt) => {
                        const id = parseInt(evt.item.dataset.commessaId, 10);
                        const toCol = evt.to.dataset.boardColumn;
                        const orderedIds = Array.from(evt.to.children)
                            .filter((c) => c.dataset.commessaId)
                            .map((c) => parseInt(c.dataset.commessaId, 10));

                        this.$wire.moveCard(id, toCol, orderedIds);
                    },
                });
                this.sortables.push(instance);
            });
        },

        destroySortable() {
            this.sortables.forEach((s) => s.destroy());
            this.sortables = [];
        },
    };
};
