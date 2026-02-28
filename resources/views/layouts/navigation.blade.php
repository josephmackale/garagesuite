{{-- resources/views/layouts/navigation.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();

    $isAdminUser = ($user?->role === 'super_admin') || ((bool)($user?->is_super_admin ?? false));
    $isAdminArea = request()->is('admin/*');

    // Only treat as "admin" if admin routes actually exist
    $adminRoutesExist = \Route::has('admin.dashboard') || \Route::has('admin.settings.index');

    $isAdmin = $isAdminArea || ($isAdminUser && $adminRoutesExist);


    $homeRoute = $isAdmin
        ? (\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/'))
        : (\Route::has('dashboard') ? route('dashboard') : url('/'));

    $settingsRoute = $isAdmin
        ? (\Route::has('admin.settings.index') ? route('admin.settings.index') : ($homeRoute ?? url('/')))
        : (\Route::has('settings.home') ? route('settings.home') : (\Route::has('settings.index') ? route('settings.index') : ($homeRoute ?? url('/'))));

@endphp
@php
    $route = request()->route()?->getName();

    $pageMap = [
        'dashboard' => ['Garage overview', "Snapshot of what's happening in your workshop today."],

        'jobs.index'   => ['Jobs', 'Track and manage service jobs.'],
        'jobs.create'  => ['New Job', 'Create a new job card.'],
        'jobs.show'    => ['Job Details', 'View job details and progress.'],

        'inventory-items.index' => ['Inventory', 'Manage items and stock levels.'],

        'documents.index'     => ['Documents', 'Invoices, receipts, job cards and PDFs.'],
        'documents.invoices'  => ['Documents', 'Invoices'],
        'documents.job-cards' => ['Documents', 'Job Cards'],
        'documents.receipts'  => ['Documents', 'Receipts'],

        'sms-campaigns.index' => ['SMS Campaigns', 'Create and send customer messages.'],

        'settings.home'        => ['Settings', 'Manage your garage workspace configuration.'],
        'settings.branding'    => ['Settings', 'Branding & Documents'],
        'settings.preferences' => ['Settings', 'Preferences'],
        'billing.index'        => ['Settings', 'Billing'],

                // Customers
        'customers.index'  => ['Customers', 'Search and manage customers.'],
        'customers.create' => ['New Customer', 'Add a customer profile.'],
        'customers.show'   => ['Customer Details', 'View customer profile and vehicles.'],
        'customers.edit'   => ['Edit Customer', 'Update customer details.'],

        // Vehicles (optional, but same idea)
        'vehicles.index'  => ['Vehicles', 'Search and manage vehicles.'],
        'vehicles.create' => ['New Vehicle', 'Add a vehicle profile.'],
        'vehicles.show'   => ['Vehicle Details', 'View vehicle profile and service history.'],
        'vehicles.edit'   => ['Edit Vehicle', 'Update vehicle details.'],

    ];

    // Allow per-page override if you pass $pageTitle/$pageSubtitle from a view/controller
    $resolvedTitle = $pageTitle ?? ($pageMap[$route][0] ?? 'GarageSuite');
    $resolvedSub   = $pageSubtitle ?? ($pageMap[$route][1] ?? null);
@endphp

@php
    $garage = $user?->garage;

    $garageName = $garage?->name ?? 'Garage';
    $garageEmail = $user?->email ?? '';

    // Change this to your actual logo field if different
    $garageLogo = $garage?->logo_path
        ? asset('storage/'.$garage->logo_path)
        : asset('assets/branding/icon/garagesuite-icon-128.png');

    // Initials from garage name (max 2 letters)
    $garageInitials = collect(preg_split('/\s+/', trim($garageName)))
        ->filter()
        ->take(2)
        ->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))
        ->join('');

    // ========= Drawer Notification flags (safe defaults) =========
    $is_read_only = (bool)($is_read_only ?? ($garage?->is_read_only ?? false));
    $trial_ended_at = $trial_ended_at ?? ($garage?->trial_ended_at ?? null);
    $sms_not_configured = (bool)($sms_not_configured ?? false);

    $billingUrl = \Route::has('billing.index') ? route('billing.index') : $settingsRoute;
    $smsSetupUrl = $smsSetupUrl ?? $settingsRoute;
@endphp



<nav x-data="{ open: false }"
     class="sticky top-0 z-30 bg-white dark:bg-slate-950
            border-b border-gray-200 dark:border-slate-800
            shadow-[0_1px_0_rgba(0,0,0,0.04),0_4px_12px_rgba(0,0,0,0.06)]">
   
    {{-- ================= TOP BAR ================= --}}
    <div class="px-4 sm:px-6 lg:px-10">
        <div class="flex items-center h-16 gap-4 relative">

            {{-- Left: Admin badge only (no logo in topbar) --}}
            <div class="flex items-center gap-3 shrink-0">
                @if($isAdmin)
                    <span class="hidden sm:inline-flex items-center rounded-full border
                                border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px]
                                font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                        Super Admin
                    </span>
                @endif
            </div>

            {{-- Center: Dashboard shows Garage identity; other pages show title/subtitle --}}
            <div class="flex-1 min-w-0">
                @if(request()->routeIs('dashboard'))
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Garage logo (hard-clamped, always visible) --}}
                        <div class="relative h-9 w-9 shrink-0 rounded-xl ring-1 ring-slate-200 bg-white overflow-hidden">
                            <img
                                src="{{ $garageLogo }}"
                                alt="{{ $garageName }}"
                                loading="lazy"
                                class="absolute inset-0 !h-full !w-full object-contain"
                            />
                        </div>


                        <div class="min-w-0 leading-tight">
                            <div class="text-[15px] font-semibold text-slate-900 dark:text-slate-100 truncate">
                                {{ $garageName }}
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 truncate italic">
                                Where Precision Meets Performance
                            </div>

                        </div>
                    </div>
                @else
                    <div class="leading-tight">
                        <div class="text-base font-semibold text-slate-900 dark:text-slate-100 truncate">
                            {{ $resolvedTitle }}
                            @php
                                $garage = auth()->user()?->garage;
                            @endphp

                            @if(($garage?->is_insurance_partner ?? false))
                                <span
                                    class="ml-2 inline-flex items-center rounded-md bg-blue-100 px-2 py-1
                                        text-xs font-semibold text-blue-800"
                                >
                                    🛡️ Insurance Partner
                                </span>
                            @endif

                        </div>

                        @if($resolvedSub)
                            <div class="text-sm text-slate-500 dark:text-slate-400 truncate">
                                {{ $resolvedSub }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right: Dashboard initials dropdown only (clamped to viewport) --}}
            @if(request()->routeIs('dashboard'))
                <div class="hidden sm:flex items-center shrink-0">
                    <div class="relative" x-data="{ garageMenu:false }" @keydown.escape.window="garageMenu=false">

                        {{-- Avatar button --}}
                        <button type="button"
                                @click="garageMenu = !garageMenu"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full
                                    bg-emerald-600 text-white font-semibold shadow-sm
                                    ring-2 ring-white hover:brightness-95
                                    focus:outline-none focus:ring-2 focus:ring-emerald-300
                                    dark:ring-slate-900">
                            {{ $garageInitials ?: 'G' }}
                        </button>

                        {{-- Dropdown (polished) --}}
                        <div x-show="garageMenu"
                            x-cloak
                            @click.away="garageMenu=false"
                            x-transition.origin.top.right
                            class="absolute right-0 mt-2 w-[320px] max-w-[calc(100vw-1rem)]
                                    rounded-2xl border border-slate-200 bg-white shadow-xl
                                    dark:border-slate-800 dark:bg-slate-950 z-50 overflow-hidden">

                            {{-- little caret --}}
                            <div class="absolute -top-2 right-4 h-4 w-4 rotate-45 bg-white border-l border-t border-slate-200
                                        dark:bg-slate-950 dark:border-slate-800"></div>

                            <div class="p-4 pt-5">
                                <div class="flex items-start gap-3">
                                    <div class="h-11 w-11 rounded-full bg-emerald-600 text-white font-semibold
                                                flex items-center justify-center shrink-0 ring-2 ring-white dark:ring-slate-950">
                                        {{ $garageInitials ?: 'G' }}
                                    </div>

                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate leading-5">
                                            {{ $user->name ?? 'Account' }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ $user?->email ?? '' }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="mt-4 space-y-1">
                                    <a href="{{ $settingsRoute }}"
                                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5
                                            text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900
                                            dark:text-slate-200 dark:hover:bg-slate-900">
                                        <span class="grid place-items-center h-9 w-9 rounded-xl bg-slate-100 text-slate-600
                                                    group-hover:bg-white
                                                    dark:bg-slate-900 dark:text-slate-300 dark:group-hover:bg-slate-950">
                                            <x-lucide-settings class="h-4 w-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <div class="leading-5">Manage Garage Account</div>
                                            <div class="text-xs font-normal text-slate-500 dark:text-slate-400">
                                                Branding, preferences & billing
                                            </div>
                                        </div>
                                        <x-lucide-chevron-right class="ml-auto h-4 w-4 text-slate-300 group-hover:text-slate-400 dark:text-slate-600" />
                                    </a>
                                </div>
                            </div>

                            <div class="border-t border-slate-200 dark:border-slate-800 p-2">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full group flex items-center gap-3 rounded-xl px-3 py-2.5
                                                text-sm font-medium text-red-600 hover:bg-red-50
                                                dark:text-red-400 dark:hover:bg-red-950/30">
                                        <span class="grid place-items-center h-9 w-9 rounded-xl bg-red-50 text-red-600
                                                    dark:bg-red-950/30 dark:text-red-400">
                                            <x-lucide-log-out class="h-4 w-4" />
                                        </span>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


            {{-- Mobile hamburger --}}
            <div class="flex items-center sm:hidden shrink-0">
                <button @click="open = true"
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2
                            text-slate-600 shadow-sm hover:bg-slate-50 hover:text-slate-900
                            dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-900">
                    <x-lucide-menu class="h-5 w-5" />
                </button>
            </div>

        </div>
    </div>

    {{-- ================= MOBILE DRAWER ================= --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-40 flex sm:hidden"
         role="dialog" aria-modal="true">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/40" @click="open = false"></div>

        {{-- Drawer --}}
        <div class="relative z-50 w-64 max-w-[75%] bg-white h-full shadow-xl
                    flex flex-col dark:bg-slate-950">

            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <img
                        src="{{ asset('assets/branding/icon/garagesuite-icon-128.png') }}"
                        alt="GarageSuite"
                        class="h-7 w-auto"
                    >
                    <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                        Garage<span class="text-indigo-600">Suite</span>
                    </span>
                </div>

                <button @click="open = false"
                        class="p-2 rounded-md text-gray-500 hover:text-gray-700
                               hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Drawer nav --}}
            <div class="flex-1 overflow-y-auto">
                <nav class="px-2 py-3 space-y-1 text-sm">

                    {{-- ===== SUPER ADMIN ===== --}}
                    @if($isAdmin)

                        <a href="{{ route('admin.dashboard') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg font-medium
                                  {{ request()->routeIs('admin.dashboard')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-layout-dashboard class="w-4 h-4 mr-3" />
                            <span>Admin Dashboard</span>
                        </a>

                        <a href="{{ route('admin.garages.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('admin.garages.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-store class="w-4 h-4 mr-3" />
                            <span>Garages</span>
                        </a>

                        <a href="{{ route('admin.users.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('admin.users.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-users class="w-4 h-4 mr-3" />
                            <span>Users</span>
                        </a>

                        <a href="{{ route('admin.activity.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('admin.activity.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-list-checks class="w-4 h-4 mr-3" />
                            <span>Activity</span>
                        </a>

                        <a href="{{ route('admin.settings.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('admin.settings.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-settings class="w-4 h-4 mr-3" />
                            <span>Settings</span>
                        </a>

                    {{-- ===== GARAGE USER ===== --}}
                    @else

                        <a href="{{ route('dashboard') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg font-medium
                                  {{ request()->routeIs('dashboard')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-layout-dashboard class="w-4 h-4 mr-3" />
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('customers.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('customers.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-users class="w-4 h-4 mr-3" />
                            <span>Customers</span>
                        </a>

                        <a href="{{ route('vehicles.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('vehicles.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-car class="w-4 h-4 mr-3" />
                            <span>Vehicles</span>
                        </a>

                        <a href="{{ route('jobs.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('jobs.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-wrench class="w-4 h-4 mr-3" />
                            <span>Jobs</span>
                        </a>

                        <a href="{{ route('vault.index') }}" @click="open=false"
                        class="flex items-center px-3 py-2 rounded-lg
                                {{ request()->routeIs('vault.*')
                                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                    : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-image class="w-4 h-4 mr-3" />
                            <span>Photo Vault</span>
                        </a>

                        <a href="{{ route('inventory-items.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('inventory-items.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-boxes class="w-4 h-4 mr-3" />
                            <span>Inventory</span>
                        </a>

                        <a href="{{ route('sms-campaigns.index') }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('sms-campaigns.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-megaphone class="w-4 h-4 mr-3" />
                            <span>SMS Campaigns</span>
                        </a>

                        {{-- ✅ FIXED: settings goes to garage settings --}}
                        <a href="{{ $settingsRoute }}" @click="open=false"
                           class="flex items-center px-3 py-2 rounded-lg
                                  {{ request()->routeIs('settings.*')
                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                      : 'text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                            <x-lucide-settings class="w-4 h-4 mr-3" />
                            <span>Settings</span>
                        </a>

                    @endif
                </nav>

                {{-- ================= SIDEBAR NOTIFICATION CARD (MOBILE DRAWER) ================= --}}
                @if($is_read_only || $sms_not_configured)
                    <div class="px-3 pb-4">
                        <div class="rounded-2xl border p-3
                                    {{ $is_read_only
                                        ? 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20'
                                        : 'border-sky-200 bg-sky-50 dark:border-sky-900/40 dark:bg-sky-950/20' }}">

                            <div class="text-sm font-semibold
                                        {{ $is_read_only ? 'text-amber-900 dark:text-amber-200' : 'text-sky-900 dark:text-sky-200' }}">
                                {{ $is_read_only ? 'Read-only mode' : 'SMS not configured' }}
                            </div>

                            @if($is_read_only && !empty($trial_ended_at))
                                <div class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                    Trial ended {{ \Carbon\Carbon::parse($trial_ended_at)->format('d M Y, H:i') }}
                                </div>
                            @endif

                            <div class="mt-2 text-xs leading-relaxed
                                        {{ $is_read_only ? 'text-amber-800 dark:text-amber-300' : 'text-sky-800 dark:text-sky-300' }}">
                                {{ $is_read_only
                                    ? 'Creating, editing, and downloads are locked until you subscribe.'
                                    : 'Reminders won’t send until SMS is connected.' }}
                            </div>

                            <div class="mt-3">
                                @if($is_read_only)
                                    <a href="{{ $billingUrl }}" @click="open=false"
                                       class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Subscribe (M-PESA)
                                    </a>
                                @else
                                    <a href="{{ $smsSetupUrl }}" @click="open=false"
                                       class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Setup SMS
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                {{-- ================= END NOTIFICATION CARD ================= --}}

            </div>
            
            {{-- Drawer footer --}}
            <div class="border-t border-gray-100 dark:border-slate-800 px-4 py-3 text-sm">
                <div class="font-medium text-gray-700 dark:text-slate-200">
                    {{ $user->name ?? '' }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ $user->email ?? '' }}
                </div>

                <form method="POST" action="{{ route('logout') }}" class="pt-2">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-lg px-3 py-2 text-sm font-medium
                                   text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
