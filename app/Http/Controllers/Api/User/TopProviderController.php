<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopProviderController extends Controller
{
    public function topProviders(Request $request)
    {
        $topProviders = Review::select(
            'provider_id',
            DB::raw('AVG(rating) as average_rating'),
            DB::raw('COUNT(*) as total_reviews')
        )
            ->groupBy('provider_id')
            ->orderByDesc('average_rating')
            ->with('provider:id,full_name,avatar,location') // relationship
            ->take($request->limit ?? 5)
            ->get();

        // Optional avatar fallback
        foreach ($topProviders as $item) {

            // Format rating to 1 decimal place (like 4.0)
            $item->average_rating = number_format($item->average_rating, 1);

            $item->provider->avatar = $item->provider->avatar
                ? asset($item->provider->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($item->provider->full_name);
        }

        return response()->json([
            'status' => true,
            'message' => 'Top providers by average rating.',
            'data' => $topProviders
        ]);
    }


    public function viewProvider($id = null)
    {
        // Fetch provider with profile
        $provider = User::with('profile')
            ->where('id', $id)
            ->where('role', 'PROVIDER')
            ->select('id', 'full_name', 'email', 'avatar', 'role')
            ->first();

        // Not found
        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'Provider not found.'
            ], 404);
        }

        // Avatar
        $provider->avatar = $provider->avatar
            ? asset($provider->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($provider->full_name);

        // Rating & review count
        $ratingStats = Review::where('provider_id', $id)
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
            ->first();

        $provider->average_rating = $ratingStats->average_rating
            ? number_format($ratingStats->average_rating, 1)
            : null;

        $provider->total_reviews = $ratingStats->total_reviews ?? 0;


        $accepted = $provider->profile->completed_services;
        $cancelled = $provider->profile->canceled_order;

        $total = $accepted + $cancelled;

        if ($total > 0) {
            $acceptRate = round(($accepted / $total) * 100); // round করলে 56 এর মত হবে
            $provider->complete_rate = $acceptRate . '%';
        } else {
            $provider->complete_rate = '0%';
        }


        return response()->json([
            'status' => true,
            'message' => 'Provider profile loaded successfully.',
            'provider' => $provider,
            'reviews' => Review::where('provider_id', $id)->get()
        ]);
    }


}
