<?php

namespace App\Http\Controllers;

use App\Mail\InvoicePdfMail;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Job;
use App\Support\Activity;
use App\Support\DocumentPath;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Browsershot\Browsershot;
use App\Services\Invoices\InvoiceTemplateResolver;
use App\Services\Invoices\InvoiceTokenRenderer;

class InvoiceController extends Controller
{
    /**
     * List invoices for current garage.
     */
    public function index()
    {
        $garageId = Auth::user()->garage_id;

        $invoices = Invoice::with(['customer', 'vehicle', 'job'])
            ->where('garage_id', $garageId)
            ->orderByDesc('issue_date')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show a single invoice.
     */
    public function show(Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        $invoice->load(['customer', 'vehicle', 'job', 'items', 'garage']);

        // ✅ Only build preview HTML when a DB template exists.
        // If no template exists, let Blade render invoices._document normally.
        $resolver = app(\App\Services\Invoices\InvoiceTemplateResolver::class);
        $tpl = $resolver->resolveForInvoice($invoice, 'default');

        $invoicePreviewHtml = null;

        if ($tpl) {
            // Build preview HTML using SAME logic as PDF (garage template -> fallback)
            $fullHtml = $this->renderInvoicePdfHtml($invoice);

            // Extract <body> content so we can embed inside the UI page cleanly
            $invoicePreviewHtml = $fullHtml;
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $fullHtml, $m)) {
                $invoicePreviewHtml = $m[1];
            }
        }

