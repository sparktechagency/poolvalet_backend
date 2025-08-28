<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function createReview(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|exists:users,id',
            'quote_id' => 'nullable',
            'rating' => 'required|integer|min:1|max:5',
            'compliment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }


        $review = Review::create([
            'user_id' => Auth::id(),
            'provider_id' => $request->provider_id,
            'quote_id' => $request->quote_id,
            'rating' => $request->rating,
            'compliment' => $request->compliment ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review created successfully.',
            'data' => $review,
        ]);
    }

    public function getReviews(Request $request)
    {
        $reviews = Review::latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'message' => 'All reviews loaded.',
            'data' => $reviews,
        ]);
    }

    public function viewReview($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Review loaded successfully.',
            'data' => $review,
        ]);
    }

    public function getProviderRating(Request $request){
        $provider_review = Review::where('quote_id',$request->quote_id)->first();

        return response()->json([
            'status' => true,
            'message' => 'Get provider review.',
            'data' => $provider_review,
        ]);
    }


}
