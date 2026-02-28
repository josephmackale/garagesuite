<form method="POST"
      action="{{ route('jobs.insurance.approval.approve', $job) }}"
      class="rounded-xl border border-gray-200 bg-white p-4"
      data-insurance-approve
      data-job-id="{{ $job->id }}">

    @csrf

    <div class="text-sm font-semibold text-gray-900 mb-3">Approve</div>

    <div class="space-y-2">

        {{-- Approved By --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Approved By
            </label>
            <input name="approved_by"
                   type="text"
                   required
                   value="{{ old('approved_by') }}"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900" />
        </div>

        {{-- Approval Ref --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Approval Ref (Email / Insurer Ref)
            </label>
            <input name="approval_ref"
                   type="text"
                   required
                   value="{{ old('approval_ref') }}"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900" />
        </div>

        {{-- LPO --}}
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">
                LPO Number (required before invoicing)
            </label>
            <input type="text"
                   name="lpo_number"
                   value="{{ old('lpo_number', $lpo ?? '') }}"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900"
                   placeholder="e.g. LPO-12345">
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Notes (optional)
            </label>
            <textarea name="approval_notes"
                      rows="3"
                      class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900">{{ old('approval_notes') }}</textarea>
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700">
            Approve
        </button>
    </div>
</form>