<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyServiceController extends Controller
{
    public function myServiceQuotes(Request $request)
    {
        $userId = Auth::id();

        $bids = Bid::with([
            'quote' => function ($query) use ($request) {
                if ($request->has('status') && $request->status != null) {
                    $query->where('status', $request->status);
                }
            }
        ])
            ->where('provider_id', $userId)
            ->where('bid_status', 'Public')
            ->get()
            ->filter(function ($bid) {
                return $bid->quote !== null;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Get my service quotes' . ($request->status ? ' with ' . $request->status . ' status' : ''),
            'data' => $bids
        ]);

    }
}
