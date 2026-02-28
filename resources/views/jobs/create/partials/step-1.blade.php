<div class="max-w-3xl mx-auto p-6">
    @include('jobs.create._progress', ['current' => 'step1'])

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h1 class="text-xl font-semibold text-slate-900">Create Job</h1>
        <p class="text-sm text-slate-600 mt-1">
            Choose who is paying for this job. This controls the entire workflow.
        </p>

        <form method="POST" action="{{ route('jobs.create.step1.post', ['modal' => $modal ?? false]) }}" class="mt-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach (['individual' => 'Individual', 'company' => 'Company', 'insurance' => 'Insurance'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio"
                               name="payer_type"
                               value="{{ $value }}"
                               class="peer sr-only"
                               {{ old('payer_type', $payer_type) === $value ? 'checked' : '' }}>

                        <div class="rounded-2xl border p-4 h-full transition
                            peer-checked:border-indigo-500
                            peer-checked:ring-2
                            peer-checked:ring-indigo-500/20
                            border-slate-200 hover:border-slate-300">

                            <div class="text-sm font-semibold text-slate-900">
                                {{ $label }}
                            </div>

                            <div class="text-xs text-slate-600 mt-1">
                                @if($value === 'individual')
                                    Customer pays directly.
                                @elseif($value === 'company')
                                    Organization/fleet account.
                                @else
                                    Insurance claim job.
                                @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            @error('payer_type')
                <div class="mt-3 text-sm text-red-600">{{ $message }}</div>
            @enderror

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button"
                        onclick="closeCreateJobModal()"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700">
                    Cancel
                </button>

                <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold">
                    Continue
                </button>
            </div>
        </form>
    </div>
</div>
