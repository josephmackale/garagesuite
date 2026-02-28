{{-- resources/views/admin/dashboard.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    Super Admin Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage garages, activations, and subscriptions.
                </p>
            </div>

            {{-- Primary admin action --}}
            <a href="#"
               class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                + Register Garage
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Top stats --}}
            <div class="grid gap-4 md:grid-cols-4">
                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Total garages
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $stats['total'] ?? 0 }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Active
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $stats['active'] ?? 0 }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Trial
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $stats['trial'] ?? 0 }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Expired / Suspended
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $stats['attention'] ?? 0 }}
                    </p>
                </div>
            </div>

            {{-- Garages needing attention --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Garages needing attention
                        </p>
                        <p class="text-xs text-slate-500">
                            Expiring soon, expired, or pending activation.
                        </p>
                    </div>

                    <a href="#"
                       class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                        View all →
                    </a>
                </div>

                @if(!empty($attention) && count($attention))
                    <div class="divide-y divide-slate-100">
                        @foreach($attention as $garage)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">
                                        {{ $garage->name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Status: {{ ucfirst($garage->status ?? 'unknown') }}
                                        @if($garage->subscription_expires_at)
                                            · Expires {{ \Illuminate\Support\Carbon::parse($garage->subscription_expires_at)->format('d M Y') }}
                                        @endif
                                    </p>
                                </div>

                                <a href="#"
                                   class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                                    Open →
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-slate-500">
                        No garages need attention right now.
                    </div>
                @endif
            </div>

            {{-- Admin quick notes --}}
            <div class="bg-indigo-50 rounded-2xl border border-indigo-100 p-4 text-xs text-indigo-900">
                <p class="font-semibold text-sm">Admin notes</p>
                <p class="mt-1">
                    This dashboard is operational. Next steps are wiring garage listing,
                    activation, suspension, and trial extensions.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
