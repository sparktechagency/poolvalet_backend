<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyServiceController extends Controller
{
    public function myServiceQuotes(Request $request)
    {
        $userId = Auth::id();

        $perPage = $request->per_page ?? 10;

        // Step 1: শুধু provider_id এবং bid_status অনুযায়ী paginate করো
        $bidsPaginator = Bid::with([
            'quote' => function ($query) use ($request) {
                $query->select('id', 'user_id', 'service', 'service_type', 'status');
                if ($request->has('status') && $request->status != null) {
                    $query->where('status', $request->status);
                }
            },
            'quote.user' => function ($query) use ($request) {
                $query->select('id', 'full_name', 'avatar');

            }
        ])
            ->where('provider_id', $userId)
            ->where('bid_status', 'Public')
            ->select('id', 'quote_id', 'provider_id', 'price_offered')
            ->latest()
            ->paginate($perPage);

        // Step 2: Only keep bids that have a quote
        $filteredBids = $bidsPaginator->getCollection()->filter(function ($bid) {
            return $bid->quote !== null;
        });

        // Step 3: Decode quote->photos
        foreach ($filteredBids as $bid) {

            $bid->quote->user->avatar = $bid->quote->user->avatar
                ? asset($bid->quote->user->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($bid->quote->user->full_name);

            $quote = $bid->quote;

            if ($quote && $quote->photos) {
                $decoded = json_decode($quote->photos, true);

                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                $quote->photos = $decoded;
            }
        }

        // Step 4: Re-attach filtered data into paginator
        $bidsPaginator->setCollection($filteredBids->values());

        return response()->json([
            'status' => true,
            'message' => 'Get my service quotes' . ($request->status ? ' with ' . $request->status . ' status' : ''),
            'data' => $bidsPaginator
        ]);
    }


    public function cancelBid(Request $request, $id = null)
    {
        $bid = Bid::find($id);

        if (!$bid) {
            return response()->json([
                'status' => false,
                'message' => 'Bid not found.'
            ], 404);
        }

        $bid->delete();

        return response()->json([
            'status' => true,
            'message' => 'Bid canceled successfully.'
        ]);
    }

    public function myEarnings(Request $request)
    {
        $mark_as_complete_quotes = Bid::where('status', 'Accepted')->pluck('quote_id')->toArray();

        $my_earnings = Quote::whereIn('id', $mark_as_complete_quotes)->get();

        if (!$my_earnings) {
            return response()->json([
                'status' => false,
                'message' => 'You have no erarnings'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your earnings',
            'data' => $my_earnings
        ]);
    }
}
