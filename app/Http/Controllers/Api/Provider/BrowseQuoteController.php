<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BrowseQuoteController extends Controller
{
    public function browseQuotes(Request $request)
    {
        $quotes = Quote::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Browse all users quotes',
            'data' => $quotes
        ]);
    }

    public function viewBrowseQuote(Request $request, $id = null)
    {
        $quote = Quote::find($id);

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found'
            ]);
        }

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
}