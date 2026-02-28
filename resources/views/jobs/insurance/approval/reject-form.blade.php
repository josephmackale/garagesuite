<form method="POST"
      action="{{ route('jobs.insurance.approval.reject', $job) }}"
      class="rounded-xl border border-gray-200 bg-white p-4">
    @csrf

    <div class="text-sm font-semibold text-gray-900 mb-3">Reject</div>

    <div class="space-y-2">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Reason</label>
            <textarea name="rejection_reason"
                      rows="4"
                      required
                      class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900">{{ old('rejection_reason') }}</textarea>
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
            Reject
        </button>
    </div>
</form>