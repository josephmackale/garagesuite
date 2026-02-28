<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (($user->role ?? null) === 'super_admin' || (bool)($user->is_super_admin ?? false)) {
        return redirect()->route('admin.dashboard');
        }


        // Prefer the same tenant key used everywhere else in your app
        $garageId = (int) ($user->garage_id ?? 0);
        $garage   = $user->garage ?? null;

        // Base queries (scoped)
        $jobQuery      = Job::query();
        $customerQuery = Customer::query();
        $vehicleQuery  = Vehicle::query();
        $invoiceQuery  = Invoice::query();

        if ($garageId > 0) {
            $jobQuery->where('garage_id', $garageId);
            $customerQuery->where('garage_id', $garageId);
            $vehicleQuery->where('garage_id', $garageId);
            $invoiceQuery->where('garage_id', $garageId);
        } else {
            // Safe no-garage fallback
            $jobQuery->whereRaw('1=0');
            $customerQuery->whereRaw('1=0');
            $vehicleQuery->whereRaw('1=0');
            $invoiceQuery->whereRaw('1=0');
        }

        // ---------------------------------------------------------
        // ✅ Job pipeline counts (REAL statuses)
        // ---------------------------------------------------------
        $countsByStatus = (clone $jobQuery)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        // Keep keys aligned to your JobController statuses:
        $pipeline = [
            'pending'     => (int) ($countsByStatus['pending'] ?? 0),
            'in_progress' => (int) ($countsByStatus['in_progress'] ?? 0),
            'completed'   => (int) ($countsByStatus['completed'] ?? 0),
            'cancelled'   => (int) ($countsByStatus['cancelled'] ?? 0),

            // Blade currently references these; keep them for now but they’ll be 0
            'awaiting_parts' => 0,
            'delivered'      => 0,
        ];

        // ---------------------------------------------------------
        // ✅ Top stats for dashboard cards
        // ---------------------------------------------------------
        $openJobs = (clone $jobQuery)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $startedToday = (clone $jobQuery)
            ->whereDate('created_at', today())
            ->count();

        $activeJobs = (clone $jobQuery)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $weekStart = Carbon::now()->startOfWeek();
        $completedThisWeek = (clone $jobQuery)
            ->where('status', 'completed')
            ->where('updated_at', '>=', $weekStart)
            ->count();

        $totalCustomers = (clone $customerQuery)->count();
        $totalVehicles  = (clone $vehicleQuery)->count();

        $recentJobs = (clone $jobQuery)
            ->with(['vehicle.customer'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentInvoices = (clone $invoiceQuery)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // ---------------------------------------------------------
        // ✅ MTD + TODAY (DATE-safe for issue_date)
        // ---------------------------------------------------------
        $now = now();

        $mtdStartDate = $now->copy()->startOfMonth()->toDateString();
        $todayDate    = $now->toDateString();

        // Issued invoices (exclude draft) - DATE-safe
        $mtdIssuedInvoices = (clone $invoiceQuery)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$mtdStartDate, $todayDate]);

        $todayIssuedInvoices = (clone $invoiceQuery)
            ->where('status', '!=', 'draft')
            ->whereDate('issue_date', $todayDate);

        // MTD totals (invoiced)
        $mtdInvoiced   = (float) (clone $mtdIssuedInvoices)->sum('total_amount');
        $todayInvoiced = (float) (clone $todayIssuedInvoices)->sum('total_amount');

        // ---------------------------------------------------------
        // ✅ CASH RECEIVED (Payments table) - accounting truth = paid_at
        // ---------------------------------------------------------
        $paymentsBase = Payment::query()
            ->whereHas('invoice', function ($q) use ($garageId) {
                $q->where('garage_id', $garageId);
            });

        // Paid (cash received): based on paid_at (unchanged)
        $mtdCashReceived = (float) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$mtdStartDate, $todayDate])
            ->sum('amount');

        $todayCashReceived = (float) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereDate('paid_at', $todayDate)
            ->sum('amount');

        // ---------------------------------------------------------
        // ✅ Payments Entered (ops/backfill truth) = created_at
        //    This is the fix that makes the dashboard "move" today
        //    even if paid_at is historical (like your backfill).
        // ---------------------------------------------------------
        $mtdPaymentsEntered = (float) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$mtdStartDate, $todayDate])
            ->sum('amount');

        $todayPaymentsEntered = (float) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereDate('created_at', $todayDate)
            ->sum('amount');

        $mtdPaymentsEnteredCount = (int) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$mtdStartDate, $todayDate])
            ->count();

        $todayPaymentsEnteredCount = (int) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereDate('created_at', $todayDate)
            ->count();

        $pendingPayments = (int) (clone $paymentsBase)
            ->where('status', 'pending')
            ->count();

        $failedPayments = (int) (clone $paymentsBase)
            ->whereIn('status', ['failed', 'cancelled'])
            ->count();

        // Credit = invoiced - cash received (simple MVP view)
        $mtdCredit   = max(0.0, $mtdInvoiced - $mtdCashReceived);
        $todayCredit = max(0.0, $todayInvoiced - $todayCashReceived);

        // Parts/Labour (prefer line_total, fallback to qty*unit_price)
        $mtdParts = (float) InvoiceItem::query()
            ->where('item_type', 'part')
            ->whereHas('invoice', function ($q) use ($garageId, $mtdStartDate, $todayDate) {
                $q->where('garage_id', $garageId)
                    ->where('status', '!=', 'draft')
                    ->whereBetween('issue_date', [$mtdStartDate, $todayDate]);
            })
            ->sum(DB::raw('COALESCE(line_total, (quantity * unit_price))'));

        $mtdLabour = (float) InvoiceItem::query()
            ->where('item_type', 'labour')
            ->whereHas('invoice', function ($q) use ($garageId, $mtdStartDate, $todayDate) {
                $q->where('garage_id', $garageId)
                    ->where('status', '!=', 'draft')
                    ->whereBetween('issue_date', [$mtdStartDate, $todayDate]);
            })
            ->sum(DB::raw('COALESCE(line_total, (quantity * unit_price))'));

        $todayParts = (float) InvoiceItem::query()
            ->where('item_type', 'part')
            ->whereHas('invoice', function ($q) use ($garageId, $todayDate) {
                $q->where('garage_id', $garageId)
                    ->where('status', '!=', 'draft')
                    ->whereDate('issue_date', $todayDate);
            })
            ->sum(DB::raw('COALESCE(line_total, (quantity * unit_price))'));

        $todayLabour = (float) InvoiceItem::query()
            ->where('item_type', 'labour')
            ->whereHas('invoice', function ($q) use ($garageId, $todayDate) {
                $q->where('garage_id', $garageId)
                    ->where('status', '!=', 'draft')
                    ->whereDate('issue_date', $todayDate);
            })
            ->sum(DB::raw('COALESCE(line_total, (quantity * unit_price))'));

        $mtdOrdersCompleted = (int) (clone $jobQuery)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$now->copy()->startOfMonth(), $now])
            ->count();

        $todayOrdersCompleted = (int) (clone $jobQuery)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$now->copy()->startOfDay(), $now])
            ->count();

        $mtdInvoicesCount   = (int) (clone $mtdIssuedInvoices)->count();
        $mtdAvgInvoiceValue = $mtdInvoicesCount > 0 ? ($mtdInvoiced / $mtdInvoicesCount) : 0.0;

        $todayInvoicesCount   = (int) (clone $todayIssuedInvoices)->count();
        $todayAvgInvoiceValue = $todayInvoicesCount > 0 ? ($todayInvoiced / $todayInvoicesCount) : 0.0;

        // ---------------------------------
        // 🚗 Vehicles IN / OUT (TODAY)
        // ---------------------------------
        $todayVehiclesIn = Job::where('garage_id', $garageId)
            ->whereDate('created_at', $todayDate)
            ->count();

        $todayVehiclesOut = Job::where('garage_id', $garageId)
            ->where('status', 'delivered') // change to 'completed' if needed
            ->whereDate('updated_at', $todayDate)
            ->count();

        // ---------------------------------------------------------
        // ✅ Revenue (last 30 days) - CASH RECEIVED (payments)
        // ---------------------------------------------------------
        $fromDate = now()->subDays(30)->toDateString();

        $revenue30d = (float) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereDate('paid_at', '>=', $fromDate)
            ->sum('amount');

        $paidCount30d = (int) (clone $paymentsBase)
            ->where('status', 'paid')
            ->whereDate('paid_at', '>=', $fromDate)
            ->count();

        // ---------------------------------------------------------
        // 📦 Inventory strip: Low Stock Items + Stock Value (With Tax)
        // ---------------------------------------------------------
        $inventoryBase = InventoryItem::where('garage_id', $garageId);

        $qtyCol = Schema::hasColumn('inventory_items', 'quantity') ? 'quantity'
            : (Schema::hasColumn('inventory_items', 'qty') ? 'qty'
                : (Schema::hasColumn('inventory_items', 'stock') ? 'stock'
                    : null));

        $reorderCol = Schema::hasColumn('inventory_items', 'reorder_level') ? 'reorder_level'
            : (Schema::hasColumn('inventory_items', 'min_qty') ? 'min_qty'
                : (Schema::hasColumn('inventory_items', 'reorder_point') ? 'reorder_point'
                    : null));

        $costCol = Schema::hasColumn('inventory_items', 'unit_cost') ? 'unit_cost'
            : (Schema::hasColumn('inventory_items', 'buy_price') ? 'buy_price'
                : (Schema::hasColumn('inventory_items', 'cost_price') ? 'cost_price'
                    : null));

        $taxCol = Schema::hasColumn('inventory_items', 'tax_rate') ? 'tax_rate'
            : (Schema::hasColumn('inventory_items', 'vat_rate') ? 'vat_rate'
                : null);

        $lowStockItems = 0;
        if ($qtyCol && $reorderCol) {
            $lowStockItems = (clone $inventoryBase)
                ->whereNotNull($reorderCol)
                ->where($reorderCol, '>', 0)
                ->whereColumn($qtyCol, '<=', $reorderCol)
                ->count();
        }

        $stockValueWithTax = 0.0;
        if ($qtyCol && $costCol) {
            $rows = (clone $inventoryBase)->get(array_filter([$qtyCol, $costCol, $taxCol]));

            $stockValueWithTax = (float) $rows->sum(function ($r) use ($qtyCol, $costCol, $taxCol) {
                $qty  = (float) ($r->$qtyCol ?? 0);
                $cost = (float) ($r->$costCol ?? 0);

                if (!$taxCol) return $qty * $cost;

                $tax = (float) ($r->$taxCol ?? 0);
                return $qty * $cost * (1 + ($tax / 100));
            });
        }

        logger()->info('DASHBOARD_CASH_PAYMENTS', [
            'auth_id'                 => auth()->id(),
            'garage_id'               => $garageId,
            'todayDate'               => $todayDate,
            'mtdStartDate'            => $mtdStartDate,

            // paid_at metrics (accounting truth)
            'todayCashReceived'       => $todayCashReceived,
            'mtdCashReceived'         => $mtdCashReceived,

            // created_at metrics (ops/backfill truth)
            'todayPaymentsEntered'    => $todayPaymentsEntered,
            'mtdPaymentsEntered'      => $mtdPaymentsEntered,

            'pendingPayments'         => $pendingPayments,
            'failedPayments'          => $failedPayments,
        ]);

        return view('dashboard', [
            'garage' => $garage,

            'activeJobs'        => $activeJobs,
            'completedThisWeek' => $completedThisWeek,
            'totalCustomers'    => $totalCustomers,
            'totalVehicles'     => $totalVehicles,
            'recentJobs'        => $recentJobs,
            'recentInvoices'    => $recentInvoices,
            'jobPipeline'       => $pipeline,

            'revenue30d'   => (float) ($revenue30d ?? 0),
            'paidCount30d' => (int)   ($paidCount30d ?? 0),

            // Blade already uses these names
            'openJobs'     => (int) ($openJobs ?? 0),
            'startedToday' => (int) ($startedToday ?? 0),

            // ✅ Inventory strip values must be TOP-LEVEL
            'lowStockItems'     => (int) $lowStockItems,
            'stockValueWithTax' => (float) $stockValueWithTax,

            // Optional (if your blade shows them)
            'todayVehiclesIn'  => (int) ($todayVehiclesIn ?? 0),
            'todayVehiclesOut' => (int) ($todayVehiclesOut ?? 0),

            // ✅ MTD payload for UI
            'mtd' => [
                'invoiced'          => round((float) ($mtdInvoiced ?? 0), 2),
                'paid'              => round((float) ($mtdCashReceived ?? 0), 2), // CASH RECEIVED (paid_at)
                'credit'            => round((float) ($mtdCredit ?? 0), 2),
                'parts'             => round((float) ($mtdParts ?? 0), 2),
                'labour'            => round((float) ($mtdLabour ?? 0), 2),
                'expense'           => 0.00,
                'orders_completed'  => (int) ($mtdOrdersCompleted ?? 0),
                'avg_invoice_value' => round((float) ($mtdAvgInvoiceValue ?? 0), 2),

                // Extra payment KPIs
                'payments_pending'        => (int) $pendingPayments,
                'payments_failed'         => (int) $failedPayments,

                // ✅ NEW: entered metrics (created_at)
                'payments_entered'        => round((float) $mtdPaymentsEntered, 2),
                'payments_entered_count'  => (int) $mtdPaymentsEnteredCount,
            ],

            // ✅ TODAY payload for UI
            'today' => [
                'invoiced'          => round((float) ($todayInvoiced ?? 0), 2),
                'paid'              => round((float) ($todayCashReceived ?? 0), 2), // CASH RECEIVED (paid_at)
                'credit'            => round((float) ($todayCredit ?? 0), 2),
                'parts'             => round((float) ($todayParts ?? 0), 2),
                'labour'            => round((float) ($todayLabour ?? 0), 2),
                'expense'           => 0.00,
                'orders_completed'  => (int) ($todayOrdersCompleted ?? 0),
                'avg_invoice_value' => round((float) ($todayAvgInvoiceValue ?? 0), 2),

                // Optional extras
                'vehicles_in'       => (int) ($todayVehiclesIn ?? 0),
                'vehicles_out'      => (int) ($todayVehiclesOut ?? 0),

                // ✅ NEW: entered metrics (created_at)
                'payments_entered'        => round((float) $todayPaymentsEntered, 2),
                'payments_entered_count'  => (int) $todayPaymentsEnteredCount,
            ],
        ]);
    }
}
