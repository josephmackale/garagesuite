{{-- resources/views/admin/organizations/form.blade.php --}}
<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-900">
                    {{ $mode === 'create' ? 'Add Organization' : 'Edit Organization' }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Insurance & Corporate partner details</p>
            </div>

            <a href="{{ route('admin.organizations.index') }}"
               class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                ← Back to organizations
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-4">

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">

            <form method="POST"
                  action="{{ $mode === 'create'
                            ? route('admin.organizations.store')
                            : route('admin.organizations.update', $organization) }}">
                @csrf
                @if($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="grid gap-4 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Name *</label>
                        <input name="name"
                               value="{{ old('name', $organization->name) }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                               placeholder="e.g. Jubilee Insurance">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Type *</label>
                        <select name="type" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            <option value="insurance" @selected(old('type', $organization->type) === 'insurance')>Insurance</option>
                            <option value="corporate" @selected(old('type', $organization->type) === 'corporate')>Corporate</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Status *</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            <option value="active" @selected(old('status', $organization->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $organization->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Billing terms (days)</label>
                        <input type="number"
                               name="billing_terms"
                               min="0"
                               value="{{ old('billing_terms', $organization->billing_terms ?? 30) }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Contact person</label>
                        <input name="contact_person"
                               value="{{ old('contact_person', $organization->contact_person) }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                               placeholder="Optional">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Phone</label>
                        <input name="phone"
                               value="{{ old('phone', $organization->phone) }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                               placeholder="Optional">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700">Email</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $organization->email) }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                               placeholder="Optional">
                    </div>

                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('admin.organizations.index') }}"
                       class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>

                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        {{ $mode === 'create' ? 'Create Organization' : 'Save Changes' }}
                    </button>
                </div>

            </form>

        </div>

    </div>

</x-app-layout>
