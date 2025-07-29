<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function getTransactions(Request $request)
    {
        $transactions = Transaction::latest()->paginate($request->per_page??10);

        return response()->json([
            'status' => true,
            'message' => 'Get all transactions',
            'data' => $transactions
        ]);
    }
}
