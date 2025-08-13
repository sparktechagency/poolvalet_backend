<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getData(Request $request)
    {
        $active_users = User::where('role', 'USER')->count();
        $plan = Plan::sum('price');
        $transactions = Transaction::sum('amount');
        $fee = $transactions / 100 * 5;

        $total_transactions = $plan + $transactions;
        $total_revenues = $fee + $plan;

        $active_users_in_kilo = $active_users > 1000 ? number_format($active_users / 1000, 2) . 'k' : $active_users;
        $total_transactions_in_kilo = $total_transactions > 1000 ? number_format($total_transactions / 1000, 2) . 'k' : $total_transactions;
        $total_revenues_in_kilo = $total_revenues > 1000 ? number_format($total_revenues / 1000, 2) . 'k' : $total_revenues;

        return response()->json([
            'status' => true,
            'message' => 'Get dashboard data',
            'active_users' => $active_users_in_kilo,
            'transactions' => $total_transactions_in_kilo,
            'revenues' => $total_revenues_in_kilo
        ]);
    }
}
