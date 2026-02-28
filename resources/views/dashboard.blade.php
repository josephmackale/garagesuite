{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>

    {{-- ✅ layout now provides max width + padding; keep only vertical spacing here --}}
    <div class="space-y-4">

        {{-- ===== INSURANCE SUMMARY (Step 5) ===== --}}
        @php
            $garage = auth()->user()?->garage;

            $insurancePartners = $garage?->insurance_partners ?? null;

            if (is_array($insurancePartners)) {
                $insurancePartners = implode(', ', array_filter($insurancePartners));
            }
        @endphp

        @if(($garage?->is_insurance_partner ?? false))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span
                        class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1
                            text-xs font-semibold text-blue-800"
                    >
                        🛡️ Insurance Partner
                    </span>

                    <span class="text-sm font-semibold text-slate-900">
                        Insurance Overview
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">

                    <div>
                        Mode:
                        <b>{{ $garage->insurance_mode ?? 'Hybrid' }}</b>
                    </div>

                    <div>
                        Partners:
                        <b>{{ $insurancePartners ?: '—' }}</b>
                    </div>

                    <div>
                        Pre-Auth:
                        <b>{{ ($garage->preauth_required ?? false) ? 'Yes' : 'No' }}</b>
                    </div>

                    <div>
                        Cash Jobs:
                        <b>{{ ($garage->allow_cash ?? true) ? 'Yes' : 'No' }}</b>
                    </div>

                    <div>
                        Insurance Jobs:
                        <b>{{ ($garage->allow_insurance_jobs ?? true) ? 'Enabled' : 'Disabled' }}</b>
                    </div>

                    <div>
                        Walk-ins:
                        <b>{{ ($garage->allow_walkin_jobs ?? true) ? 'Enabled' : 'Disabled' }}</b>
                    </div>

                </div>
            </div>
        @endif
        {{-- ===== /INSURANCE SUMMARY ===== --}}

        @php
            /**
             * IMPORTANT:
             * - This view is UI-only. It should NOT guess business logic.
             * - But it CAN safely "adapt" to whichever variable shape the controller provides.
             *
             * Supported shapes:
             * 1) Preferred: $mtd and $today arrays (from controller).
             * 2) Older: $mtdStats and $todayStats (fallback mapping below).
             */

            $money = function ($v) {
                $v = is_numeric($v) ? (float)$v : 0;
                return 'KES ' . number_format($v, 2);
            };

            // Normalize shape: if controller returns $mtd/$today, keep.
            // If controller returns $mtdStats/$todayStats, map them.
            if (!isset($mtd) && isset($mtdStats) && is_array($mtdStats)) {
                $mtd = $mtdStats;
            }
            if (!isset($today) && isset($todayStats) && is_array($todayStats)) {
                $today = $todayStats;
            }

            // Ensure arrays exist
            $mtd = is_array($mtd ?? null) ? $mtd : [];
            $today = is_array($today ?? null) ? $today : [];

            // Normalize missing keys (MTD)
            $mtd = array_merge([
                'invoiced' => 0, 'paid' => 0, 'credit' => 0,
                'parts' => 0, 'labour' => 0, 'expense' => 0,
                'orders_completed' => 0, 'avg_invoice_value' => 0,

                // ✅ Backfill KPI (created_at)
                'payments_entered' => 0,
                'payments_entered_count' => 0,
            ], $mtd);

            // Normalize missing keys (TODAY)
            $today = array_merge([
                'invoiced' => 0, 'paid' => 0, 'credit' => 0,
                'parts' => 0, 'labour' => 0, 'expense' => 0,
                'orders_completed' => 0, 'avg_invoice_value' => 0,
                'vehicles_in' => 0, 'vehicles_out' => 0,

                // ✅ Backfill KPI (created_at)
                'payments_entered' => 0,
                'payments_entered_count' => 0,
            ], $today);

            // ===== NEW: Dashboard "Attention Needed" (safe: uses variables if controller provides them) =====
            $attention = [
                'unpaid_invoices' => (int)($unpaidInvoicesCount ?? $unpaidInvoices ?? 0),
                'overdue_jobs'    => (int)($overdueJobsCount ?? $overdueJobs ?? 0),
                'low_stock'       => (int)($lowStockItems ?? 0),
            ];
            $attentionTotal = array_sum($attention);

            // ===== NEW: Safe URL helper (avoid hard failing if routes aren't defined) =====
            $safeUrl = function (string $routeName, string $fallback) {
                try {
                    return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
                } catch (\Throwable $e) {
                    return url($fallback);
                }
            };
        @endphp

        {{-- ===== TOP STRIP ===== --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Open Jobs</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ (int)($openJobs ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">Pending + In Progress</div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Started Today</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ (int)($startedToday ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">Jobs created today</div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Customers</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ (int)($totalCustomers ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">Total customers</div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Vehicles</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ (int)($totalVehicles ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">Total vehicles</div>
            </div>
        </div>

        {{-- ===== NEW: QUICK ACTIONS + ATTENTION NEEDED ===== --}}
        {{-- ✅ DROP-IN REPLACEMENT: Quick Actions + Attention Needed section ONLY
            Replace your whole "Quick Actions + Attention Needed" block with this.
        --}}

        @php
            // Safe route/url helper (works even if route names don't exist)
            $safeUrl = $safeUrl ?? function (string $routeName, string $fallback) {
                try {
                    return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
                } catch (\Throwable $e) {
                    return url($fallback);
                }
            };

            // "Attention" numbers (controller may or may not send these)
            $attention = $attention ?? [
                'unpaid_invoices' => (int)($unpaidInvoicesCount ?? $unpaidInvoices ?? 0),
                'overdue_jobs'    => (int)($overdueJobsCount ?? $overdueJobs ?? 0),
                'low_stock'       => (int)($lowStockItems ?? 0),
            ];
            $attentionTotal = (int)($attentionTotal ?? array_sum($attention));

            // ✅ Primary Quick Actions (use ONLY routes you confirmed exist)
            $quickActions = [
                [
                    'title' => 'New Job',
                    'desc'  => 'Start by adding/selecting customer',
                    // ✅ safest: go straight to Add Customer
                    'href'  => route('customers.create'),
                    'icon'  => 'M12 6v12m6-6H6',
                    'badge' => null,
                ],
                [
                    'title' => 'Invoices',
                    'desc'  => 'View issued invoices',
                    'href'  => route('invoices.index'),
                    'icon'  => 'M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2V6a2 2 0 0 1 2-2z',
                    'badge' => null,
                ],
                [
                    'title' => 'Add Customer',
                    'desc'  => 'New client',
                    'href'  => route('customers.create'),
                    'icon'  => 'M15 19a4 4 0 0 0-8 0m8 0h3m-3 0H9m3-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
                    'badge' => null,
                ],
                [
                    'title' => 'Reports',
                    'desc'  => 'Documents & PDFs',
                    'href'  => route('documents.index'),
                    'icon'  => 'M9 17v-6m4 6V7m4 10v-3',
                    'badge' => null,
                ],
            ];

            // ✅ Secondary actions (use ONLY routes you confirmed exist)
            $secondaryActions = [
                [
                    'title' => 'Add Vehicle',
                    'href'  => route('vehicles.create'),
                    'icon'  => 'M3 13l2-5a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 5M5 17a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm12 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0z',
                ],
                [
                    'title' => 'Stock In',
                    'href'  => route('inventory-items.create'),
                    'icon'  => 'M12 5v14m-7-7h14',
                ],
                [
                    'title' => 'SMS Campaigns',
                    'href'  => route('sms-campaigns.index'),
                    'icon'  => 'M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z',
                ],
            ];
        @endphp

        <div class="grid gap-3 lg:grid-cols-3">

            {{-- ✅ QUICK ACTIONS --}}
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:col-span-2">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Quick Actions</div>
                        <div class="text-xs text-slate-500">Do the common things faster</div>
                    </div>

                    <a href="{{ $safeUrl('jobs.index', '/jobs') }}"
                    class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                        View jobs →
                    </a>
                </div>

                {{-- Primary actions --}}
                <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($quickActions as $a)
                        <a href="{{ $a['href'] }}"
                        class="group rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm transition hover:-translate-y-[1px] hover:border-slate-300 hover:bg-slate-50">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white group-hover:bg-slate-100">
                                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-slate-700">
                                            <path d="{{ $a['icon'] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-800">{{ $a['title'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $a['desc'] }}</div>
                                    </div>
                                </div>

                                @if(!empty($a['badge']))
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">
                                        {{ $a['badge'] }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Secondary actions --}}
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($secondaryActions as $s)
                        <a href="{{ $s['href'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4 text-slate-600">
                                <path d="{{ $s['icon'] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ $s['title'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ✅ ATTENTION NEEDED --}}
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Attention Needed</div>
                        <div class="text-xs text-slate-500">Today’s alerts</div>
                    </div>

                    <div class="rounded-full border {{ $attentionTotal > 0 ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-slate-50 text-slate-700' }} px-2 py-1 text-xs font-semibold">
                        {{ $attentionTotal }}
                    </div>
                </div>

                <div class="mt-2 space-y-2 text-sm">

                    {{-- Unpaid invoices --}}
                    <a href="{{ route('invoices.index') }}"
                    class="block rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 hover:bg-slate-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-slate-800">Unpaid invoices</div>
                                <div class="text-xs text-slate-500">Need payment follow-up</div>
                            </div>
                            <div class="text-lg font-extrabold {{ $attention['unpaid_invoices'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                                {{ (int)$attention['unpaid_invoices'] }}
                            </div>
                        </div>
                        @if($attention['unpaid_invoices'] > 0)
                            <div class="mt-1 text-xs font-semibold text-amber-700">Review invoices →</div>
                        @endif
                    </a>

                    {{-- Overdue jobs --}}
                    <a href="{{ $safeUrl('jobs.index', '/jobs') }}"
                    class="block rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 hover:bg-slate-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-slate-800">Overdue jobs</div>
                                <div class="text-xs text-slate-500">Past expected date</div>
                            </div>
                            <div class="text-lg font-extrabold {{ $attention['overdue_jobs'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                                {{ (int)$attention['overdue_jobs'] }}
                            </div>
                        </div>
                        @if($attention['overdue_jobs'] > 0)
                            <div class="mt-1 text-xs font-semibold text-amber-700">Open jobs list →</div>
                        @endif
                    </a>

                    {{-- Low stock --}}
                    <a href="{{ $safeUrl('inventory-items.index', '/inventory-items') }}"
                    class="block rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 hover:bg-slate-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-slate-800">Low stock</div>
                                <div class="text-xs text-slate-500">At/below reorder</div>
                            </div>
                            <div class="text-lg font-extrabold {{ $attention['low_stock'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                                {{ (int)$attention['low_stock'] }}
                            </div>
                        </div>
                        @if($attention['low_stock'] > 0)
                            <div class="mt-1 text-xs font-semibold text-amber-700">Restock now →</div>
                        @endif
                    </a>

                    <div class="pt-1">
                        <a href="{{ route('documents.index') }}"
                        class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                            View reports →
                        </a>
                    </div>
                </div>
            </div>

        </div>



        {{-- ===== REVENUE / INVENTORY STRIP ===== --}}
        <div class="grid gap-3 lg:grid-cols-3">

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Revenue (Last 30 Days)</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ $money($revenue30d ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">
                    {{ (int)($paidCount30d ?? 0) }} payment(s)
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Low Stock Items</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ (int)($lowStockItems ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">At or below reorder level</div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-sm font-semibold text-slate-700">Stock Value (With Tax)</div>
                <div class="mt-1 text-2lg font-bold text-slate-900">{{ $money($stockValueWithTax ?? 0) }}</div>
                <div class="mt-1 text-xs text-slate-500">Approx. inventory valuation</div>
            </div>
        </div>

        {{-- ===== MTD + TODAY CARDS ===== --}}
        <div class="grid gap-3 lg:grid-cols-2">

            {{-- MTD --}}
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">Month to Date</div>
                        <div class="text-xs text-slate-500">Performance summary</div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-500">Invoiced</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ $money($mtd['invoiced'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-500">Avg Invoice</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ $money($mtd['avg_invoice_value'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div>
                        <div class="text-slate-500 font-semibold">Paid</div>
                        <div>{{ $money($mtd['paid'] ?? 0) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-slate-500 font-semibold">Credit</div>
                        <div>{{ $money($mtd['credit'] ?? 0) }}</div>
                    </div>
                </div>

                {{-- ✅ NEW: Payments Entered (MTD) --}}
                <div class="mt-1 text-xs text-slate-600">
                    <div class="text-slate-500 font-semibold">
                        Payments Entered (MTD)
                        <span class="text-slate-400 font-normal">
                            ({{ (int)($mtd['payments_entered_count'] ?? 0) }})
                        </span>
                    </div>
                    <div>{{ $money($mtd['payments_entered'] ?? 0) }}</div>
                </div>

                <div class="mt-2 grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-[11px] font-semibold text-slate-500">Parts</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $money($mtd['parts'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-[11px] font-semibold text-slate-500">Labour</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $money($mtd['labour'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-[11px] font-semibold text-slate-500">Orders</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ (int)($mtd['orders_completed'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            {{-- TODAY --}}
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">Today</div>
                        <div class="text-xs text-slate-500">Daily snapshot</div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-500">Invoiced</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ $money($today['invoiced'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-500">Avg Invoice</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ $money($today['avg_invoice_value'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div>
                        <div class="text-slate-500 font-semibold">Paid</div>
                        <div>{{ $money($today['paid'] ?? 0) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-slate-500 font-semibold">Credit</div>
                        <div>{{ $money($today['credit'] ?? 0) }}</div>
                    </div>
                </div>

                {{-- ✅ NEW: Payments Entered Today --}}
                <div class="mt-1 text-xs text-slate-600">
                    <div class="text-slate-500 font-semibold">
                        Payments Entered Today
                        <span class="text-slate-400 font-normal">
                            ({{ (int)($today['payments_entered_count'] ?? 0) }})
                        </span>
                    </div>
                    <div>{{ $money($today['payments_entered'] ?? 0) }}</div>
                </div>

                <div class="mt-2 grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-[11px] font-semibold text-slate-500">Parts</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $money($today['parts'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-[11px] font-semibold text-slate-500">Labour</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $money($today['labour'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-[11px] font-semibold text-slate-500">Orders</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ (int)($today['orders_completed'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div class="rounded-lg border border-slate-200 p-2">
                        <div class="text-slate-500 font-semibold">Vehicles In</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ (int)($today['vehicles_in'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-2 text-right">
                        <div class="text-slate-500 font-semibold">Vehicles Out</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ (int)($today['vehicles_out'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== RECENT ITEMS ===== --}}
        <div class="grid gap-3 lg:grid-cols-2">

            {{-- Recent Jobs --}}
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">Recent Jobs</div>
                        <div class="text-xs text-slate-500">Latest activity</div>
                    </div>
                </div>

                <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-slate-500">Job</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-slate-500">Customer</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse(($recentJobs ?? []) as $job)
                                <tr>
                                    <td class="px-3 py-1.5 font-semibold text-slate-700">
                                        #{{ $job->id }}
                                    </td>
                                    <td class="px-3 py-1.5 text-slate-600">
                                        {{ optional(optional($job->vehicle)->customer)->name ?? '—' }}
                                    </td>
                                    <td class="px-3 py-1.5 text-slate-600">
                                        {{ $job->status ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-3 text-center text-slate-500" colspan="3">No recent jobs.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Invoices --}}
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">Recent Invoices</div>
                        <div class="text-xs text-slate-500">Latest issued invoices</div>
                    </div>
                </div>

                <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-slate-500">Invoice</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-slate-500">Status</th>
                                <th class="px-3 py-1.5 text-right text-xs font-semibold text-slate-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse(($recentInvoices ?? []) as $inv)
                                <tr>
                                    <td class="px-3 py-1.5 font-semibold text-slate-700">
                                        {{ $inv->invoice_number ?? ('#'.$inv->id) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-slate-600">
                                        {{ $inv->payment_status ?? ($inv->status ?? '—') }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-semibold text-slate-700">
                                        {{ $money($inv->total_amount ?? 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-3 text-center text-slate-500" colspan="3">No recent invoices.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
