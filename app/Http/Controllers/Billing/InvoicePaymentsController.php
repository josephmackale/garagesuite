<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentConfig;
use App\Services\MpesaStkService;
use Illuminate\Http\Request;

class InvoicePaymentsController extends Controller
{
    public function stkPush(Request $request, Invoice $invoice, MpesaStkService $stk)
    {
        $data = $request->validate([
            'phone'  => ['required', 'string'],
            'amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        $amount = isset($data['amount']) ? (float)$data['amount'] : (float)($invoice->amount_due ?? $invoice->total ?? 0);

        $config = PaymentConfig::active()->where('provider', 'mpesa')->firstOrFail();

        $resp = $stk->initiateStkPush(
            config: $config,
            phone: $data['phone'],
            amount: $amount,
            invoiceId: $invoice->id
        );

        return response()->json([
            'ok' => true,
            'invoice_id' => $invoice->id,
            'mpesa' => $resp,
        ]);
    }

    public function storeManual(Request $request, \App\Models\Invoice $invoice)
    {
        // garage isolation
        if ((int) $invoice->garage_id !== (int) auth()->user()->garage_id) abort(403);

        if (($invoice->status ?? null) === 'draft') {
            return back()->withErrors(['payment' => 'Issue the invoice before recording payments.']);
        }

        $data = $request->validate([
            'amount'    => ['required','numeric','min:0.01'],
            'method'    => ['nullable','string','max:50'],
            'reference' => ['nullable','string','max:100'],
            'paid_at'   => ['nullable','date'],
        ]);

        \App\Models\Payment::create([
            'invoice_id' => $invoice->id,
            'amount'     => round((float) $data['amount'], 2),
            'status'     => 'paid',
            'paid_at'    => !empty($data['paid_at']) ? \Carbon\Carbon::parse($data['paid_at']) : now(),
            'method'     => $data['method'] ?? 'manual',
            'reference'  => $data['reference'] ?? null,
            // garage_id auto-set by Payment::creating()
        ]);

        // 🔒 Insurance workflow gate: require claim submission before settlement
        $jobId = $invoice->job_id ?? null;

        if ($jobId) {
            $claimExists = \DB::table('insurance_claims')
                ->where('garage_id', (int) $invoice->garage_id)
                ->where('job_id', (int) $jobId)
                ->exists();

            if (!$claimExists) {
                return back()->withErrors([
                    'payment' => 'Submit insurance claim before recording settlement payment.'
                ]);
            }
        }

        return back()->with('success', 'Payment recorded.');
    }
}
