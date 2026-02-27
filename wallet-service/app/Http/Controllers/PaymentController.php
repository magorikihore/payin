<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(Request $request)
    {
        // Payment logic placeholder
        return response()->json(['status' => 'success', 'transaction_id' => uniqid()], 201);
    }

    public function status($transaction_id)
    {
        // Transaction status logic placeholder
        return response()->json(['transaction_id' => $transaction_id, 'status' => 'completed']);
    }
}
