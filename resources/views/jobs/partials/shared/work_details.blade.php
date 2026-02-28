{{-- SECTION 2: Work Details --}}
<div class="space-y-2">
    <h3 class="text-base font-semibold text-gray-900">
        Work Details
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="diagnosis" value="Diagnosis" />
            <textarea
                id="diagnosis"
                name="diagnosis"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                placeholder="What did you find after inspection?"
            >{{ old('diagnosis', $job->diagnosis) }}</textarea>
            <x-input-error :messages="$errors->get('diagnosis')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="notes" value="Internal Notes" />
            <textarea
                id="notes"
                name="notes"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                placeholder="Internal notes for your team."
            >{{ old('notes', $job->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
        </div>
    </div>
</div>

<hr class="my-6">