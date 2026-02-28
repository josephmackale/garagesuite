{{-- resources/views/inventory/partials/form.blade.php --}}
<div>
    <label class="block text-sm font-medium mb-1">Name</label>
    <input type="text" name="name" class="w-full border-gray-300 rounded-md"
           value="{{ old('name', $item->name ?? '') }}" required>
</div>

<div>
    <label class="block text-sm font-medium mb-1">Category</label>
    <input type="text" name="category" class="w-full border-gray-300 rounded-md"
           value="{{ old('category', $item->category ?? '') }}">
</div>

<div>
    <label class="block text-sm font-medium mb-1">Brand</label>
    <input type="text" name="brand" class="w-full border-gray-300 rounded-md"
           value="{{ old('brand', $item->brand ?? '') }}">
</div>

<div>
    <label class="block text-sm font-medium mb-1">Part Number</label>
    <input type="text" name="part_number" class="w-full border-gray-300 rounded-md"
           value="{{ old('part_number', $item->part_number ?? '') }}">
</div>

<div class="grid grid-cols-2 gap-4">

    <div>
        <label class="block text-sm font-medium mb-1">Unit</label>
        <input type="text" name="unit" class="w-full border-gray-300 rounded-md"
               value="{{ old('unit', $item->unit ?? '') }}" required>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Reorder Level</label>
        <input type="number" name="reorder_level" min="0"
               class="w-full border-gray-300 rounded-md"
               value="{{ old('reorder_level', $item->reorder_level ?? 0) }}" required>
    </div>

</div>

<div class="grid grid-cols-2 gap-4">

    <div>
        <label class="block text-sm font-medium mb-1">Cost Price</label>
        <input type="number" step="0.01" name="cost_price"
               class="w-full border-gray-300 rounded-md"
               value="{{ old('cost_price', $item->cost_price ?? '') }}" required>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Selling Price</label>
        <input type="number" step="0.01" name="selling_price"
               class="w-full border-gray-300 rounded-md"
               value="{{ old('selling_price', $item->selling_price ?? '') }}" required>
    </div>

</div>

@if($item === null)
<div>
    <label class="block text-sm font-medium mb-1">Initial Stock</label>
    <input type="number" name="current_stock" min="0"
           class="w-full border-gray-300 rounded-md"
           value="{{ old('current_stock', 0) }}">
</div>
@endif

<div>
    <label class="block text-sm font-medium mb-1">Status</label>
    <select name="status" class="w-full border-gray-300 rounded-md">
        <option value="active"
            {{ old('status', $item->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive"
            {{ old('status', $item->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
</div>
