{{-- SECTION 3: Part Items --}}
<div
    id="parts"
    x-data="jobParts({
        rows: @js($partItems),
        inventory: @js($inventoryForParts ?? []),
    })"
    class="space-y-3"
>
    <div class="flex items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Parts</h3>
            <p class="text-xs text-gray-500">Add one line per part used. Empty rows will be ignored.</p>
        </div>

        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
            @click="addRow()"
        >
            <x-lucide-plus class="w-4 h-4" />
            Add Part Row
        </button>
    </div>

    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left font-semibold text-gray-600">Part / Description</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-600 w-20">Qty</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-600 w-28">Unit Price</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-600 w-28">Line Total</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row, index) in rows" :key="index">
                <tr class="border-t border-gray-100">
                    <td class="px-3 py-2">
                        <div class="flex items-stretch gap-2">
                            <div class="flex-1">
                                <input
                                    type="text"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    :name="`part_items[${index}][description]`"
                                    x-model="row.description"
                                    placeholder="e.g. Oil filter"
                                >
                                <input type="hidden"
                                       :name="`part_items[${index}][inventory_item_id]`"
                                       x-model="row.inventory_item_id">
                            </div>

                            <div class="relative">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-2 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                    @click="row.showPicker = !row.showPicker"
                                    title="Pick from inventory"
                                >
                                    <x-lucide-package-plus class="w-4 h-4" />
                                </button>

                                <div
                                    x-show="row.showPicker"
                                    @click.outside="row.showPicker = false"
                                    x-transition
                                    class="absolute z-20 mt-1 w-72 max-h-64 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg text-xs"
                                >
                                    <div class="px-2 py-1 border-b border-gray-100 font-semibold text-gray-700">
                                        Pick from inventory
                                    </div>

                                    <button
                                        type="button"
                                        class="w-full text-left px-3 py-2 text-gray-500 hover:bg-gray-50"
                                        @click="pickFromInventory(index, null)"
                                    >
                                        — Clear selection —
                                    </button>

                                    <template x-for="item in inventory" :key="item.id">
                                        <button
                                            type="button"
                                            class="w-full text-left px-3 py-2 hover:bg-indigo-50"
                                            @click="pickFromInventory(index, item.id)"
                                        >
                                            <div class="font-medium text-gray-900" x-text="item.name"></div>
                                            <div class="text-[11px] text-gray-500">
                                                KES <span x-text="formatMoney(item.price)"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-3 py-2">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            :name="`part_items[${index}][quantity]`"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                            x-model.number="row.quantity"
                        >
                    </td>

                    <td class="px-3 py-2">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            :name="`part_items[${index}][unit_price]`"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                            x-model.number="row.unit_price"
                        >
                    </td>

                    <td class="px-3 py-2">
                        <input
                            type="text"
                            readonly
                            class="block w-full rounded-md border-gray-200 bg-gray-50 text-right text-sm"
                            :value="formatMoney((Number(row.quantity || 0) * Number(row.unit_price || 0)))"
                            placeholder="auto"
                        >
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>

<script>
    function jobParts(initial) {
        return {
            rows: (initial.rows || []).map(r => ({
                description: r.description ?? '',
                quantity: r.quantity ?? null,
                unit_price: r.unit_price ?? null,
                line_total: r.line_total ?? null,
                inventory_item_id: r.inventory_item_id ?? null,
                showPicker: false,
            })),
            inventory: initial.inventory || [],

            addRow() {
                this.rows.push({
                    description: '',
                    quantity: null,
                    unit_price: null,
                    line_total: null,
                    inventory_item_id: null,
                    showPicker: false,
                });
            },

            pickFromInventory(rowIndex, inventoryId) {
                const row = this.rows[rowIndex];

                if (!inventoryId) {
                    row.inventory_item_id = null;
                    row.showPicker = false;
                    return;
                }

                const item = this.inventory.find(i => i.id === inventoryId);
                if (!item) return;

                row.description       = item.name;
                row.unit_price        = item.price;
                row.inventory_item_id = item.id;
                row.showPicker        = false;
            },

            formatMoney(value) {
                const n = Number(value || 0);
                return n.toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
        }
    }
</script>

<hr class="my-6">