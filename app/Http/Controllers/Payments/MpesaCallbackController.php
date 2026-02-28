<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpesaCallbackController extends Controller
{
    public function stkResult(Request $request)
    {
        $payload = $request->all();

        // Safaricom structure:
        // Body.stkCallback.MerchantRequestID
        // Body.stkCallback.CheckoutRequestID
        // Body.stkCallback.ResultCode
        // Body.stkCallback.ResultDesc
        // Body.stkCallback.CallbackMetadata.Item[]  (Amount, MpesaReceiptNumber, PhoneNumber, TransactionDate, etc.)
        $cb = data_get($payload, 'Body.stkCallback', []);

        $merchantRequestId = data_get($cb, 'MerchantRequestID');
        $checkoutRequestId = data_get($cb, 'CheckoutRequestID');
        $resultCode        = (string) data_get($cb, 'ResultCode');
        $resultDesc        = (string) data_get($cb, 'ResultDesc');

        // Pull metadata items into key=>value
        $items = collect(data_get($cb, 'CallbackMetadata.Item', []))
            ->mapWithKeys(function ($i) {
                $name = data_get($i, 'Name');
                return [$name => data_get($i, 'Value')];
            });

        $amount   = $items->get('Amount');
        $receipt  = $items->get('MpesaReceiptNumber');
        $phone    = $items->get('PhoneNumber');
        $trxDate  = $items->get('TransactionDate'); // yyyymmddhhmmss typically

        // Always log the callback first
        $log = PaymentLog::create([
            'provider'            => 'mpesa',
            'direction'           => 'callback',
            'event'               => 'stk_result',
            'merchant_request_id' => $merchantRequestId,
            'checkout_request_id' => $checkoutRequestId,
            'result_code'         => $resultCode,
            'result_desc'         => $resultDesc,
            'phone'              => $phone ? (string)$phone : null,
            'amount'             => $amount !== null ? (float)$amount : null,
            'payload'            => $payload,
        ]);

        // Correlate to payment by CheckoutRequestID (most reliable)
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

        // If not found, you can fallback to merchant_request_id
        if (!$payment && $merchantRequestId) {
            $payment = Payment::where('merchant_request_id', $merchantRequestId)->first();
        }

        // If still not found: keep log only, return OK to avoid retries storms
        if (!$payment) {
            return response()->json(['ok' => true, 'note' => 'payment_not_found_logged']);
        }

        // Attach invoice/payment ids to log for traceability
        $log->update([
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
        ]);

        return DB::transaction(function () use ($payment, $resultCode, $resultDesc, $receipt, $amount, $trxDate) {

            if ($resultCode === '0') {
                // Paid
                $payment->update([
                    'status'        => 'paid',
                    'mpesa_receipt' => $receipt,
                    'amount'        => $amount ?? $payment->amount,
                    'result_code'   => $resultCode,
                    'result_desc'   => $resultDesc,
                    'paid_at'       => now(),
                    'meta'          => array_merge($payment->meta ?? [], [
                        'trx_date_raw' => $trxDate,
                    ]),
                ]);

                // Update invoice
                if ($payment->invoice_id) {
                    /** @var \App\Models\Invoice|null $invoice */
                    $invoice = Invoice::lockForUpdate()->find($payment->invoice_id);

                    if ($invoice) {
                        // You can implement partials here if you allow them.
                        $paidAmount = (float)($amount ?? $payment->amount ?? 0);

                        // Example fields: amount_paid + status
                        if (isset($invoice->amount_paid)) {
                            $invoice->amount_paid = (float)$invoice->amount_paid + $paidAmount;
                        }

                        // Mark paid if fully covered (adapt to your schema)
                        $due = (float)($invoice->amount_due ?? $invoice->total ?? 0);
                        $paid = (float)($invoice->amount_paid ?? $paidAmount);

                        if ($due > 0 && $paid >= $due) {
                            $invoice->status = 'paid';
                            $invoice->paid_at = $invoice->paid_at ?? now();
                        } else {
                            $invoice->status = $invoice->status ?? 'partial';
                        }

                        $invoice->save();
                    }
                }
            } else {
                // Failed / cancelled
                $payment->update([
                    'status'      => 'failed',
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);
            }

            return response()->json(['ok' => true]);
        });
    }
}
