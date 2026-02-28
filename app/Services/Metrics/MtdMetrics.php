<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

class MtdMetrics
{
    /**
     * Month-to-date metrics snapshot for a garage.
     *
     * Assumptions (LOCKED):
     * - invoices.issue_date is a DATE column
     * - invoices.paid_amount is cumulative per invoice
     * - invoice_items stores snapshot values (qty * unit_price)
     */
    public static function forGarage(int $garageId): array
    {
        // DATE-SAFE boundaries (Africa/Nairobi already applied at app level)
        $monthStart = now()->startOfMonth()->toDateString();
        $today      = now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | MTD INVOICES (ISSUED)
        |--------------------------------------------------------------------------
        */
        $mtdInvoicesCount = Invoice::where('garage_id', $garageId)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$monthStart, $today])
            ->count();

        $mtdInvoicedTotal = (float) Invoice::where('garage_id', $garageId)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$monthStart, $today])
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | MTD PAID (REVENUE FROM INVOICES)
        | paid_amount is cumulative per invoice (NOT per payment record)
        |--------------------------------------------------------------------------
        */
        $mtdPaidTotal = (float) Invoice::where('garage_id', $garageId)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$monthStart, $today])
            ->where('paid_amount', '>', 0)
            ->sum('paid_amount');

        /*
        |--------------------------------------------------------------------------
        | MTD CREDIT (OUTSTANDING CREATED THIS MONTH)
        |--------------------------------------------------------------------------
        */
        $mtdCreditTotal = max(
            0,
            $mtdInvoicedTotal - $mtdPaidTotal
        );

        /*
        |--------------------------------------------------------------------------
        | MTD PARTS & LABOUR (SNAPSHOT FROM INVOICE ITEMS)
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | - invoice_items.unit_price * quantity is trusted snapshot
        | - join filtered by invoice issue_date
        |--------------------------------------------------------------------------
        */
        $mtdPartsTotal = (float) DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.garage_id', $garageId)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.issue_date', [$monthStart, $today])
            ->where('invoice_items.item_type', 'part')
            ->sum(DB::raw('invoice_items.quantity * invoice_items.unit_price'));

        $mtdLabourTotal = (float) DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.garage_id', $garageId)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.issue_date', [$monthStart, $today])
            ->where('invoice_items.item_type', 'labour')
            ->sum(DB::raw('invoice_items.quantity * invoice_items.unit_price'));

        /*
        |--------------------------------------------------------------------------
        | RETURN SNAPSHOT
        |--------------------------------------------------------------------------
        */
        return [
            // Counts
            'mtd_invoices_count' => $mtdInvoicesCount,

            // Money
            'mtd_invoiced' => $mtdInvoicedTotal,
            'mtd_paid'     => $mtdPaidTotal,
            'mtd_credit'   => $mtdCreditTotal,

            // Breakdown
            'mtd_parts'    => $mtdPartsTotal,
            'mtd_labour'   => $mtdLabourTotal,

            // Meta
            'period_start' => $monthStart,
            'period_end'   => $today,
        ];
    }
}
