<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        return view('billing.index');
    }
}
