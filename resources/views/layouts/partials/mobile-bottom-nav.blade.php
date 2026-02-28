{{-- resources/views/layouts/partials/mobile-bottom-nav.blade.php --}}
@php
    $isAdmin = auth()->user()?->is_super_admin ?? false;

    $activeClass = 'text-indigo-600';
    $idleClass   = 'text-gray-500';

    $is = fn ($pattern) => request()->routeIs($pattern);

    // Safe route helper: avoids crashing if a route isn't registered on some env
    $go = function (string $name, string $fallback = '#') {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return $fallback;
        }
    };

    // Some extra state
    $isImpersonating = session()->has('impersonator_id'); // adjust if your app uses a different session key
@endphp

{{-- Mobile/Tablet Bottom Nav --}}
<nav class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-200 lg:hidden pb-[env(safe-area-inset-bottom)]">
    <div class="max-w-screen-sm mx-auto">
        <div class="grid grid-cols-6 h-16">

            @if($isAdmin)
                {{-- Admin --}}
                <a href="{{ $go('admin.dashboard') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('admin.dashboard') ? $activeClass : $idleClass }}">
                    <x-lucide-layout-dashboard class="w-5 h-5" />
                    <span>Admin</span>
                </a>

                {{-- Garages --}}
                <a href="{{ $go('admin.garages.index') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('admin.garages.*') ? $activeClass : $idleClass }}">
                    <x-lucide-store class="w-5 h-5" />
                    <span>Garages</span>
                </a>

                {{-- Users --}}
                <a href="{{ $go('admin.users.index') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('admin.users.*') ? $activeClass : $idleClass }}">
                    <x-lucide-users class="w-5 h-5" />
                    <span>Users</span>
                </a>

                {{-- Activity --}}
                <a href="{{ $go('admin.activity.index') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('admin.activity.*') ? $activeClass : $idleClass }}">
                    <x-lucide-activity class="w-5 h-5" />
                    <span>Activity</span>
                </a>

                {{-- Settings --}}
                <a href="{{ $go('settings.home') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('settings.*') ? $activeClass : $idleClass }}">
                    <x-lucide-settings class="w-5 h-5" />
                    <span>Settings</span>
                </a>

                {{-- More --}}
                <div x-data="{ open:false }" class="relative">
                    <button type="button"
                            @click="open = !open"
                            class="w-full h-full flex flex-col items-center justify-center gap-1 text-[11px] {{ $idleClass }}">
                        <x-lucide-ellipsis class="w-5 h-5" />
                        <span>More</span>
                    </button>

                    <div x-show="open" x-transition
                         @click.outside="open=false"
                         class="absolute bottom-16 right-2 w-60 rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden">

                        <a href="{{ $go('billing.index') }}"
                           class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-credit-card class="w-4 h-4" />
                            <span>Billing</span>
                        </a>

                        <a href="{{ $go('profile.edit') }}"
                           class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-user class="w-4 h-4" />
                            <span>Profile</span>
                        </a>

                        @if($isImpersonating)
                            <form method="POST" action="{{ $go('admin.impersonate.stop') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-sm text-amber-700 hover:bg-amber-50">
                                    <x-lucide-user-x class="w-4 h-4" />
                                    <span>Stop impersonating</span>
                                </button>
                            </form>
                        @endif

                        <div class="h-px bg-gray-100"></div>

                        <form method="POST" action="{{ $go('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                                <x-lucide-log-out class="w-4 h-4" />
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>

            @else
                {{-- Home --}}
                <a href="{{ $go('dashboard') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('dashboard') ? $activeClass : $idleClass }}">
                    <x-lucide-layout-dashboard class="w-5 h-5" />
                    <span>Home</span>
                </a>

                {{-- Jobs --}}
                <a href="{{ $go('jobs.index') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('jobs.*') ? $activeClass : $idleClass }}">
                    <x-lucide-wrench class="w-5 h-5" />
                    <span>Jobs</span>
                </a>

                {{-- Docs --}}
                <a href="{{ $go('documents.index', $go('documents.invoices', '#')) }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('documents.*') ? $activeClass : $idleClass }}">
                    <x-lucide-file-text class="w-5 h-5" />
                    <span>Docs</span>
                </a>

                {{-- Stock --}}
                <a href="{{ $go('inventory-items.index') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('inventory-items.*') ? $activeClass : $idleClass }}">
                    <x-lucide-boxes class="w-5 h-5" />
                    <span>Stock</span>
                </a>

                {{-- Settings --}}
                <a href="{{ $go('settings.home') }}"
                   class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $is('settings.*') ? $activeClass : $idleClass }}">
                    <x-lucide-settings class="w-5 h-5" />
                    <span>Settings</span>
                </a>

                {{-- More --}}
                <div x-data="{ open:false }" class="relative">
                    <button type="button"
                            @click="open = !open"
                            class="w-full h-full flex flex-col items-center justify-center gap-1 text-[11px] {{ $idleClass }}">
                        <x-lucide-ellipsis class="w-5 h-5" />
                        <span>More</span>
                    </button>

                    <div x-show="open" x-transition
                        @click.outside="open=false"
                        class="absolute bottom-16 right-2 w-60 rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden">

                        {{-- Customers --}}
                        <a href="{{ $go('customers.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-users class="w-4 h-4" />
                            <span>Customers</span>
                        </a>

                        {{-- Vehicles --}}
                        <a href="{{ $go('vehicles.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-car class="w-4 h-4" />
                            <span>Vehicles</span>
                        </a>

                        {{-- Invoices --}}
                        <a href="{{ $go('invoices.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-receipt class="w-4 h-4" />
                            <span>Invoices</span>
                        </a>

                        {{-- Reminders (SMS Campaigns route) --}}
                        <a href="{{ $go('sms-campaigns.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-bell class="w-4 h-4" />
                            <span>Reminders</span>
                        </a>

                        {{-- Reports (future module) --}}
                        <a href="#"
                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-400 cursor-not-allowed">
                            <x-lucide-bar-chart-3 class="w-4 h-4" />
                            <span>Reports</span>
                        </a>

                        <div class="h-px bg-gray-100"></div>

                        {{-- Profile --}}
                        <a href="{{ $go('profile.edit') }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                            <x-lucide-user class="w-4 h-4" />
                            <span>Profile</span>
                        </a>

                        {{-- Logout --}}
                        <form method="POST" action="{{ $go('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                                <x-lucide-log-out class="w-4 h-4" />
                                <span>Logout</span>
                            </button>
                        </form>

                    </div>
                </div>
            @endif

        </div>
    </div>
</nav>
