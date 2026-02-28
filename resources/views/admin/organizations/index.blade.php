{{-- resources/views/admin/organizations/index.blade.php --}}
<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900">Organizations</h2>
                <p class="mt-1 text-sm text-slate-500">Insurance & Corporate partners</p>
            </div>

            <button type="button"
                    onclick="document.getElementById('orgCreateModal').showModal()"
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                Add Organization
            </button>

        </div>
    </x-slot>

    <div class="max-w-6xl space-y-4">

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Billing</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($organizations as $org)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    {{ $org->name }}
                                </td>

                                <td class="px-4 py-3">
                                    <span class="text-xs rounded-full px-2 py-1 font-semibold
                                        {{ $org->isInsurance() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ ucfirst($org->type) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-xs text-slate-700">
                                    {{ $org->billing_terms }} days
                                </td>

                                <td class="px-4 py-3">
                                    <span class="text-xs rounded-full px-2 py-1 font-semibold
                                        {{ $org->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($org->status) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.organizations.edit', $org) }}"
                                       class="text-indigo-600 text-xs font-semibold hover:text-indigo-800">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No organizations yet. Click <span class="font-semibold">Add Organization</span> to create insurers/corporates.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div>
            {{ $organizations->links() }}
        </div>

    </div>
{{-- Create Organization Modal --}}
<dialog id="orgCreateModal" class="rounded-2xl p-0 w-full max-w-2xl backdrop:bg-black/40">
    <form method="dialog" class="bg-white rounded-2xl border border-slate-100 shadow-xl">
        <div class="p-5 border-b border-slate-100 flex items-start justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Add Organization</h3>
                <p class="mt-1 text-xs text-slate-500">Insurance & Corporate partner details</p>
            </div>

            <button class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                Close
            </button>
        </div>
    </form>

    {{-- REAL POST form --}}
    <form method="POST" action="{{ route('admin.organizations.store') }}" class="p-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700">Name *</label>
                <input name="name"
                       value="{{ old('name') }}"
                       class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                       placeholder="e.g. Jubilee Insurance"
                       required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Type *</label>
                <select name="type" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    <option value="insurance" @selected(old('type')==='insurance')>Insurance</option>
                    <option value="corporate" @selected(old('type')==='corporate')>Corporate</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Status *</label>
                <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    <option value="active" @selected(old('status')==='active')>Active</option>
                    <option value="inactive" @selected(old('status')==='inactive')>Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Billing terms (days)</label>
                <input type="number"
                       name="billing_terms"
                       min="0"
                       value="{{ old('billing_terms', 30) }}"
                       class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Contact person</label>
                <input name="contact_person"
                       value="{{ old('contact_person') }}"
                       class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                       placeholder="Optional">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Phone</label>
                <input name="phone"
                       value="{{ old('phone') }}"
                       class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                       placeholder="Optional">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                       placeholder="Optional">
            </div>

        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button"
                    onclick="document.getElementById('orgCreateModal').close()"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </button>

            <button type="submit"
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                Create Organization
            </button>
        </div>
    </form>
</dialog>

{{-- Auto-open modal if validation errors happened on store --}}
@if($errors->any())
    <script>
        window.addEventListener('load', () => {
            const d = document.getElementById('orgCreateModal');
            if (d && typeof d.showModal === 'function') d.showModal();
        });
    </script>
@endif

</x-app-layout>
