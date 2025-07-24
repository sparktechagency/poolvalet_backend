<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Quote;
use Illuminate\Http\Request;

class BidController extends Controller
{
    public function getCheckBids(Request $request)
    {
        $bids = Bid::where(function ($query) {
            $query->where('status', '!=', 'Accepted')
                ->where('bid_status', 'Public');

        })->get();

        return response()->json([
            'status' => true,
            'message' => 'Get check bids',
            'data' => $bids
        ]);
    }

    public function getAcceptedBids(Request $request)
    {
        $bids = Bid::where('status', 'Accepted')
            ->where('bid_status', 'Public')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Get all accepted bids',
            'data' => $bids
        ]);
    }

    public function acceptRequest(Request $request)
    {
        $quote = Quote::where('id', $request->quote_id)->first();
        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found'
            ]);
        }

        $quote->status = 'In progress';
        $quote->save();

        return response()->json([
            'status' => true,
            'message' => 'Accept request successfully',
            'data' => $quote
        ]);
    }

}
