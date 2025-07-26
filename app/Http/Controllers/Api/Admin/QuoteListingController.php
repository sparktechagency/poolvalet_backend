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

        $quotes = Quote::with([
            'user' => function ($q) {
                $q->select('id', 'full_name', 'avatar');
            }
        ])
            ->select('id', 'user_id', 'service', 'expected_budget', 'photos')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($quotes->isEmpty() || $quotes->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Quotes not found'
            ]);
        }

        foreach ($quotes as $quote) {
            $decoded = json_decode($quote->photos, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $quote->photos = $decoded;
        }

        $quote->user->avatar = $quote->user->avatar
            ? asset($quote->user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($quote->user->full_name);

        return response()->json([
            'status' => true,
            'message' => 'Get quote listing',
            'quotes' => $quotes
        ]);
    }

    public function viewQuote($id = null)
    {
        $quote = Quote::find($id);

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'View quote',
            'quote' => $quote
        ]);
    }


}