        return view('invoices.show', [
            'invoice' => $invoice,
            'invoicePreviewHtml' => $invoicePreviewHtml,
        ]);
    }

    private function browsershotPdf(string $html): \Spatie\Browsershot\Browsershot
    {
        return \Spatie\Browsershot\Browsershot::html($html)
            ->setNodeBinary('/home/iwebgarage/.nvm/versions/node/v20.19.6/bin/node')
            ->setNpmBinary('/home/iwebgarage/.nvm/versions/node/v20.19.6/bin/npm')
            ->setChromePath('/usr/bin/google-chrome-stable')
            ->noSandbox()

            // 🔥 THIS is what fixes the centered-A4 issue
            ->emulateMedia('print')
            ->windowSize(1400, 2000)
            ->preferCssPageSize()

            ->format('A4')
            ->scale(1)
            ->showBackground()
            ->margins(12, 12, 12, 12)

            ->waitUntilNetworkIdle()
            ->timeout(120);
    }


    /**
     * Create an invoice from a job (one invoice per job).
     * ✅ Creates line items per job part (not a single "Parts" summary line).
     * ✅ Adds a single Labour line (optional).
     * ✅ Recalculates totals from invoice_items.
     *
     * 🔒 Insurance Gate:
     * - If job is insurance => must be approval_status=approved AND LPO must exist before invoice can be generated.
     */
    public function storeFromJob(Job $job)
    {
        $garageId = Auth::user()->garage_id;

        if ((int) $job->garage_id !== (int) $garageId) {
            abort(403);
        }

        // ✅ HARD RULE: only completed jobs can generate invoices
        if (($job->status ?? null) !== 'completed') {
            return back()->withErrors([
                'invoice' => 'You can only create an invoice after the job is marked as Completed.',
            ]);
        }

        // 🔒 INSURANCE ENFORCEMENT (approval + LPO required before invoicing)
        if (($job->payer_type ?? null) === 'insurance') {
            $gate = app(\App\Services\InsuranceGate::class);
            $r = $gate->canInvoice($job);

            if (!($r['ok'] ?? false)) {
                return back()->withErrors([
                    'invoice' => $r['reason'] ?? 'Cannot generate invoice for this insurance job yet.',
                ]);
            }
        }


        // Load latest job parts
        $job->load(['partItems']);

        // We'll return: [$invoice, $event, $meta]
        [$invoice, $event, $meta] = DB::transaction(function () use ($job, $garageId) {

            $event = null;
            $meta  = [];

            // Find existing invoice for same job
            $existing = Invoice::where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {

                // If not draft, do not change it
                if (($existing->status ?? 'draft') !== 'draft') {
                    return [$existing, null, []];
                }

                // Draft invoice: rebuild snapshot from job
                $existing->items()->delete();

                // Rebuild items (Labour + Parts)
                if ((float) $job->labour_cost > 0) {
                    $existing->items()->create([
                        'item_type'   => 'labour',
                        'description' => 'Labour',
                        'quantity'    => 1,
                        'unit_price'  => round((float) $job->labour_cost, 2),
                        'line_total'  => round((float) $job->labour_cost, 2),
                    ]);
                }

                foreach ($job->partItems as $pi) {
                    $desc = trim((string) ($pi->description ?? ''));
                    $qty  = (float) ($pi->quantity ?? 0);
                    $unit = (float) ($pi->unit_price ?? 0);
                    $lt   = (float) ($pi->line_total ?? ($qty * $unit));

                    if ($desc === '' && $lt <= 0) {
                        continue;
                    }

                    $existing->items()->create([
                        'item_type'   => 'part',
                        'description' => $desc !== '' ? $desc : 'Part',
                        'quantity'    => $qty > 0 ? $qty : 1,
                        'unit_price'  => round($unit, 2),
                        'line_total'  => round($lt, 2),
                    ]);
                }

                // Recalculate totals after rebuild
                $this->recalculateTotals($existing);
                $existing->refresh();

                $event = 'Updated draft invoice from job';
                $meta  = [
                    'job_id'          => $job->id,
                    'invoice_number'  => $existing->invoice_number,
                    'subtotal'        => (string) $existing->subtotal,
                    'tax'             => (string) $existing->tax_amount,
                    'total'           => (string) $existing->total_amount,
                    'items_count'     => $existing->items()->count(),
                ];

                return [$existing, $event, $meta];
            }

            // -----------------------------
            // No existing invoice: create it
            // -----------------------------

            // ✅ Collision-proof invoice numbering per garage using garages.invoice_sequence
            // Requires: garages.invoice_sequence (int) default 0
            $seq = DB::table('garages')
                ->where('id', $garageId)
                ->lockForUpdate()
                ->value('invoice_sequence');

            $seq = (int) ($seq ?? 0);
            $seq++;

            DB::table('garages')
                ->where('id', $garageId)
                ->update(['invoice_sequence' => $seq]);

            $invoiceNumber = 'INV-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

            $today = now()->toDateString();

            $invoice = Invoice::create([
                'garage_id'      => $garageId,
                'job_id'         => $job->id,
                'customer_id'    => $job->customer_id,
                'vehicle_id'     => $job->vehicle_id,
                'invoice_number' => $invoiceNumber,
                'issue_date'     => $today,
                'due_date'       => null,
                'status'         => 'draft',
                'payment_status' => 'unpaid',
                'paid_amount'    => 0,
                'subtotal'       => 0,
                'tax_rate'       => 0, // will be set during recalc
                'tax_amount'     => 0,
                'total_amount'   => 0,
                'currency'       => 'KES',
            ]);

            // Labour item
            if ((float) $job->labour_cost > 0) {
                $invoice->items()->create([
                    'item_type'   => 'labour',
                    'description' => 'Labour',
                    'quantity'    => 1,
                    'unit_price'  => round((float) $job->labour_cost, 2),
                    'line_total'  => round((float) $job->labour_cost, 2),
                ]);
            }

            // Parts items
            foreach ($job->partItems as $pi) {
                $desc = trim((string) ($pi->description ?? ''));
                $qty  = (float) ($pi->quantity ?? 0);
                $unit = (float) ($pi->unit_price ?? 0);
                $lt   = (float) ($pi->line_total ?? ($qty * $unit));

                if ($desc === '' && $lt <= 0) {
                    continue;
                }

                $invoice->items()->create([
                    'item_type'   => 'part',
                    'description' => $desc !== '' ? $desc : 'Part',
                    'quantity'    => $qty > 0 ? $qty : 1,
                    'unit_price'  => round($unit, 2),
                    'line_total'  => round($lt, 2),
                ]);
            }

            $this->recalculateTotals($invoice);
            $invoice->refresh();

            $event = 'Generated invoice from job';
            $meta  = [
                'job_id'          => $job->id,
                'invoice_number'  => $invoice->invoice_number,
                'subtotal'        => (string) $invoice->subtotal,
                'tax'             => (string) $invoice->tax_amount,
                'total'           => (string) $invoice->total_amount,
                'items_count'     => $invoice->items()->count(),
            ];

            return [$invoice, $event, $meta];
        });

        // ✅ Audit log (only if we actually created/updated)
        if ($event) {
            Activity::log($event, $invoice, $meta);
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice generated / updated from job.');
    }


    /**
     * Render invoice PDF HTML and inline Vite-built CSS.
     * ✅ Garage-specific template override (with fallback to global)
     * ✅ Keeps Vite CSS inlining (pixel-perfect Browsershot)
     * ✅ Supports DB-template CSS on UI preview (injects <style data-invoice-template> into <body>)
     * ✅ Supports items table rows via token: {!!items.rows!!}
     */
    public function renderInvoicePdfHtml(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'vehicle', 'job', 'items', 'garage']);
        $format = request()->query('format', 'a5'); // 'a5' | 'a4'
        $format = in_array($format, ['a5','a4'], true) ? $format : 'a5';

        // ---------------------------------------
        // 1) Build base HTML (garage template or fallback)
        // ---------------------------------------
        $resolver = app(\App\Services\Invoices\InvoiceTemplateResolver::class);
        $renderer = app(\App\Services\Invoices\InvoiceTokenRenderer::class);

        // v1: always 'default' template key
        $tpl = $resolver->resolveForInvoice($invoice, 'default');

        // helper to inject template css into <body> so UI embedding keeps template styling
        $injectCssIntoBody = function (string $html, ?string $templateCss): string {
            $templateCss = trim((string) $templateCss);
            if ($templateCss === '') return $html;

            $styleBlock = "<style data-invoice-template>\n{$templateCss}\n</style>\n";

            // Prefer inserting right after <body ...>
            if (preg_match('/<body\b[^>]*>/i', $html)) {
                return preg_replace('/(<body\b[^>]*>)/i', '$1' . "\n" . $styleBlock, $html, 1);
            }

            // Fallback: just prepend
            return $styleBlock . $html;
        };

        if ($tpl) {
            $garage = $invoice->garage;
            if (!$garage) {
                throw new \RuntimeException('Invoice has no garage linked.');
            }

            // Build fixed "book style" rows html for DB templates
            $rowsHtml = '';

            $items = $invoice->items->sortBy(fn ($it) => ($it->item_type === 'labour') ? 1 : 0);

            foreach ($items as $item) {
                $qty  = ($item->item_type === 'labour') ? '' : (string) ($item->quantity ?? '');
                $desc = e((string) ($item->description ?? ''));

                $unit = number_format((float) ($item->unit_price ?? 0), 0);
                $line = number_format((float) ($item->line_total ?? 0), 0);

                $rowsHtml .= "
                    <tr>
                        <td class='c-qty'>{$qty}</td>
                        <td class='c-part'>{$desc}</td>
                        <td class='c-at'>{$unit}</td>
                        <td class='c-amt'>{$line}</td>
                    </tr>
                ";

            }

            $logoDataUri = '';

            if (!empty($garage->logo_path)) {
                $abs = public_path('storage/' . $garage->logo_path);

                if (is_file($abs)) {
                    $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($abs));
                }
            }

            \Log::info('INVOICE LOGO DEBUG', [
                'logo_path' => $garage->logo_path,
                'abs_path'  => $abs ?? null,
                'exists'    => isset($abs) ? file_exists($abs) : false,
                'size'      => isset($abs) && file_exists($abs) ? filesize($abs) : 0,
                'uri_len'   => strlen($logoDataUri),
            ]);

            $data = [
                'garage' => [
                    'name'    => $garage->name ?? ($garage->label ?? 'Garage'),
                    'phone'   => $garage->phone ?? '',
                    'phone2'  => $garage->phone2 ?? ($garage->alt_phone ?? ''),   // ✅ alt number
                    'phone3'  => $garage->phone3 ?? '',                            // ✅ optional 3rd
                    'email'   => $garage->email ?? '',
                    'kra_pin' => $garage->kra_pin ?? ($garage->pin ?? ''),        // ✅ KRA PIN
                    'address' => $garage->address ?? '',

                    // ✅ must match the DB token {garage.logo_src}
                    'logo_src' => $logoDataUri,

                    // ✅ Logo support for DB invoice templates
                    'logo_data_uri' => $logoDataUri,
                ],


                'invoice' => [
                    'number'   => $invoice->invoice_number ?? (string) $invoice->id,
                    'issue'    => optional($invoice->issue_date)->format('Y-m-d'),
                    'due'      => optional($invoice->due_date)->format('Y-m-d'),
                    'status'   => $invoice->status ?? 'draft',
                    'currency' => $invoice->currency ?? 'KES',
                    'lpo_number' => (string) ($invoice->lpo_number ?? ''),
                    'subtotal' => number_format((float) ($invoice->subtotal ?? 0), 2),
                    'tax_rate' => rtrim(rtrim(number_format((float) ($invoice->tax_rate ?? 0), 2, '.', ''), '0'), '.'),
                    'tax'      => number_format((float) ($invoice->tax_amount ?? 0), 2),   // ✅ ADD THIS
                    'total'    => number_format((float) ($invoice->total_amount ?? 0), 2),

                    'paid'     => number_format((float) ($invoice->paid_amount ?? 0), 2),
                    'balance'  => number_format((float) ($invoice->balance ?? 0), 2),
                ],

                'customer' => [
                    'name'  => $invoice->customer?->name ?? '',
                    'phone' => $invoice->customer?->phone ?? '',
                    'email' => $invoice->customer?->email ?? '',
                ],
                'vehicle' => [
                    'plate' => $invoice->vehicle?->registration_number
                        ?? $invoice->vehicle?->plate_number
                        ?? '',
                    'make'  => $invoice->vehicle?->make ?? '',
                    'model' => $invoice->vehicle?->model ?? '',
                    'year'  => $invoice->vehicle?->year ?? '',
                    'vin'   => $invoice->vehicle?->vin ?? '',
                ],
                'items' => [
                    // use in template as: {!!items.rows!!}
                    'rows' => $rowsHtml,
                ],
            ];

            $bodyHtml = $renderer->render($tpl->body_html, $data);

            // Wrap DB template in a normal HTML doc with optional CSS
            $html = view('invoices.db-template', [
                'html'    => $bodyHtml,
                'css'     => $tpl->css,
                'invoice' => $invoice,
                'format'  => $format,
            ])->render();

            // ✅ also inject template CSS into <body> so UI embedding keeps styling
            $html = $injectCssIntoBody($html, $tpl->css);
        } else {
            // Fallback: your existing global default (includes invoices._document)
            $html = view('invoices.pdf', compact('invoice', 'format'))->render();
        }

        // ---------------------------------------
        // 2) Inline the compiled Vite CSS (unchanged from your method)
        // ---------------------------------------
        $manifestPath = public_path('build/manifest.json');

        $css = '';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);

            $key = 'resources/css/app.css';

            if (isset($manifest[$key])) {
                $cssFiles = [];

                if (!empty($manifest[$key]['css']) && is_array($manifest[$key]['css'])) {
                    $cssFiles = $manifest[$key]['css'];
                } elseif (!empty($manifest[$key]['file'])) {
                    $cssFiles = [$manifest[$key]['file']];
                }

                foreach ($cssFiles as $file) {
                    $p = public_path('build/' . ltrim($file, '/'));
                    if (file_exists($p)) {
                        $css .= "\n/* {$file} */\n" . file_get_contents($p) . "\n";
                    }
                }
            }
        }

        // Inject Vite CSS into <head> so PDF uses same Tailwind styles
        if ($css !== '') {
            $styleTag = "<style>\n{$css}\n</style>\n";
            if (stripos($html, '</head>') !== false) {
                $html = str_ireplace('</head>', $styleTag . '</head>', $html);
            } else {
                $html = $styleTag . $html;
            }
        }
        @file_put_contents(storage_path("app/tmp/invoice_pdf_{$invoice->id}.html"), $html);
        
        \Log::info('PDF_HTML_SOURCE', [
            'invoice_id' => $invoice->id,
            'used' => $tpl ? 'db-template' : 'fallback-pdf',
            'lpo_present' => !empty($invoice->lpo_number),
            'has_maxwidth' => str_contains($html, 'max-width') ? 'yes' : 'no',
            'has_margin_auto' => str_contains($html, 'margin:0 auto') ? 'yes' : 'no',
            'has_width_px' => (bool) preg_match('/width:\s*\d{3,4}px/i', $html),
        ]);

        return $html;
    }



    /**
     * Share invoice PDF via email (as attachment).
     * ✅ FIXED: generate PDF to disk (no big binary in RAM)
     */
    public function shareEmail(Request $request, Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $invoice->load(['customer', 'vehicle', 'job', 'items', 'garage']);

        // Same stored path every time (overwrite)
        $relativePath = DocumentPath::invoicePdfPath($invoice);
        $disk = 'public';

        // Render HTML from your invoice PDF Blade
        $html = $this->renderInvoicePdfHtml($invoice);

        dd([
                'has_items_rows_marker' => str_contains($fullHtml, '{{ITEMS_ROWS}}'),
                'has_items_table_marker' => str_contains($fullHtml, '{{ITEMS_TABLE}}'),
                'items_count' => $invoice->items->count(),
                'fullHtml_len' => strlen($fullHtml),
                'fullHtml_snip' => substr($fullHtml, 0, 800),
            ]);

        // Ensure directory exists
        Storage::disk($disk)->makeDirectory(dirname($relativePath));
        $absolutePath = Storage::disk($disk)->path($relativePath);

        // Generate via Chrome -> SAVE to disk
        $this->browsershotPdf($html)->savePdf($absolutePath);

        abort_unless(Storage::disk($disk)->exists($relativePath), 500, 'Invoice PDF was not saved');

        Mail::send([], [], function ($message) use ($validated, $invoice, $absolutePath) {
            $message->to($validated['email'])
                ->subject('Invoice ' . ($invoice->invoice_number ?? $invoice->id))
                ->html('Hello,<br><br>Please find your invoice attached.<br><br>Thank you.')
                ->attach($absolutePath, [
                    'as'   => 'Invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf',
                    'mime' => 'application/pdf',
                ]);
        });

        return back()->with('success', 'Invoice PDF sent to ' . $validated['email'] . ' (attached).');
    }

    /**
     * Update payment status / amount.
     *
     * ✅ FIXED RULE:
     * - Any payment (>0) sets paid_at = now() (so partial payments count for TODAY/MTD)
     * - Unpaid clears paid_at
     */
    public function updatePayment(Request $request, Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        // Optional business rule: don't allow payment updates while still Draft
        if (($invoice->status ?? null) === 'draft') {
            return back()->withErrors(['payment' => 'You must issue the invoice before recording payments.']);
        }

        $data = $request->validate([
            'paid_amount'    => ['required', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'in:unpaid,partial,paid'], // ignored; derived below
        ]);

        return DB::transaction(function () use ($invoice, $data) {

            $beforePaid   = round((float) ($invoice->paid_amount ?? 0), 2);
            $beforeStatus = (string) ($invoice->payment_status ?? 'unpaid');

            $total = round((float) ($invoice->total_amount ?? 0), 2);
            $paid  = round((float) $data['paid_amount'], 2);

            if ($paid > $total) {
                return back()->withErrors(['paid_amount' => 'Paid amount cannot exceed invoice total.']);
            }

            if ($paid <= 0) {
                $status = 'unpaid';
                $paid   = 0.00;
            } elseif ($paid < $total) {
                $status = 'partial';
            } else {
                $status = 'paid';
            }

            $invoice->paid_amount    = $paid;
            $invoice->payment_status = $status;

            // ✅ Revenue date anchor for dashboards
            if (Schema::hasColumn('invoices', 'paid_at')) {
                $invoice->paid_at = ($paid > 0) ? now() : null;
            }

            $invoice->save();

            // ✅ Audit log: payment recorded / status change
            Activity::log('Recorded payment', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'total'          => (string) $invoice->total_amount,
                'paid_before'    => (string) $beforePaid,
                'paid_after'     => (string) $invoice->paid_amount,
                'status_before'  => $beforeStatus,
                'status_after'   => (string) $invoice->payment_status,
            ]);

            if (($invoice->payment_status ?? null) === 'paid') {
                Activity::log('Invoice fully paid', $invoice, [
                    'invoice_number' => $invoice->invoice_number,
                    'total'          => (string) $invoice->total_amount,
                ]);
            }

            return back()->with('success', 'Payment updated successfully.');
        });
    }

    /**
     * Download invoice as PDF.
     * ✅ FIXED: save to disk via Browsershot::savePdf to avoid RAM exhaustion.
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        $garageId = auth()->user()->garage_id;

        $invoice->load(['customer', 'vehicle', 'job', 'items', 'garage']);

        $this->recalculateTotals($invoice);
        $invoice->refresh();
        $invoice->load(['customer', 'vehicle', 'job', 'items', 'garage']);

        $html = $this->renderInvoicePdfHtml($invoice);

        $fileName = 'Invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';

        // ✅ Canonical path + ensure directory exists
        $disk = 'public';
        $path = DocumentPath::invoicePdfPath($invoice);
        Storage::disk($disk)->makeDirectory(dirname($path));
        $absolutePath = Storage::disk($disk)->path($path);

        // ✅ Generate PDF to file (overwrite)
        $this->browsershotPdf($html)->savePdf($absolutePath);

        // ✅ Register/Update in documents table
        $size = null;
        try {
            $size = Storage::disk($disk)->size($path);
        } catch (\Throwable $e) {
            // not fatal
        }

        $doc = Document::withTrashed()
            ->where('garage_id', $garageId)
            ->where('document_type', 'invoice_pdf')
            ->where('documentable_type', \App\Models\Invoice::class)
            ->where('documentable_id', $invoice->id)
            ->first();

        if ($doc && $doc->trashed()) {
            $doc->restore();
        }

        if (!$doc) {
            $doc = new Document([
                'garage_id'         => $garageId,
                'document_type'     => 'invoice_pdf',
                'documentable_type' => \App\Models\Invoice::class,
                'documentable_id'   => $invoice->id,
                'version'           => 1,
            ]);
        } else {
            $doc->version = ((int) $doc->version) + 1;
        }

        $doc->name      = 'Invoice ' . ($invoice->invoice_number ?? $invoice->id);
        $doc->disk      = $disk;
        $doc->path      = $path;
        $doc->file_name = $fileName;
        $doc->mime_type = 'application/pdf';
        $doc->file_size = $size;

        $doc->save();

        // ✅ Return streamed file contents (small memory footprint)
        $headers = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ];

        return response()->file($absolutePath, $headers);
    }

    /**
     * Add a line item to an invoice.
     */
    public function addItem(Request $request, Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        if (($invoice->status ?? 'draft') !== 'draft') {
            return back()->withErrors(['items' => 'This invoice is issued and cannot be edited.']);
        }

        $data = $request->validate([
            'item_type'   => ['required', Rule::in(['labour', 'part', 'other'])],
            'description' => ['required', 'string', 'max:255'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $data['line_total'] = round(((float) $data['quantity']) * ((float) $data['unit_price']), 2);

        $invoice->items()->create($data);

        $this->recalculateTotals($invoice);

        return back()->with('success', 'Invoice item added.');
    }

    /**
     * Update an invoice line item.
     */
    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->authorizeGarage($invoice);

        if ((int) $item->invoice_id !== (int) $invoice->id) {
            abort(403);
        }

        if (($invoice->status ?? 'draft') !== 'draft') {
            return back()->withErrors(['items' => 'This invoice is issued and cannot be edited.']);
        }

        $data = $request->validate([
            'item_type'   => ['required', Rule::in(['labour', 'part', 'other'])],
            'description' => ['required', 'string', 'max:255'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $data['line_total'] = round(((float) $data['quantity']) * ((float) $data['unit_price']), 2);

        $item->update($data);

        $this->recalculateTotals($invoice);

        return back()->with('success', 'Invoice item updated.');
    }

    /**
     * Issue an invoice (locks editing). Optionally perform an action: email/whatsapp/pdf.
     */
    public function issue(Request $request, Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        // 🔒 INSURANCE ENFORCEMENT (approval + LPO required before issuing)
        // Applies ONLY to insurance jobs; individual/company unaffected.
        $job = \App\Models\Job::query()
            ->where('garage_id', auth()->user()->garage_id)
            ->with(['insuranceDetails']) // if you have this relationship; safe even if not used by gate
            ->find($invoice->job_id);

        if ($job && ($job->payer_type ?? null) === 'insurance') {
            $gate = app(\App\Services\InsuranceGate::class);
            $r = $gate->canInvoice($job);

            if (!($r['ok'] ?? false)) {
                return back()->withErrors([
                    'issue' => $r['reason'] ?? 'Cannot issue this invoice for an insurance job yet.',
                ]);
            }
        }

        $data = $request->validate([
            'action' => ['nullable', Rule::in(['email', 'whatsapp', 'pdf'])],
        ]);

        $justIssuedNow = false;

        if (($invoice->status ?? 'draft') === 'draft') {

            $this->recalculateTotals($invoice);
            $invoice->refresh();

            if ($invoice->items()->count() === 0) {
                return back()->withErrors(['issue' => 'Cannot send an empty invoice. Add items first.']);
            }

            $update = ['status' => 'sent'];

            if (Schema::hasColumn('invoices', 'issued_at')) {
                $update['issued_at'] = now();
            }

            $invoice->update($update);
            $invoice->refresh();

            $justIssuedNow = true;

            Activity::log('Issued invoice', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'subtotal'       => (string) $invoice->subtotal,
                'tax'            => (string) $invoice->tax_amount,
                'total'          => (string) $invoice->total_amount,
                'currency'       => $invoice->currency,
                'items_count'    => $invoice->items()->count(),
            ]);
        }

        $action = $data['action'] ?? null;

        if ($action === 'email') {
            $resp = $this->sendEmail($invoice);

            Activity::log('Sent invoice via email', $invoice, [
                'invoice_number'  => $invoice->invoice_number,
                'was_just_issued' => $justIssuedNow,
            ]);

            return $resp;
        }

        if ($action === 'whatsapp') {
            $resp = $this->sendWhatsApp($invoice);

            Activity::log('Sent invoice via WhatsApp', $invoice, [
                'invoice_number'  => $invoice->invoice_number,
                'was_just_issued' => $justIssuedNow,
            ]);

            return $resp;
        }

        if ($action === 'pdf') {
            return $this->pdf($invoice);
        }

        return back()->with('success', 'Invoice sent successfully.');
    }


    /**
     * Delete an invoice line item.
     */
    public function deleteItem(Invoice $invoice, InvoiceItem $item)
    {
        $this->authorizeGarage($invoice);

        if ((int) $item->invoice_id !== (int) $invoice->id) {
            abort(403);
        }

        if (($invoice->status ?? 'draft') !== 'draft') {
            return back()->withErrors(['items' => 'This invoice is issued and cannot be edited.']);
        }

        $item->delete();

        $this->recalculateTotals($invoice);

        return back()->with('success', 'Invoice item removed.');
    }

    public function receiptPdf(Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        $invoice->load(['customer', 'vehicle', 'garage']);

        $garageId = (int) $invoice->garage_id;
        $disk     = 'public';
        $path     = "garages/{$garageId}/receipts/receipt-{$invoice->id}.pdf";
        $filename = 'Receipt-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';

        Storage::disk($disk)->makeDirectory(dirname($path));
        $absolutePath = Storage::disk($disk)->path($path);

        try {
            // ✅ EXACT same HTML renderer as Invoice PDF
            $html = $this->renderInvoicePdfHtml($invoice);

            $this->browsershotPdf($html)->savePdf($absolutePath);

        } catch (\Throwable $e) {
            \Log::error('RECEIPT_PDF_FAILED', [
                'invoice_id' => $invoice->id,
                'garage_id'  => $garageId,
                'path'       => $path,
                'abs'        => $absolutePath,
                'message'    => $e->getMessage(),
            ]);

            abort(500, 'Receipt PDF generation failed: ' . $e->getMessage());
        }

        abort_unless(Storage::disk($disk)->exists($path), 500, 'Receipt PDF was not saved');

        $size = Storage::disk($disk)->size($path);

        // ✅ Versioning (unchanged)
        $existing = Document::where('garage_id', $garageId)
            ->where('documentable_type', Invoice::class)
            ->where('documentable_id', $invoice->id)
            ->where('document_type', 'receipt_pdf')
            ->first();

        $version = ($existing?->version ?? 0) + 1;

        // ✅ Archive (unchanged)
        Document::updateOrCreate(
            [
                'garage_id'         => $garageId,
                'documentable_type' => Invoice::class,
                'documentable_id'   => $invoice->id,
                'document_type'     => 'receipt_pdf',
            ],
            [
                'name'      => 'Receipt ' . ($invoice->invoice_number ?? ''),
                'disk'      => $disk,
                'path'      => $path,
                'file_name' => $filename,
                'mime_type' => 'application/pdf',
                'file_size' => $size,
                'version'   => $version,
            ]
        );

        return Storage::disk($disk)->download($path, $filename);
    }

    /**
     * Email invoice with PDF attached.
     * ✅ FIXED:
     * - Avoid giant attachData($pdf->output()) (memory blow)
     * - Uses Chrome-based PDF saved to disk, then attached by path.
     */
    public function sendEmail(Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        $invoice->load(['customer', 'vehicle', 'job', 'items', 'garage']);

        $customer = $invoice->customer;

        if (!$customer || !$customer->email) {
            return back()->with('error', 'Customer email not available.');
        }

        $this->recalculateTotals($invoice);
        $invoice->refresh();

        $disk = 'public';
        $path = DocumentPath::invoicePdfPath($invoice);
        Storage::disk($disk)->makeDirectory(dirname($path));
        $absolutePath = Storage::disk($disk)->path($path);

        // Use the SAME HTML you use for PDF preview
        $html = $this->renderInvoicePdfHtml($invoice);

        $this->browsershotPdf($html)->savePdf($absolutePath);

        $fileName = 'Invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';

        Mail::send([], [], function ($message) use ($customer, $invoice, $absolutePath, $fileName) {
            $name = $customer->name ?: 'Customer';

            $message->to($customer->email)
                ->subject('Invoice ' . ($invoice->invoice_number ?? $invoice->id))
                ->html("Hello {$name},<br><br>Please find your invoice <strong>#{$invoice->invoice_number}</strong> attached.<br><br>Thank you.")
                ->attach($absolutePath, [
                    'as'   => $fileName,
                    'mime' => 'application/pdf',
                ]);
        });

        return back()->with('success', 'Invoice emailed successfully (PDF attached).');
    }

    /**
     * Redirect to WhatsApp with PDF link (not the internal show page).
     */
    public function sendWhatsApp(Invoice $invoice)
    {
        $this->authorizeGarage($invoice);

        $invoice->load(['customer', 'items']);

        $customer = $invoice->customer;

        if (!$customer || !$customer->phone) {
            return back()->with('error', 'Customer phone number not available.');
        }

        $this->recalculateTotals($invoice);
        $invoice->refresh();

        // Kenyan normalization: 07xx -> 2547xx
        $phone = preg_replace('/\s+/', '', (string) $customer->phone);
        $phone = preg_replace('/^\+?254/', '254', $phone);
        $phone = preg_replace('/^0/', '254', $phone);

        $pdfUrl = URL::temporarySignedRoute(
            'invoices.share',
            now()->addDays(7),
            ['invoice' => $invoice->id]
        );


        $message = "Hello {$customer->name}, your invoice #{$invoice->invoice_number} "
            . "of KES " . number_format((float) $invoice->total_amount, 2)
            . " is ready. Download PDF: {$pdfUrl}";

        $url = "https://wa.me/{$phone}?text=" . urlencode($message);

        return redirect()->away($url);
    }

    public function share(Invoice $invoice)
    {
        $invoice->load(['garage']);

        $garage = $invoice->garage;

        // Public logo (must be accessible)
        $logoUrl =
            $garage->logo_url
            ?? ($garage->logo_path
                ? asset('storage/' . ltrim($garage->logo_path, '/'))
                : asset('images/brand/share-default.png'));

        $pdfUrl = route('invoices.pdf', $invoice);

        return response()
            ->view('invoices.share', compact(
                'invoice',
                'garage',
                'logoUrl',
                'pdfUrl'
            ))
            ->header('Content-Type', 'text/html');
    }

    /**
     * Recalculate totals from invoice items.
     * ✅ Stores tax_rate as PERCENT (e.g. 16.00), consistent with Invoice model.
     * ✅ Accepts garage vat_rate as either 0.16 OR 16
     * ✅ Also updates labour_amount / parts_amount if those columns exist.
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $subtotal = round((float) $invoice->items()->sum('line_total'), 2);

        $taxRatePercent = (float) ($invoice->tax_rate ?? 0);

        $garage = auth()->user()->garage ?? null;
        if ($garage && isset($garage->vat_rate)) {
            $v = (float) $garage->vat_rate;

            // Allow either 0.16 OR 16 formats in settings
            $taxRatePercent = ($v > 0 && $v <= 1) ? ($v * 100) : $v;
        }

        $tax   = round($subtotal * ($taxRatePercent / 100), 2);
        $total = round($subtotal + $tax, 2);

        $labour = round((float) $invoice->items()->where('item_type', 'labour')->sum('line_total'), 2);
        $parts  = round((float) $invoice->items()->where('item_type', 'part')->sum('line_total'), 2);

        $update = [
            'subtotal'     => $subtotal,
            'tax_rate'     => round($taxRatePercent, 2),
            'tax_amount'   => $tax,
            'total_amount' => $total,
        ];

        if (Schema::hasColumn('invoices', 'labour_amount')) {
            $update['labour_amount'] = $labour;
        }
        if (Schema::hasColumn('invoices', 'parts_amount')) {
            $update['parts_amount'] = $parts;
        }

        $invoice->update($update);
    }

    /**
     * Garage isolation guard.
     */
    protected function authorizeGarage($model): void
    {
        $garageId = Auth::user()->garage_id;

        if ((int) $model->garage_id !== (int) $garageId) {
            abort(403);
        }
    }

    private function guardInsuranceInvoicing(\App\Models\Job $job): void
    {
        // Only applies to insurance jobs
        if (($job->payer_type ?? null) !== 'insurance') {
            return;
        }

        if (($job->approval_status ?? null) !== 'approved') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'insurance_gate' => 'Cannot invoice: approval not granted.',
            ]);
        }

        $lpo = \Illuminate\Support\Facades\DB::table('job_insurance_details')
            ->where('job_id', $job->id)
            ->value('lpo_number');

        if (empty($lpo)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'insurance_gate' => 'Cannot invoice: LPO number is required before invoicing.',
            ]);
        }
    }

}
