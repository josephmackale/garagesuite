<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugInvoiceTemplate extends Command
{
    protected $signature = 'debug:invoice-template {invoice_number=INV-00003}';
    protected $description = 'Debug invoice template resolution and items token rendering';

    public function handle(): int
    {
        $invoiceNumber = $this->argument('invoice_number');

        $inv = \App\Models\Invoice::with(['items','garage','customer','vehicle','job'])
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (!$inv) {
            $this->error("Invoice not found: {$invoiceNumber}");
            return 1;
        }

        $resolver = app(\App\Services\Invoices\InvoiceTemplateResolver::class);
        $renderer = app(\App\Services\Invoices\InvoiceTokenRenderer::class);

        $tpl = $resolver->resolveForInvoice($inv, 'default');

        $this->info("Invoice: {$inv->invoice_number} (items={$inv->items->count()})");

        if (!$tpl) {
            $this->warn("Template: NULL (fallback view invoices.pdf will be used)");
            return 0;
        }

        $this->info("Template ID: " . ($tpl->id ?? 'n/a'));
        $this->info("Template has token '{!!items.rows!!}': " . (str_contains($tpl->body_html, '{!!items.rows!!}') ? 'YES' : 'NO'));

        // Build rows the same way your controller does
        $rowsHtml = '';
        $maxRows = 14;
        $i = 0;

        foreach ($inv->items as $item) {
            $i++;
            $qty  = ($item->item_type === 'labour') ? '' : (string) ($item->quantity ?? '');
            $desc = e((string) ($item->description ?? ''));
            $unit = number_format((float) ($item->unit_price ?? 0), 2);
            $line = number_format((float) ($item->line_total ?? 0), 2);

            $rowsHtml .= "<tr><td class='c-qty'>{$qty}</td><td class='c-part'>{$desc}</td><td class='c-at'>{$unit}</td><td class='c-shs'>{$line}</td><td class='c-cts'></td></tr>";

            if ($i >= $maxRows) break;
        }

        $this->line("Rows HTML contains 'oil filter': " . (str_contains($rowsHtml, 'oil filter') ? 'YES' : 'NO'));

        $data = [
            'items' => ['rows' => $rowsHtml],
            'invoice' => [
                'subtotal' => number_format((float) ($inv->subtotal ?? 0), 2),
                'tax'      => number_format((float) ($inv->tax_amount ?? 0), 2),
                'total'    => number_format((float) ($inv->total_amount ?? 0), 2),
            ],
            'garage' => ['name' => $inv->garage?->name ?? 'Garage'],
            'customer' => ['name' => $inv->customer?->name ?? ''],
            'vehicle' => ['plate' => $inv->vehicle?->registration_number ?? ''],
        ];

        $rendered = $renderer->render($tpl->body_html, $data);

        $this->line("Rendered body contains 'oil filter': " . (str_contains($rendered, 'oil filter') ? 'YES' : 'NO'));
        $this->line("Rendered body contains '<tr': " . (str_contains($rendered, '<tr') ? 'YES' : 'NO'));

        // Show a short snippet around items.rows (if present)
        if (preg_match('/(.{0,200}items\.rows.{0,200})/si', $tpl->body_html, $m)) {
            $this->line("Template snippet near items.rows:");
            $this->line($m[0]);
        } else {
            $this->warn("No 'items.rows' reference found in template body_html.");
        }

        return 0;
    }
}
