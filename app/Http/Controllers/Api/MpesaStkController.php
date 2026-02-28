<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Services\MpesaDarajaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MpesaStkController extends Controller
{
    public function initiate(Request $request)
    {
        $data = $request->validate([
            'garage_id'  => ['nullable', 'integer'],
            'amount'     => ['required', 'integer', 'min:1'],
            'phone'      => ['required', 'string'],
            'reference'  => ['nullable', 'string', 'max:50'],
            'desc'       => ['nullable', 'string', 'max:100'],
        ]);

        $amount = (int) $data['amount'];
        $phone  = (string) $data['phone'];

        $garageId = $data['garage_id'] ?? null;
        $garage = $garageId ? Garage::find($garageId) : null;

        $accountRef = $data['reference']
            ?? ($garage ? ('GARAGE-' . $garage->id) : 'GARAGESUITE');

        $desc = $data['desc'] ?? 'GarageSuite Subscription';

        try {
            $phone254 = MpesaDarajaService::normalizePhoneTo254($phone);

            /** @var MpesaDarajaService $mpesa */
            $mpesa = app(MpesaDarajaService::class);

            $payload = $mpesa->buildStkPayload($phone254, $amount, $accountRef, $desc);
            $resp = $mpesa->stkPush($payload);

            return response()->json([
                'ok' => true,
                'data' => $resp,
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'message' => 'Server error initiating STK push.',
            ], 500);
        }
    }
}
