<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class BrowseQuoteController extends Controller
{
    public function browseQuotes(Request $request)
    {

        $category_names = Category::pluck('name')->toArray();

        $query = Quote::query();

        // ✅ Optional status filter
        if ($request->has('category') && in_array($request->category, $category_names)) {
            $query->where('service', $request->category);
        }

        $quotes = $query->with([
            'user' => function ($q) {
                $q->select('id', 'full_name', 'avatar');
            }
        ])
            ->select('id', 'user_id', 'service', 'expected_budget')
            ->latest()->paginate($request->per_page ?? 10);

        // ✅ Format each user
        foreach ($quotes as $quote) {
            $quote->user->avatar = $quote->user->avatar
                ? asset($quote->user->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($quote->user->full_name);
        }

        return response()->json([
            'status' => true,
            'message' => 'Browse all users quotes',
            'data' => $quotes
        ]);
    }

    public function viewBrowseQuote(Request $request, $id = null)
    {
        $quote = Quote::with('user.profile')->where('id', $id)->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found'
            ]);
        }

        $decoded = json_decode($quote->photos, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        $quote->photos = $decoded;

        return response()->json([
            'status' => true,
            'message' => 'View browse quote',
            'data' => $quote
        ]);
    }

    public function acceptBudget(Request $request)
    {
        $quote = Quote::where('id', $request->quote_id)->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found'
            ]);
        }

        $provider_id = Bid::where('quote_id', $request->quote_id)
            ->where('provider_id', Auth::id())
            ->first()
            ->provider_id ?? null;

        if ($provider_id == Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'You alredy accepted budget in this quote'
            ]);
        }

        $bid = Bid::create([
            'quote_id' => $quote->id,
            'provider_id' => Auth::id(),
            'price_offered' => $quote->expected_budget,
            'quote_outline' => "The homeowner's expected budget was directly accepted by the provider.",
            'status' => 'Accepted',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Budget accepted successfully',
            'data' => $bid
        ]);
    }

    public function applyBid(Request $request)
    {

        $check_plan = Plan::where('provider_id', Auth::id())->first();

        if (!$check_plan) {
            return response()->json([
                'status' => false,
                'message' => 'Please buy a plan'
            ]);
        }

        // Plan must be active and have at least 1 quote left
        if ($check_plan->status != 'Active' || $check_plan->total_quotes <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'You have no active plan or no remaining quotes. Please buy a plan.'
            ]);
        }

        // Decrement quotes
        $check_plan->decrement('total_quotes');


        if ($check_plan->total_quotes <= 0) {
            $check_plan->status = 'Inactive';
            $check_plan->save();
        }

        // return response()->json([
        //     'status' => true,
        //     'message' => 'Quote usage recorded successfully.'
        // ]);

        $quote = Quote::where('id', $request->quote_id)->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found'
            ]);
        }

        $provider_id = Bid::where('quote_id', $request->quote_id)
            ->where('provider_id', Auth::id())
            ->first()
            ->provider_id ?? null;

        if ($provider_id == Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'You already bid in this quote'
            ]);
        }

        $bid = Bid::create([
            'quote_id' => $quote->id,
            'provider_id' => Auth::id(),
            'price_offered' => $request->price_offered,
            'quote_outline' => $request->quote_outline
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Bid applied successfully',
            'data' => $bid
        ]);
    }

    public function getYourBid(Request $request)
    {
        $bid = Bid::where('quote_id', $request->quote_id)
            ->where('bid_status', 'Private')
            ->where('provider_id', Auth::id())
            ->first();

        if (!$bid) {
            return response()->json([
                'status' => false,
                'message' => 'Bid not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Get your bid',
            'data' => $bid
        ]);

    }

    public function editYourBid(Request $request)
    {
        $bid = Bid::where('quote_id', $request->quote_id)
            ->where('bid_status', 'Private')
            ->where('provider_id', Auth::id())
            ->first();

        if (!$bid) {
            return response()->json([
                'status' => false,
                'message' => 'Bid not found'
            ]);
        }

        $bid->update([
            'price_offered' => $request->price_offered,
            'quote_outline' => $request->quote_outline,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Edit your bid successfully',
            'data' => $bid

        ]);
    }

    public function makeFinalSaveYourBid(Request $request)
    {
        $bid = Bid::where('quote_id', $request->quote_id)
            ->where('bid_status', 'Private')
            ->where('provider_id', Auth::id())
            ->first();

        if (!$bid) {
            return response()->json([
                'status' => false,
                'message' => 'Bid not found'
            ]);
        }

        $bid->bid_status = 'Public';
        $bid->save();

        return response()->json([
            'status' => true,
            'message' => 'Make final save your bid',
            'data' => $bid
        ]);
    }

    public function biddingLists(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        // বাকি বিড লিস্ট
        $bidding_lists = Bid::with(['provider:id,full_name,avatar'])
            ->where('quote_id', $request->quote_id)
            // ->where('bid_status', 'Private')
            ->where('provider_id', '!=', Auth::id())
            ->select('id', 'provider_id', 'price_offered', 'bid_status', 'created_at')
            ->paginate($perPage);

        // ✅ নিজের bid (যদি থাকে)
        $yourBid = Bid::where('quote_id', $request->quote_id)
            ->where('provider_id', Auth::id())
            ->value('price_offered'); // value() null return করে যদি না পায়

        // if ($bidding_lists->isEmpty()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Bidding list is empty.'
        //     ]);
        // }

        // ✅ Avatar formatting
        foreach ($bidding_lists as $bid) {
            $bid->provider->avatar = $bid->provider->avatar
                ? asset($bid->provider->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($bid->provider->full_name);
        }



        return response()->json([
            'status' => true,
            'message' => 'Bidding list fetched successfully.',
            'your_bid' => $yourBid ? 'Your asking bid price: $' . $yourBid : 'You have not placed any bid yet.',
            'data' => $bidding_lists
        ]);
    }
}