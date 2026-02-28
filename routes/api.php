<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MpesaStkController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are automatically loaded with the "api" middleware group
| by Laravel's RouteServiceProvider.
|
*/

Route::post('mpesa/stk/initiate', [MpesaStkController::class, 'initiate']);
Route::post('mpesa/stk/callback', [MpesaStkController::class, 'callback']);
