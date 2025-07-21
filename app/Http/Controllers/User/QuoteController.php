<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    public function createQuote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|max:255',
            'describe_issue' => 'required|string',
            'property_type' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'pool_depth' => 'nullable|string|max:255',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'zip_code' => 'required|string|max:5',
            'address' => 'required|string|max:255',
            'expected_budget' => 'nullable|string|max:255',

            'photos' => 'nullable|array|max:4', // Max 4 files
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime|max:10240',
        ]);

        // 🛑 Custom validation: Only one of photos or video can be sent
        $hasPhotos = $request->hasFile('photos');
        $hasVideo = $request->hasFile('video');

        if ($hasPhotos && $hasVideo) {
            $validator->after(function ($validator) {
                $validator->errors()->add('photos', 'You can upload either photos or a video — not both.');
                $validator->errors()->add('video', 'You can upload either photos or a video — not both.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // multiple image upload
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = '/storage/' . $photo->store('quotes/photos', 'public');
            }
        }

        // single video upload
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = '/storage/' . $request->file('video')->store('quotes/videos', 'public');
        }

        $date = Carbon::createFromFormat('m/d/Y', $request->date)->format('Y-m-d');
        $time = Carbon::parse($request->time)->format('H:i');

        // Create quote
        $quote = Quote::create([
            'user_id' => Auth::id(),
            'service' => $request->service,
            'describe_issue' => $request->describe_issue,
            'property_type' => $request->property_type,
            'service_type' => $request->service_type,
            'pool_depth' => $request->pool_depth ?? null,
            'date' => $date,
            'time' => $time,
            'zip_code' => $request->zip_code,
            'address' => $request->address,
            'expected_budget' => $request->expected_budget ?? null,
            'photos' => json_encode($photoPaths),
            'video' => $videoPath,
            'status' => 'Pending',
            'is_paid' => false,
        ]);

        // $quote->photos = json_decode($quote->photos, true);

        return response()->json([
            'message' => 'Quote created successfully.',
            'quote' => $quote,
        ], 201);
    }

    public function getQuotes(Request $request)
    {
        $perPage = $request->get('per_page', 10); // default 10 per page

        $quotes = Quote::with('user') // optional
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
            'message' => 'Get all quotes',
            'quotes' => $quotes
        ]);
    }

    public function getMyQuotes()
    {
        $quotes = Quote::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($quotes as $quote) {
            $decoded = json_decode($quote->photos, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $quote->photos = $decoded;
        }

        return response()->json([
            'status' => true,
            'message' => 'Get my all quotes',
            'quotes' => $quotes
        ]);
    }


}
