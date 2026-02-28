<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaymentConfig;
use App\Services\MpesaStkService;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index()
    {
        $configs = PaymentConfig::orderBy('provider')->get();

        return view('settings.payments.index', compact('configs'));
    }

    public function edit(PaymentConfig $paymentConfig)
    {
        return view('settings.payments.edit', ['config' => $paymentConfig]);
    }

    public function update(Request $request, PaymentConfig $paymentConfig)
    {
        $data = $request->validate([
            'is_active'         => ['nullable', 'boolean'],
            'environment'       => ['required', 'in:sandbox,live'],
            'shortcode'         => ['required', 'string', 'max:20'],
            'consumer_key'      => ['nullable', 'string'],
            'consumer_secret'   => ['nullable', 'string'],
            'passkey'           => ['nullable', 'string'],
            'callback_url'      => ['required', 'url'],
            'account_reference' => ['nullable', 'string', 'max:50'],
            'transaction_desc'  => ['nullable', 'string', 'max:100'],
        ]);

        // checkbox normalization
        $data['is_active'] = (bool)($request->boolean('is_active'));

        // Only overwrite secrets if provided (so blank form doesn’t wipe)
        foreach (['consumer_key','consumer_secret','passkey'] as $secretField) {
            if (!array_key_exists($secretField, $data) || $data[$secretField] === null || $data[$secretField] === '') {
                unset($data[$secretField]);
            }
        }

        $paymentConfig->update($data);

        return redirect()
            ->route('settings.payments.index')
            ->with('success', 'Payment settings updated.');
    }

    /**
     * Optional: quick “test STK” from settings page (admin only)
     * POST settings/payments/{paymentConfig}/test-stk
     */
    public function testStk(Request $request, PaymentConfig $paymentConfig, MpesaStkService $stk)
    {
        $data = $request->validate([
            'phone'  => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $resp = $stk->initiateStkPush(
            config: $paymentConfig,
            phone: $data['phone'],
            amount: (float)$data['amount'],
            invoiceId: null // settings test, not tied to invoice
        );

        return back()->with('success', 'STK request sent.')->with('stk_response', $resp);
    }
}
