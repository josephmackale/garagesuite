{{-- resources/views/admin/garages/show.blade.php --}}
<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">{{ $garage->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Code: <span class="font-mono">{{ $garage->garage_code }}</span>
                </p>
            </div>

            <a href="{{ route('admin.garages.index') }}"
               class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                ← Back to garages
            </a>
        </div>
    </x-slot>


    <div class="max-w-6xl grid gap-4 lg:grid-cols-3">


        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 text-sm">
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


        {{-- =========================
             Garage Info
             ========================= --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 lg:col-span-2">

            <div class="grid gap-3 sm:grid-cols-2 text-sm">

                <div>
                    <span class="text-slate-500">Status:</span>
                    @php
                        $statusColors = [
                            'trial' => 'bg-yellow-100 text-yellow-800',
                            'active' => 'bg-emerald-100 text-emerald-800',
                            'suspended' => 'bg-red-100 text-red-800',
                        ];
                    @endphp

                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        {{ $statusColors[$garage->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ ucfirst($garage->status) }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-500">Phone:</span>
                    <span class="font-semibold">{{ $garage->phone ?? '—' }}</span>
                </div>

                <div>
                    <span class="text-slate-500">Trial ends:</span>
                    <span class="font-semibold">
                        {{ $garage->trial_ends_at
                            ? \Illuminate\Support\Carbon::parse($garage->trial_ends_at)->format('d M Y, H:i')
                            : '—'
                        }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-500">Subscription expires:</span>
                    <span class="font-semibold">
                        {{ $garage->subscription_expires_at
                            ? \Illuminate\Support\Carbon::parse($garage->subscription_expires_at)->format('d M Y, H:i')
                            : '—'
                        }}
                    </span>
                </div>

            </div>


            <div class="mt-5 border-t pt-5">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <p class="text-sm font-semibold text-slate-900">Owner</p>

                        <p class="text-sm text-slate-700 mt-1">
                            {{ $owner?->name ?? '—' }} · {{ $owner?->email ?? '—' }}
                        </p>
                    </div>


                    {{-- Impersonate --}}
                    @if($owner && \Illuminate\Support\Facades\Route::has('admin.impersonation.start'))

                        <form method="POST" action="{{ route('admin.impersonation.start', $garage) }}">
                            @csrf

                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2
                                       text-xs font-semibold text-white hover:bg-indigo-700">

                                <x-lucide-log-in class="w-4 h-4" />
                                Login as Garage
                            </button>
                        </form>

                    @endif

                </div>


                @if($owner)
                    <p class="mt-2 text-xs text-slate-500">
                        Tip: Use this to reproduce issues (jobs, invoices, payments) without logging out.
                    </p>
                @endif

            </div>

        </div>


        {{-- =========================
             Partner Organizations
             ========================= --}}
        @php
            $selected = $garage->organizations()->orderBy('name')->get();
        @endphp

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Insurance & Corporate Partners</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        These partners will appear in the job payer dropdown for this garage.
                    </p>
                </div>
            </div>

            {{-- View mode --}}
            <div class="mt-4">
                @if($selected->isEmpty())
                    <p class="text-sm text-slate-500">No partners linked yet.</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($selected as $org)
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800">
                                {{ $org->name }}
                                <span class="font-normal text-slate-500">({{ ucfirst($org->type) }})</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Edit mode --}}
            <details class="mt-4">
                <summary class="cursor-pointer text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                    Edit partners
                </summary>

                <form method="POST"
                    action="{{ route('admin.admin.garages.organizations.update', $garage) }}"
                    class="mt-4 space-y-4">
                    @csrf

                    <div class="divide-y rounded-xl border border-slate-200 overflow-hidden">

                        @foreach(\App\Models\Organization::where('status','active')->orderBy('type')->orderBy('name')->get() as $org)

                            <label class="flex items-center justify-between gap-3 px-3 py-2
                                        hover:bg-slate-50 text-sm text-slate-700">

                                <div class="flex items-center gap-3">

                                    <input type="checkbox"
                                        name="organizations[]"
                                        value="{{ $org->id }}"
                                        class="rounded border-slate-300"
                                        @checked($garage->organizations->contains($org->id))>

                                    <span class="font-medium">
                                        {{ $org->name }}
                                    </span>

                                </div>


                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                    {{ ucfirst($org->type) }}
                                </span>

                            </label>

                        @endforeach

                    </div>


                    <div class="flex justify-end gap-2 pt-2">
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                            Save Partners
                        </button>
                    </div>
                </form>
            </details>
        </div>


        {{-- =========================
            Account Controls
            ========================= --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Account Controls</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Manage trial, activation and suspension for this garage.
                    </p>
                </div>
            </div>

            <div class="mt-5 divide-y divide-slate-100">

                {{-- Extend Trial --}}
                <div class="py-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Extend trial</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Adds days to the current trial period.
                        </p>
                    </div>

                    <form method="POST"
                        action="{{ route('admin.garages.extend-trial', $garage) }}"
                        class="flex items-center gap-2">
                        @csrf

                        <input type="number"
                            name="days"
                            value="7"
                            min="1"
                            max="60"
                            class="w-24 rounded-xl border-slate-200 text-sm">

                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2
                                    text-xs font-semibold text-white hover:bg-slate-800">
                            <x-lucide-notebook-pen class="w-4 h-4" />
                            Extend
                        </button>
                    </form>
                </div>


                {{-- Activate --}}
                <div class="py-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Activate (paid)</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Activates the garage for a paid period.
                        </p>
                    </div>

                    <form method="POST"
                        action="{{ route('admin.garages.activate', $garage) }}"
                        class="flex items-center gap-2">
                        @csrf

                        <input type="number"
                            name="days"
                            value="30"
                            min="1"
                            max="3650"
                            class="w-24 rounded-xl border-slate-200 text-sm">

                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2
                                    text-xs font-semibold text-white hover:bg-emerald-700">
                            <x-lucide-check-circle class="w-4 h-4" />
                            Activate
                        </button>
                    </form>
                </div>


                {{-- Suspend (Danger Zone) --}}
                <div class="py-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Suspend</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Blocks access entirely. Use only when necessary.
                        </p>
                    </div>

                    <form method="POST"
                        action="{{ route('admin.garages.suspend', $garage) }}"
                        onsubmit="return confirm('Suspend this garage? This will block access immediately.');">
                        @csrf

                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-3 py-2
                                    text-xs font-semibold text-white hover:bg-red-700">
                            <x-lucide-ban class="w-4 h-4" />
                            Suspend
                        </button>
                    </form>
                </div>

            </div>

        </div>


    </div>

</x-app-layout>
