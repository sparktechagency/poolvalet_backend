<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\ServiceCompletedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyServiceController extends Controller
{
    public function myServiceQuotes(Request $request)
    {
        $status = $request->status;

        if($request->status == 'In progress'){
            $status = 'Accepted';
        }

        $bids = Bid::with(['quote','quote.user'])
            ->where('status',$status)
            ->where('provider_id', Auth::id())
            ->where('bid_status', 'Public')
            ->latest()
            ->paginate($request->per_page ?? 10);



        foreach ($bids as $bid) {
            $bid->quote->avatar = $bid->quote->avatar
                ? asset($bid->quote->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($bid->quote->full_name);

            $quote = $bid->quote;

            if ($quote && $quote->photos) {
                $decoded = json_decode($quote->photos, true);

                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                $quote->photos = $decoded;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Get my service quotes.',
            'data' => $bids
        ]);
    }

    public function cancelBid(Request $request, $id = null)
    {
        $bid = Bid::where('id', $id)->first();

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

    public function markAsComplete(Request $request)
    {

        $bids_of_quote = Bid::where('id', $request->bid_id)->where('bid_status', 'public')->first();

         $quote = Quote::where('id', $bids_of_quote->quote_id)->first();
        
        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found'
            ]);
        }

        if ($bids_of_quote) {
            $bids_of_quote->status = 'Completed';
            $bids_of_quote->save();

            $quote->status = 'Completed';
            $quote->save();

            $profile = Profile::where('user_id', Auth::id())->first();
            $profile->increment('completed_service');
        }


        $quote = Quote::where('id', $bids_of_quote->quote_id)->first();
        $user = User::where('id', $quote->user_id)->first();

        $provider = User::where('id', Auth::id())->first();

        $data = [
            'provider_id' => $provider->id,
            'provider_name' => $provider->full_name,
            'provider_avatar' => $provider->avatar
                ? asset($provider->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($provider->full_name)
        ];

        $user->notify(new ServiceCompletedNotification($data));

        return response()->json([
            'status' => true,
            'message' => 'Make as completed',
            'data' => $quote
        ]);
    }

    public function myEarnings(Request $request)
    {
        $mark_as_complete_quotes = Bid::where('status', 'Accepted')->where('provider_id', Auth::id())->pluck('quote_id')->toArray();

        $my_earnings = Quote::whereIn('id', $mark_as_complete_quotes)->where('status', 'Completed')->get();

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
