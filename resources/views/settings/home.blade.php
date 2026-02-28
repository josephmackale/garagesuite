<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Settings</div>
                            <p class="mt-1 text-sm text-gray-600">
                                Configure documents, billing rules, and insurance workflows.
                            </p>
                        </div>

                        {{-- Optional: small “scope” pill --}}
                        <span class="inline-flex items-center gap-1 rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3a9 9 0 1 0 9 9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Dalima Works Settings
                        </span>
                    </div>
                </div>
            </div>

            {{-- Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Branding & Documents (ACTIVE) --}}
                <a href="{{ route('settings.branding') }}"
                   class="group relative rounded-2xl border border-gray-100 bg-white shadow-sm
                          hover:shadow-md hover:-translate-y-[1px] hover:border-gray-200
                          transition overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            {{-- Icon tile --}}
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-100
                                        flex items-center justify-center text-indigo-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                                          stroke="currentColor" stroke-width="1.7" />
                                    <path d="M8 15l2.5-3 2 2 3.5-5L20 12"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M8 9h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-base font-semibold text-gray-900 group-hover:text-indigo-700 transition">
                                        Branding & Documents
                                    </div>

                                    <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Active
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Logo, invoices, receipts, PDF footer and document layout.
                                </p>

                                <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">
                                    Open
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8"
                                              stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- subtle bottom accent --}}
                    <div class="h-1 bg-gradient-to-r from-indigo-500/40 via-indigo-400/20 to-transparent"></div>
                </a>

                {{-- Garage Profile (ACTIVE) --}}
                <a href="{{ route('settings.profile') }}"
                class="group relative rounded-2xl border border-gray-100 bg-white shadow-sm
                        hover:shadow-md hover:-translate-y-[1px] hover:border-gray-200
                        transition overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-100
                                        flex items-center justify-center text-indigo-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"
                                        stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M4 20a8 8 0 0 1 16 0"
                                        stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-base font-semibold text-gray-900 group-hover:text-indigo-700 transition">
                                        Garage Profile
                                    </div>

                                    <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Active
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Garage name, contacts, address and business details.
                                </p>

                                <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">
                                    Open
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-1 bg-gradient-to-r from-indigo-500/40 via-indigo-400/20 to-transparent"></div>
                </a>

                {{-- Users & Roles (SOON) --}}
                <div class="relative rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-100
                                        flex items-center justify-center text-amber-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z"
                                          stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M4 20a7 7 0 0 1 10-6"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                    <path d="M14.5 14.5a6 6 0 0 1 5.5 5.5"
                                          stroke="currentColor" stroke-width="1.7"
                                          stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-base font-semibold text-gray-900">
                                        Users & Roles
                                    </div>

                                    <span class="inline-flex items-center rounded-full border border-amber-100 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        Soon
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Invite staff and manage permissions.
                                </p>

                                <div class="mt-4 text-sm font-semibold text-gray-400">
                                    Coming next
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pointer-events-none absolute inset-0 bg-white/40"></div>
                    <div class="h-1 bg-gradient-to-r from-amber-500/30 via-amber-400/15 to-transparent"></div>
                </div>

                {{-- Billing (LOCKED) --}}
                <div class="relative rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200
                                        flex items-center justify-center text-gray-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7Z"
                                          stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M9 9h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M9 13h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-base font-semibold text-gray-900">
                                        Billing
                                    </div>

                                    <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                            <path d="M6 11h12v9a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-9Z" stroke="currentColor" stroke-width="1.7"/>
                                        </svg>
                                        Locked
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Subscription, invoices and plan management.
                                </p>

                                <div class="mt-4 text-sm font-semibold text-gray-400">
                                    Controlled by admin
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pointer-events-none absolute inset-0 bg-white/35"></div>
                    <div class="h-1 bg-gradient-to-r from-gray-500/20 via-gray-400/10 to-transparent"></div>
                </div>

                {{-- Preferences (ACTIVE) --}}
                <a href="{{ route('settings.preferences') }}"
                   class="group relative rounded-2xl border border-gray-100 bg-white shadow-sm
                          hover:shadow-md hover:-translate-y-[1px] hover:border-gray-200
                          transition overflow-hidden md:col-span-2">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-100
                                        flex items-center justify-center text-indigo-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 2v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M12 18v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M4.93 4.93l2.83 2.83" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M16.24 16.24l2.83 2.83" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M2 12h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M18 12h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M4.93 19.07l2.83-2.83" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M16.24 7.76l2.83-2.83" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="M12 8a4 4 0 1 0 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-base font-semibold text-gray-900 group-hover:text-indigo-700 transition">
                                        Preferences
                                    </div>

                                    <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Active
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Currency, taxes, numbering, and default system behavior.
                                </p>

                                <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">
                                    Open
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8"
                                              stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-1 bg-gradient-to-r from-indigo-500/40 via-indigo-400/20 to-transparent"></div>
                </a>

            </div>

            {{-- Footer hint --}}
            {{-- Operational footer --}}
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="px-6 py-4 flex items-center gap-3 text-sm text-gray-600">

                    <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M10.3 3.9 2.6 17.2a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"
                            stroke="currentColor" stroke-width="1.6"/>
                    </svg>

                    <span>
                        These settings control invoices, insurance claims, reports, and customer communication.
                        <span class="font-semibold text-gray-800">Update carefully.</span>
                    </span>

                </div>
            </div>


        </div>
    </div>
</x-app-layout>
