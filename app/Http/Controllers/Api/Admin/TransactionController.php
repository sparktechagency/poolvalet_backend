<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function getTransactions(Request $request)
    {
        $last_days = $request->filter + $request->filter;
        $lastStartDate = Carbon::now()->subDays($last_days ?? 14);
        $startDate = Carbon::now()->subDays($request->filter ?? 7);
        $endDate = Carbon::now();

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

        // all transactions
        $all_transactions = Transaction::whereBetween('created_at',[$startDate,$endDate])->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'message' => 'Get all transactions',
            'transactions' => $transactions >= 1000 ? number_format($transactions / 1000, 2) . 'k' : number_format($transactions, 2),
            'up_down_transactions' => $up_down_transactions,
            'revenues' => $revenues >= 1000 ? number_format($revenues / 1000) . 'k' : number_format($revenues, 2),
            'up_down_revenues' => $up_down_revenues,
            'all_transactions' => $all_transactions
        ]);
    }
}
