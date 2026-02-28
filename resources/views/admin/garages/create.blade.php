{{-- resources/views/admin/garages/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Register Garage</h2>
                <p class="mt-1 text-sm text-gray-500">Create a garage and an owner who can log in immediately.</p>
            </div>

            <a href="{{ route('admin.garages.index') }}"
               class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                ← Back
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-4">
        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                <div class="font-semibold mb-1">Fix the following:</div>
                <ul class="list-disc ml-5 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.garages.store') }}"
              class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
            @csrf

            <div>
                <h3 class="text-sm font-semibold text-slate-900">Garage details</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Garage name *</label>
                        <input name="garage_name" value="{{ old('garage_name') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Garage phone</label>
                        <input name="garage_phone" value="{{ old('garage_phone') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">City / area</label>
                        <input name="garage_city" value="{{ old('garage_city') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Trial days *</label>
                        <input type="number" name="trial_days" value="{{ old('trial_days', 7) }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm" min="1" max="60" required>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Address / landmark</label>
                        <input name="garage_address" value="{{ old('garage_address') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-sm font-semibold text-slate-900">Owner login</h3>
                <p class="mt-1 text-xs text-slate-500">This owner can log in immediately using email + password.</p>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Owner name *</label>
                        <input name="owner_name" value="{{ old('owner_name') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Owner email *</label>
                        <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Temporary password *</label>
                        <input type="text" name="owner_password" value="{{ old('owner_password') }}"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        <p class="mt-1 text-xs text-slate-500">Share this with the owner. They can change later.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.garages.index') }}"
                   class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                    Cancel
                </a>

                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                    Create garage
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
