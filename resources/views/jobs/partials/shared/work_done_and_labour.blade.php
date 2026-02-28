{{-- Work done --}}
<div class="mb-4" id="labour">
    <x-input-label for="work_done" value="Work Done (description)" />
    <textarea
        id="work_done"
        name="work_done"
        rows="4"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        placeholder="Describe all work carried out..."
    >{{ old('work_done', $job->work_done ?? '') }}</textarea>
</div>

{{-- Labour: single cost field --}}
<div class="mb-4">
    <x-input-label for="labour_cost" value="Labour Cost" />
    <div class="mt-1 flex items-center gap-2">
        <span class="text-sm text-gray-500">KES</span>
        <input
            type="number"
            name="labour_cost"
            id="labour_cost"
            step="0.01"
            class="w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="0.00"
            value="{{ old('labour_cost', $job->labour_cost ?? '') }}"
        />
    </div>
    <p class="mt-1 text-xs text-gray-500">
        Put the total labour charge here. Details stay in “Work Done”.
    </p>
</div>

<hr class="my-6">