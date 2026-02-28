@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;

    $user = Auth::user();

    // Admin detection (role OR boolean OR /admin/* URL)
    $isAdminUser = (bool)(
        ($user?->role ?? null) === 'super_admin' ||
        ($user?->is_super_admin ?? false)
    );
    $isAdminArea = request()->is('admin/*');
    $isAdmin     = $isAdminUser || $isAdminArea;

    /**
     * Safe route helper: returns a URL only if the route exists.
     * If not, returns a fallback URL (or '#').
     */
    $safeRoute = function (string $name, $params = [], string $fallback = '#') {
        return Route::has($name) ? route($name, $params) : $fallback;
    };

    // Home route (admin dashboard if exists, otherwise normal dashboard)
    $homeRoute = $isAdmin
        ? $safeRoute('admin.dashboard', [], Route::has('dashboard') ? route('dashboard') : '/')
        : $safeRoute('dashboard', [], '/');

    // Settings route:
    // - In admin: prefer admin.settings.index if it exists, else fall back to settings.home if it exists, else '#'
    // - In client: prefer settings.home if it exists, else '#'
    if ($isAdmin) {
        $settingsRoute = $safeRoute(
            'admin.settings.index',
            [],
            $safeRoute('settings.home', [], '#')
        );
    } else {
        $settingsRoute = $safeRoute('settings.home', [], '#');
    }

    // Open state for admin settings tree (only true if admin.settings.* exists AND we are on it)
    $adminSettingsOpen = $isAdmin && request()->routeIs('admin.settings.*') && Route::has('admin.settings.index');

    // Manage open/active state (customers + vehicles)
    $manageActive = request()->routeIs('customers.*') || request()->routeIs('vehicles.*');

    // FE polish: reusable classes
    $navBase  = "group relative flex items-center px-3 py-1.5 rounded-md transition-all duration-150";
    $navHover = "hover:bg-gray-50 hover:text-indigo-700 hover:translate-x-[1px]
                 dark:hover:bg-slate-800 dark:hover:text-indigo-300";

    $iconBase = "w-4 h-4 mr-3 transition-colors";

    // Active state (no ring, no before bar)
    $navActive = "bg-indigo-50 text-indigo-700 border border-indigo-200
                  dark:bg-indigo-950/40 dark:text-indigo-200 dark:border-indigo-900/40";

    $iconActive   = "text-indigo-600 dark:text-indigo-300";
    $iconInactive = "text-gray-500 group-hover:text-indigo-600 dark:text-slate-400 dark:group-hover:text-indigo-300";

    // -----------------------------
    // ✅ Sidebar notifications data (safe + non-breaking)
    // -----------------------------
    $garage = $user?->garage;

    // If your controller already passes $is_read_only / $trial_ended_at, keep them.
    // Otherwise, try common garage fields (won't crash if missing).
    $is_read_only = $is_read_only ?? (bool) data_get($garage, 'is_read_only', false);

    // Trial ended / subscription ended timestamps (pick whatever exists in your DB)
    $trial_ended_at = $trial_ended_at
        ?? data_get($garage, 'trial_ended_at')
        ?? data_get($garage, 'trial_ends_at');

    $subscription_ended_at = $subscription_ended_at
        ?? data_get($garage, 'subscription_ended_at')
        ?? data_get($garage, 'subscription_expires_at')
        ?? data_get($garage, 'subscription_ends_at');

    // SMS “setup required” (safe heuristic — adjust to your real fields later)
    $sms_sender_id = data_get($garage, 'sms_sender_id') ?? data_get($garage, 'sender_id');
    $sms_api_key   = data_get($garage, 'sms_api_key') ?? data_get($garage, 'sms_key');
    $sms_setup_required = $sms_setup_required ?? (!$isAdmin && empty($sms_sender_id) && empty($sms_api_key));
@endphp

{{-- ✅ Auto-expanding width by screen size --}}
<aside class="hidden lg:block w-64 xl:w-72 2xl:w-80 shrink-0 border-r border-gray-100 bg-white dark:bg-slate-950 dark:border-slate-800">


