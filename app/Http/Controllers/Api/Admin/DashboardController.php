<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getData(Request $request)
    {

        $startDate = Carbon::now()->subDays(7); // 7 দিন আগে থেকে
        $endDate = Carbon::now(); // আজ পর্যন্ত

        $active_users = User::where('role', 'USER')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $plan = Plan::whereBetween('created_at', [$startDate, $endDate])
            ->sum('price');

        $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $fee = $transactions / 100 * 5;


        $active_users_last7days = $active_users;
        $plan_last7days = $plan;
        $transactions_last7days = $transactions;
        $fee_last7days = $fee;


        $active_users = User::where('role', 'USER')->count();
        $plan = Plan::sum('price');
        $transactions = Transaction::sum('amount');
        $fee = $transactions / 100 * 5;

        $total_transactions = $plan + $transactions;
        $total_revenues = $fee + $plan;

        $active_users_in_kilo = $active_users >= 1000 ? number_format($active_users / 1000, 2) . 'k' : $active_users;
        $total_transactions_in_kilo = $total_transactions >= 1000 ? number_format($total_transactions / 1000, 2) . 'k' : $total_transactions;
        $total_revenues_in_kilo = $total_revenues >= 1000 ? number_format($total_revenues / 1000, 2) . 'k' : $total_revenues;

        return response()->json([
            'status' => true,
            'message' => 'Get dashboard data',
            'active_users' => $active_users_in_kilo,
            'active_users_last7days' => $active_users_last7days >= 1000 ? number_format($active_users_last7days / 1000, 2) . 'k' : $active_users_last7days,

            'transactions' => $total_transactions_in_kilo,
            'transactions_last7days' => $plan_last7days + $transactions_last7days >= 1000 ? number_format($plan_last7days + $transactions_last7days / 1000, 2) . 'k' : $plan_last7days + $transactions_last7days,

            'revenues' => $total_revenues_in_kilo,
            'revenues_last7days' => $plan_last7days + $fee_last7days >= 1000 ? number_format($plan_last7days + $fee_last7days / 1000, 2) . 'k' : $plan_last7days + $fee_last7days
        ]);
    }
}
