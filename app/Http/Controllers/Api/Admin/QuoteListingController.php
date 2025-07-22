<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;

class QuoteListingController extends Controller
{
    public function getQuoteListing(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $quotes = Quote::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);


        foreach ($quotes as $quote) {
            $decoded = json_decode($quote->photos, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $quote->photos = $decoded;
        }

        return response()->json([
            'status' => true,
            'message' => 'Get quote listing',
            'quotes' => $quotes
        ]);
    }

    public function viewQuote($id = null)
    {
        $quote = Quote::with('user')->find($id);

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message'=> 'View quote',
            'quote' => $quote
        ]);
    }


}
