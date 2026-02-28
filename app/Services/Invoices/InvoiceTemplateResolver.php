<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;

class InvoiceTemplateResolver
{
    public function resolveForInvoice(Invoice $invoice, ?string $key = null): ?InvoiceTemplate
    {
        $garageId = (int) ($invoice->garage_id ?? 0);
        if ($garageId <= 0) return null;

        $key = $key ?: 'default';

        // 1) Garage exact key
        $tpl = InvoiceTemplate::query()
            ->where('garage_id', $garageId)
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if ($tpl) return $tpl;

        // 2) Garage default
        $tpl = InvoiceTemplate::query()
            ->where('garage_id', $garageId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($tpl) return $tpl;

        // 3) Global exact key
        $tpl = InvoiceTemplate::query()
            ->whereNull('garage_id')
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if ($tpl) return $tpl;

        // 4) Global default
        return InvoiceTemplate::query()
            ->whereNull('garage_id')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }
}