<div class="h-screen">
        <div class="h-full flex flex-col overflow-hidden">

            {{-- Brand/Header (neat, compact, no separator line) --}}
            <div class="px-4 pt-4 pb-3 shrink-0">
                <a href="{{ $homeRoute }}" class="flex items-center gap-2.5">
                    <img
                        src="{{ asset('assets/branding/icon/garagesuite-icon-128.png') }}"
                        class="h-10 w-10"
                        alt="GarageSuite"
                    >

                    <div class="min-w-0 leading-tight">
                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                            Garage<span class="text-indigo-600">Suite</span>
                        </div>

                        @if($isAdmin)
                            <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate">
                                Super Admin
                            </div>
                        @else
                            <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate">
                                Garage Workspace
                            </div>
                        @endif
                    </div>
                </a>
            </div>

            {{-- Nav (scrolls) --}}
            {{-- ✅ CHANGE: add min-h-0 so footer cards below can show reliably --}}
            <nav class="mt-3 flex-1 min-h-0 overflow-y-auto px-2 pb-6 space-y-1 text-sm">

                {{-- ===== ADMIN NAV ===== --}}
                @if($isAdmin)

                    <a href="{{ $safeRoute('admin.dashboard', [], $homeRoute) }}"
                       class="{{ $navBase }} {{ request()->routeIs('admin.dashboard') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                        <x-lucide-layout-dashboard class="{{ $iconBase }} {{ request()->routeIs('admin.dashboard') ? $iconActive : $iconInactive }}" />
                        <span>Admin Dashboard</span>
                    </a>

                    <a href="{{ $safeRoute('admin.garages.index', [], '#') }}"
                       class="{{ $navBase }} {{ request()->routeIs('admin.garages.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                        <x-lucide-store class="{{ $iconBase }} {{ request()->routeIs('admin.garages.*') ? $iconActive : $iconInactive }}" />
                        <span>Garages</span>
                    </a>

                    <a href="{{ $safeRoute('admin.users.index', [], '#') }}"
                       class="{{ $navBase }} {{ request()->routeIs('admin.users.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                        <x-lucide-users class="{{ $iconBase }} {{ request()->routeIs('admin.users.*') ? $iconActive : $iconInactive }}" />
                        <span>Users</span>
                    </a>

                    <a href="{{ $safeRoute('admin.activity.index', [], '#') }}"
                       class="{{ $navBase }} {{ request()->routeIs('admin.activity.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                        <x-lucide-list-checks class="{{ $iconBase }} {{ request()->routeIs('admin.activity.*') ? $iconActive : $iconInactive }}" />
                        <span>Activity</span>
                    </a>

                    <a href="{{ $safeRoute('admin.organizations.index', [], '#') }}"
                    class="{{ $navBase }} {{ request()->routeIs('admin.organizations.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                        <x-lucide-building-2 class="{{ $iconBase }} {{ request()->routeIs('admin.organizations.*') ? $iconActive : $iconInactive }}" />
                        <span>Organizations</span>
                    </a>
                    <a href="{{ $safeRoute('admin.organizations.index', [], '#') }}"
                       class="{{ $navBase }} {{ request()->routeIs('admin.organizations.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                           <x-lucide-building-2 class="{{ $iconBase }} {{ request()->routeIs('admin.organizations.*') ? $iconActive : $iconInactive }}" />
                           <span>Organizations</span>
                    </a>


                    {{-- ===== SETTINGS (Parent + Child: SMS) ===== --}}
                    <div class="pt-2 space-y-1">
                        <a href="{{ $settingsRoute }}"
                           class="{{ $navBase }} {{ $adminSettingsOpen ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-settings class="{{ $iconBase }} {{ $adminSettingsOpen ? $iconActive : $iconInactive }}" />
                            <span class="flex-1">Settings</span>

                            <x-lucide-chevron-right
                                class="w-4 h-4 transition-transform duration-150
                                       {{ $adminSettingsOpen
                                            ? 'rotate-90 text-indigo-600 dark:text-indigo-300'
                                            : 'text-gray-400 group-hover:text-gray-600 dark:text-slate-500 dark:group-hover:text-slate-200' }}" />
                        </a>

                        @if($adminSettingsOpen)
                            <div class="ml-6 pl-4 border-l border-indigo-100 dark:border-slate-800">
                                <a href="{{ $settingsRoute }}"
                                   class="{{ $navBase }} text-sm {{ request()->routeIs('admin.settings.*') ? $navActive : ('text-gray-600 dark:text-slate-400 '.$navHover) }}">
                                    <x-lucide-megaphone class="{{ $iconBase }} {{ request()->routeIs('admin.settings.*') ? $iconActive : 'text-gray-500 group-hover:text-indigo-600 dark:text-slate-500 dark:group-hover:text-indigo-300' }}" />
                                    <span>SMS</span>
                                </a>
                            </div>
                        @endif
                    </div>

                {{-- ===== GARAGE NAV ===== --}}
                @else

                    <div x-data="{ manageOpen: {{ $manageActive ? 'true' : 'false' }} }" class="space-y-1">

                        <a href="{{ $safeRoute('dashboard', [], '/') }}"
                           class="{{ $navBase }} {{ request()->routeIs('dashboard') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-layout-dashboard class="{{ $iconBase }} {{ request()->routeIs('dashboard') ? $iconActive : $iconInactive }}" />
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ $safeRoute('jobs.index', [], '#') }}"
                           class="{{ $navBase }} {{ request()->routeIs('jobs.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-wrench class="{{ $iconBase }} {{ request()->routeIs('jobs.*') ? $iconActive : $iconInactive }}" />
                            <span>Jobs</span>
                        </a>

                        <a href="{{ $safeRoute('inventory-items.index', [], '#') }}"
                           class="{{ $navBase }} {{ request()->routeIs('inventory-items.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-boxes class="{{ $iconBase }} {{ request()->routeIs('inventory-items.*') ? $iconActive : $iconInactive }}" />
                            <span>Inventory</span>
                        </a>

                        {{-- Manage (submenu parent) --}}
                        <button type="button"
                                @click="manageOpen = !manageOpen"
                                class="w-full {{ $navBase }} text-left {{ $manageActive ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-layers class="{{ $iconBase }} {{ $manageActive ? $iconActive : $iconInactive }}" />
                            <span class="flex-1 text-sm font-medium">Manage</span>

                            <x-lucide-chevron-right
                                class="w-4 h-4 transition-transform duration-150
                                    {{ $manageActive
                                            ? 'text-indigo-600 dark:text-indigo-300'
                                            : 'text-gray-400 group-hover:text-gray-600 dark:text-slate-500 dark:group-hover:text-slate-200' }}"
                                x-bind:class="manageOpen ? 'rotate-90' : ''" />
                        </button>

                        {{-- Manage children --}}
                        <div x-cloak
                             x-show="manageOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="ml-6 pl-4 border-l border-indigo-100 dark:border-slate-800 mt-1 mb-2 space-y-1">

                            <a href="{{ $safeRoute('customers.index', [], '#') }}"
                               class="{{ $navBase }} text-sm {{ request()->routeIs('customers.*') ? $navActive : ('text-gray-600 dark:text-slate-400 '.$navHover) }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-3
                                             {{ request()->routeIs('customers.*')
                                                  ? 'bg-indigo-600'
                                                  : 'bg-gray-300 group-hover:bg-indigo-400 dark:bg-slate-600 dark:group-hover:bg-indigo-300' }}"></span>
                                <span>Customers</span>
                            </a>

                            <a href="{{ $safeRoute('vehicles.index', [], '#') }}"
                               class="{{ $navBase }} text-sm {{ request()->routeIs('vehicles.*') ? $navActive : ('text-gray-600 dark:text-slate-400 '.$navHover) }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-3
                                             {{ request()->routeIs('vehicles.*')
                                                  ? 'bg-indigo-600'
                                                  : 'bg-gray-300 group-hover:bg-indigo-400 dark:bg-slate-600 dark:group-hover:bg-indigo-300' }}"></span>
                                <span>Vehicles</span>
                            </a>

                            {{-- Employees placeholder (no route yet) --}}
                            <a href="#"
                               onclick="return false;"
                               class="group flex items-center px-3 py-2 rounded-md transition-all duration-150 text-gray-400 cursor-not-allowed dark:text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full mr-3 bg-gray-200 dark:bg-slate-800"></span>
                                <span class="text-sm">Employees</span>
                            </a>
                        </div>

                        {{-- Documents --}}
                        <a href="{{ $safeRoute('documents.index', [], '#') }}"
                           class="{{ $navBase }} {{ request()->routeIs('documents.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-file-text class="{{ $iconBase }} {{ request()->routeIs('documents.*') ? $iconActive : $iconInactive }}" />
                            <span>Documents</span>
                        </a>

                        <a href="{{ $safeRoute('sms-campaigns.index', [], '#') }}"
                           class="{{ $navBase }} {{ request()->routeIs('sms-campaigns.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-megaphone class="{{ $iconBase }} {{ request()->routeIs('sms-campaigns.*') ? $iconActive : $iconInactive }}" />
                            <span>SMS Campaigns</span>
                        </a>


                        {{-- Photo Vault --}}
                        <a href="{{ route('vault.index') }}"
                        class="{{ $navBase }} {{ request()->routeIs('vault.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-image class="{{ $iconBase }} {{ request()->routeIs('vault.*') ? $iconActive : $iconInactive }}" />
                            <span>Photo Vault</span>
                        </a>

                        <a href="{{ $safeRoute('settings.home', [], '#') }}"
                           class="{{ $navBase }} {{ request()->routeIs('settings.*') ? $navActive : ('text-gray-700 dark:text-slate-300 '.$navHover) }}">
                            <x-lucide-settings class="{{ $iconBase }} {{ request()->routeIs('settings.*') ? $iconActive : $iconInactive }}" />
                            <span>Settings</span>
                        </a>

                    </div>
                @endif

            </nav>

            {{-- ✅ INSERT SIDEBAR NOTIFICATIONS HERE (RIGHT AFTER </nav>) --}}
            <div class="shrink-0 px-3 pb-4 space-y-3">

                {{-- Read-only / Subscription expired --}}
                @if(!empty($is_read_only) && $is_read_only)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm
                                dark:border-amber-900/40 dark:bg-amber-950/30">
                        <div class="flex items-start gap-2">
                            <div class="mt-0.5 text-amber-700 dark:text-amber-300">
                                <x-lucide-lock class="h-4 w-4" />
                            </div>

                            <div class="min-w-0">
                                <div class="font-semibold text-amber-900 dark:text-amber-100">
                                    Read-only mode
                                </div>

                                @if(!empty($subscription_ended_at))
                                    <div class="mt-0.5 text-xs text-amber-800 dark:text-amber-200">
                                        Subscription expired {{ \Carbon\Carbon::parse($subscription_ended_at)->format('d M Y') }}
                                    </div>
                                @elseif(!empty($trial_ended_at))
                                    <div class="mt-0.5 text-xs text-amber-800 dark:text-amber-200">
                                        Trial ended {{ \Carbon\Carbon::parse($trial_ended_at)->format('d M Y') }}
                                    </div>
                                @endif

                                <div class="mt-2 text-xs text-amber-900/90 dark:text-amber-100/90">
                                    Creating, editing, and downloads are locked until you subscribe.
                                </div>

                                <a href="{{ $safeRoute('billing.index', [], $settingsRoute) }}"
                                   class="mt-3 inline-flex w-full items-center justify-center rounded-xl
                                          bg-indigo-600 px-3 py-2 text-xs font-semibold text-white
                                          hover:bg-indigo-700">
                                    Subscribe (M-PESA)
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- SMS setup reminder --}}
                @if(!empty($sms_setup_required) && $sms_setup_required)
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 text-sm
                                dark:border-slate-800 dark:bg-slate-950">
                        <div class="flex items-start gap-2">
                            <div class="mt-0.5 text-slate-600 dark:text-slate-300">
                                <x-lucide-message-square-warning class="h-4 w-4" />
                            </div>

                            <div class="min-w-0">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">
                                    SMS setup needed
                                </div>
                                <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                    Configure sender ID / credentials to enable reminders & campaigns.
                                </div>

                                <a href="{{ $settingsRoute }}"
                                   class="mt-3 inline-flex w-full items-center justify-center rounded-xl
                                          border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800
                                          hover:bg-slate-100
                                          dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                                    Open Settings
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
            {{-- ✅ App Version (sticky bottom) --}}
            <div class="shrink-0 mt-auto px-3 py-2 border-t border-slate-200 dark:border-slate-800">
                <div class="text-center text-[11px] font-medium text-slate-400 dark:text-slate-500 truncate">
                    GarageSuite v{{ config('app.version', '1.0.0') }}
                </div>
            </div>


        </div>
    </div>
</aside>
