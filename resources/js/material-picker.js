export default (config) => ({
    open: false,
    selectedItems: config.initialSelected || [],
    property: config.property || 'selectedMaterialIds',

    get selectedIds() {
        return this.selectedItems.map((item) => item.id);
    },

    toggle(item) {
        const index = this.selectedItems.findIndex((selected) => selected.id === item.id);

        if (index >= 0) {
            this.selectedItems.splice(index, 1);
        } else {
            this.selectedItems.push(item);
        }

        this.sync();
    },

    remove(id) {
        this.selectedItems = this.selectedItems.filter((item) => item.id !== id);
        this.sync();
    },

    setItems(items) {
        this.selectedItems = items || [];
        this.sync();
    },

    sync() {
        this.$wire.set(this.property, this.selectedIds, false);
    },
});
