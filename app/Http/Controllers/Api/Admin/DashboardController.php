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

        return response()->json([
            'status' => true,
            'message' => 'Get data for ' . $request->filter . ' days.',
            'active_users' => $active_users >= 1000 ? number_format($active_users / 1000) . 'k' : number_format($active_users),
            'up_down_active_users' => $up_down_active_users,
            'transactions' => $transactions >= 1000 ? number_format($transactions / 1000, 2) . 'k' : number_format($transactions, 2),
            'up_down_transactions' => $up_down_transactions,
            'revenues' => $revenues >= 1000 ? number_format($revenues / 1000) . 'k' : number_format($revenues, 2),
            'up_down_revenues' => $up_down_revenues,
        ]);

    }
    public function getChart(Request $request)
    {
        // active users
        $dates = collect();
        for ($i = 0; $i < 7; $i++) {
            $dates->push(Carbon::now()->subDays(6 - $i)->format('Y-m-d'));
        }

        $active_users = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('role', 'USER')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date'); // [ '2025-08-07' => 5, '2025-08-09' => 2, ... ]

        $active_users_chart = $dates->map(function ($date) use ($active_users) {
            $count = $active_users->get($date, 0);
            return [
                'date' => $date,
                'count' => $count >= 1000 ? number_format($count / 1000) . 'k' : number_format($count),
                'day' => Carbon::parse($date)->format('D')
            ];
        });

        // Transactions
        $startDate = Carbon::now()->subDays(6)->startOfDay(); // last 7 days including today
        $endDate = Carbon::now()->endOfDay();
        $transactionsData = Transaction::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount
        ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total_transactions' => (int) $item->total_transactions,
                    'total_amount' => (float) $item->total_amount
                ];
            });
        $plansData = Plan::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_transactions,
            SUM(price) as total_amount
        ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total_transactions' => (int) $item->total_transactions,
                    'total_amount' => (float) $item->total_amount
                ];
            });
        $transactions_chart = collect()
            ->merge($transactionsData)
            ->merge($plansData)
            ->groupBy('date')
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'day' => Carbon::parse($date)->format('D'),
                    'total_transactions' => $items->sum('total_transactions') >= 1000
                        ? number_format($items->sum('total_transactions') / 1000) . 'k'
                        : number_format($items->sum('total_transactions')),
                    'total_amount' => $items->sum('total_amount') >= 1000
                        ? number_format($items->sum('total_amount') / 1000, 2) . 'k'
                        : number_format($items->sum('total_amount'), 2)
                ];
            });
        $full_weeks = collect();
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->toDateString();
            if (!isset($transactions_chart[$dateStr])) {
                $full_weeks->put($dateStr, [
                    'date' => $dateStr,
                    'day' => $currentDate->format('D'),
                    'total_transactions' => 0,
                    'total_amount' => number_format(0, 2)
                ]);
            } else {
                $full_weeks->put($dateStr, $transactions_chart[$dateStr]);
            }
            $currentDate->addDay();
        }
        $transactions_chart = $full_weeks->sortBy('date')->values();

        // revenues
        $startDate = Carbon::now()->subDays(6)->startOfDay(); // last 7 days
        $endDate = Carbon::now()->endOfDay();
        $feeData = Transaction::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount
        ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total_amount', 'date')
            ->map(function ($amount) {
                return (float) $amount / 100 * 5;
            });
        $plansData = Plan::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_transactions,
            SUM(price) as total_amount
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total_amount', 'date');
        $revenues_chart = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->toDateString();
            $transactionsCount = Transaction::whereDate('created_at', $dateStr)->count()
                + Plan::whereDate('created_at', $dateStr)->count();
            $totalAmount = ($feeData[$dateStr] ?? 0) + ($plansData[$dateStr] ?? 0);
            $revenues_chart->push([
                'date' => $dateStr,
                'day' => $date->format('D'),
                'total_transactions' => $transactionsCount >= 1000
                    ? number_format($transactionsCount / 1000) . 'k'
                    : number_format($transactionsCount),
                'total_amount' => $totalAmount >= 1000
                    ? number_format($totalAmount / 1000, 2) . 'k'
                    : number_format($totalAmount, 2)
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Get chart.',
            'active_users_chart' => $active_users_chart,
            'transactions_chart' => $transactions_chart,
            'revenues_chart' => $revenues_chart
        ]);
    }
}
