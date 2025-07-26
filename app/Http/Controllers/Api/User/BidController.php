<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BidController extends Controller
{
    public function getCheckBids(Request $request)
    {
        $bids = Bid::where('bid_status', 'Public')
            ->where('quote_id', $request->quote_id)
            ->where('status', null)
            ->paginate($request->per_page ?? 10);

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
            ->paginate($request->per_page ??10);

        return response()->json([
            'status' => true,
            'message' => 'Get all accepted bids',
            'data' => $bids
        ]);
    }

    public function acceptRequest(Request $request)
    {
        try {
            $quote = Quote::where('id', $request->quote_id)->where('status','Pending')->first();

            if (!$quote) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quote not found'
                ]);
            }

            $bids_of_quote = Bid::where('quote_id', $request->quote_id)->where('bid_status','public')->first();

            if ($bids_of_quote) {
                $bids_of_quote->status = 'Accepted';
                $bids_of_quote->save();

                $quote->status = 'In progress';
                $quote->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Accept request successfully',
                    'data' => $quote
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'This quote has no bids.'
                ]);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => $th->getMessage()
            ]);
        }
    }

    public function cancelOrder(Request $request)
    {
        try {
            $quote = Quote::where('id', $request->quote_id)->where('status','In progress')->first();

            if (!$quote) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quote not found'
                ]);
            }

            $bids_of_quote = Bid::where('quote_id', $request->quote_id)->where('bid_status','public')->first();

            if ($bids_of_quote) {
                $bids_of_quote->status = 'Canceled';
                $bids_of_quote->save();

                $quote->status = 'Pending';
                $quote->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Order canceled successfully',
                    'data' => $quote
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'This quote has no bids.'
                ]);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => $th->getMessage()
            ]);
        }
    }

}
