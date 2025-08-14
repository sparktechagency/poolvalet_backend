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
        $last_days = $request->filter + $request->filter;

        $lastStartDate = Carbon::now()->subDays($last_days ?? 14);
        $startDate = Carbon::now()->subDays($request->filter ?? 7);
        $endDate = Carbon::now();

        // active users
        $active_users = User::where('role', 'USER')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $last_days_active_users = User::where('role', 'USER')
            ->whereBetween('created_at', [$lastStartDate, $endDate])
            ->count() - $active_users;

        $diff_active_users = $active_users - $last_days_active_users;

        if ($diff_active_users < 0) {
            $up_down_active_users = abs($diff_active_users) >= 1000
                ? number_format(abs($diff_active_users) / 1000) . 'k'
                : number_format(abs($diff_active_users)) . ' decrease in than last ' . $request->filter . ' days';
        } else {
            $up_down_active_users = $diff_active_users >= 1000
                ? number_format($diff_active_users / 1000) . 'k'
                : number_format($diff_active_users) . ' increase in than last ' . $request->filter . ' days';
        }

        // transactions
        $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->sum('amount') + Plan::whereBetween('created_at', [$startDate, $endDate])->sum('price');
        $last_days_transactions = Transaction::whereBetween('created_at', [$lastStartDate, $endDate])->sum('amount') + Plan::whereBetween('created_at', [$lastStartDate, $endDate])->sum('price') - $transactions;

        $diff_transactions = $transactions - $last_days_transactions;

        if ($diff_transactions < 0) {
            $up_down_transactions = abs($diff_transactions) >= 1000
                ? number_format(abs($diff_transactions) / 1000, 2) . 'k'
                : number_format(abs($diff_transactions), 2) . ' decrease in than last ' . $request->filter . ' days';
        } else {
            $up_down_transactions = $diff_transactions >= 1000
                ? number_format($diff_transactions / 1000, 2) . 'k'
                : number_format($diff_transactions, 2) . ' increase in than last ' . $request->filter . ' days';
        }

        // revenues
        $fee = $transactions / 100 * 5;
        $revenues = $fee + Plan::whereBetween('created_at', [$startDate, $endDate])->sum('price');
        $last_days_revenues = $fee + Plan::whereBetween('created_at', [$lastStartDate, $endDate])->sum('price') - $revenues;

        $diff_revenues = $revenues - $last_days_revenues;

        if ($diff_revenues < 0) {
            $up_down_revenues = abs($diff_revenues) >= 1000
                ? number_format(abs($diff_revenues) / 1000, 2) . 'k'
                : number_format(abs($diff_revenues), 2) . ' decrease in than last ' . $request->filter . ' days';
        } else {
            $up_down_revenues = $diff_revenues >= 1000
                ? number_format($diff_revenues / 1000, 2) . 'k'
                : number_format($diff_revenues, 2) . ' increase in than last ' . $request->filter . ' days';
        }


        // active user chart
        $active_users_chart = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('role', 'USER')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($active_users_chart as &$item) {
            $item['day'] = Carbon::parse($item['date'])->format('D');
        }


    //     // transactions
    //     $chartData = Transaction::selectRaw('
    //     DATE(created_at) as date,
    //     COUNT(*) as total_transactions,
    //     SUM(amount) as total_amount
    // ')
    //         ->where('created_at', '>=', Carbon::now()->subDays(7))
    //         ->groupByRaw('DATE(created_at)')
    //         ->orderByRaw('DATE(created_at)')
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'date' => $item->date,
    //                 'day' => Carbon::parse($item->date)->format('D'),
    //                 'total_transactions' => (int) $item->total_transactions,
    //                 'total_amount' => (float) $item->total_amount
    //             ];
    //         });

    //     return response()->json($chartData);



        return response()->json([
            'status' => true,
            'message' => 'Get dashboard data',
            'active_users' => $active_users >= 1000 ? number_format($active_users / 1000) . 'k' : number_format($active_users),
            'up_down_active_users' => $up_down_active_users,
            'transactions' => $transactions >= 1000 ? number_format($transactions / 1000, 2) . 'k' : number_format($transactions, 2),
            'up_down_transactions' => $up_down_transactions,
            'revenues' => $revenues >= 1000 ? number_format($revenues / 1000) . 'k' : number_format($revenues, 2),
            'up_down_revenues' => $up_down_revenues,
            'active_users_chart' => $active_users_chart
        ]);

    }
}
