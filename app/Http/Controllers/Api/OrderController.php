<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function test(Request $request){


        $start = Carbon::now()->startOfYear();
        $end   = Carbon::now()->endOfYear();

        // DB থেকে grouped ডাটা
        $orders = Order::select(
                        DB::raw('YEARWEEK(date, 1) as week'),
                        DB::raw('SUM(price * quantity) as total_sales'),
                        DB::raw('COUNT(id) as total_orders')
                    )
                    ->whereBetween('date', [$start, $end])
                    ->groupBy('week')
                    ->get()
                    ->keyBy('week');

        // সব সপ্তাহ জেনারেট করা
        $weeks = [];
        $period = new \DatePeriod($start, new \DateInterval('P1W'), $end);
        foreach ($period as $dt) {
            $week = $dt->format("oW"); // ISO YearWeek
            $weeks[] = [
                'week' => $week,
                'total_sales' => $orders[$week]->total_sales ?? 0,
                'total_orders' => $orders[$week]->total_orders ?? 0,
            ];
        }

        return response()->json($weeks);

        $orders = Order::whereBetween('created_at',[Carbon::now(),Carbon::now()->subDays(15)])->get();

         return response()->json([
            'status' => true,
            'message' => 'get test',
            'orders' => $orders
         ]);
    }
}
