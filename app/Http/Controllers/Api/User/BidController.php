<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\Review;
use App\Models\User;
use App\Notifications\CanceledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BidController extends Controller
{
    public function getCheckBids(Request $request)
    {
        $bids = Bid::with('provider')->where('bid_status', 'Public')
            ->where('quote_id', $request->quote_id)
            ->where('status', null)
            ->paginate($request->per_page ?? 10);

        foreach ($bids as $bid) {

            $bid->provider->avatar = $bid->provider->avatar
                ? asset($bid->provider->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($bid->provider->full_name);


            $ratingStats = Review::where('provider_id', $bid->provider_id)
                ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
                ->first();

            $bid->average_rating = $ratingStats->average_rating
                ? number_format($ratingStats->average_rating, 1)
                : 0;

            $bid->button = Quote::where('id', $request->quote_id)->first()->status;
        }

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
            ->paginate($request->per_page ?? 10);


        foreach ($bids as $bid) {

            $bid->provider->avatar = $bid->provider->avatar
                ? asset($bid->provider->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($bid->provider->full_name);


            $ratingStats = Review::where('provider_id', $bid->provider_id)
                ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
                ->first();

            $bid->average_rating = $ratingStats->average_rating
                ? number_format($ratingStats->average_rating, 1)
                : 0;
        }

        return response()->json([
            'status' => true,
            'message' => 'Get all accepted bids',
            'data' => $bids
        ]);
    }
    public function acceptRequest(Request $request)
    {
        try {
            $bids_of_quote = Bid::where('id', $request->bid_id)->where('bid_status', 'public')->first();
            $bids_of_quote_status = Bid::where('quote_id', $bids_of_quote->quote_id)
                ->where('bid_status', 'public')
                ->pluck('status')
                ->toArray();
            if (in_array('Accepted', $bids_of_quote_status)) {
                return response()->json([
                    'status' => false,
                    'message' => "You already accepted one person, you can't accept anyone else."
                ]);
            }
            if ($bids_of_quote) {
                $bids_of_quote->status = 'Accepted';
                $bids_of_quote->save();
                $bids_of_quote->quote->status = 'In progress';
                $bids_of_quote->quote->save();
                $profile = Profile::where('user_id', $bids_of_quote->quote->user_id)->first();
                $profile->increment('order_accept');
                return response()->json([
                    'status' => true,
                    'message' => 'Accept request successfully',
                    'data' => $bids_of_quote
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
            $quote = Quote::where('id', $request->quote_id)->where('status', 'In progress')->first();
            if (!$quote) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quote not found'
                ]);
            }

            $bids_of_quote = Bid::where('quote_id', $request->quote_id)->where('bid_status', 'public')->first();

            if ($bids_of_quote) {

                $bids_of_quote->status = 'Canceled';

                $bids_of_quote->save();

                $quote->delete();

                $profile = Profile::where('user_id', $quote->user_id)->first();

                $profile->increment('canceled_order');

                if ($profile->order_accept > 0) {
                    $profile->decrement('order_accept');
                } else {
                    $profile->order_accept = 0;
                }


                $provider = User::where('id', $bids_of_quote->provider_id)->first();

                $user = User::where('id', $quote->user_id)->first();

                $data = [
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'user_avatar' => $user->avatar
                        ? asset($user->avatar)
                        : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name)
                ];

                $provider->notify(new CanceledNotification($data));

                return response()->json([
                    'status' => true,
                    'message' => 'Order canceled successfully'
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
