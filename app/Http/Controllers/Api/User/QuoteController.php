<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Review;
use App\Models\User;
use App\Notifications\CreateQuoteNotification;
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
            'date' => 'required',
            'zip_code' => 'required|string|max:5',
            'address' => 'required|string|max:255',
            'expected_budget' => 'nullable|numeric|min:0',

            // ✅ Either photos OR video required
            'photos' => 'required_without:video|array|max:4',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            'video' => 'required_without:photos|file|mimetypes:video/mp4,video/quicktime|max:10240',
        ]);

        // Custom validation: Prevent both photos and video
        if ($request->hasFile('photos') && $request->hasFile('video')) {
            $validator->after(function ($validator) {
                $validator->errors()->add('media', 'You can upload either photos or a video — not both.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Upload photos
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = '/storage/' . $photo->store('quotes/photos', 'public');
            }
        }

        // Upload video
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = '/storage/' . $request->file('video')->store('quotes/videos', 'public');
        }

        // Format date & time
        $date = Carbon::createFromFormat('m/d/Y', $request->date)->format('Y-m-d');
        // $time = Carbon::parse($request->time)->format('H:i');
        // Carbon::createFromFormat('H:i', $request->time)->format('g:i A');

        // Create quote
        $quote = Quote::create([
            'user_id' => Auth::id(),
            'service' => $request->service,
            'describe_issue' => $request->describe_issue,
            'property_type' => $request->property_type,
            'service_type' => $request->service_type,
            'pool_depth' => $request->pool_depth,
            'date' => $date,
            'zip_code' => $request->zip_code,
            'address' => $request->address,
            'expected_budget' => $request->expected_budget ?? 0,
            'photos' => json_encode($photoPaths),
            'video' => $videoPath,
            'status' => 'Pending',
            'is_paid' => false,
        ]);

        $quote->photos = json_decode($quote->photos, true);
        // $quote->time = Carbon::createFromFormat('H:i', $quote->time)->format('g:i A');

        // // Notify post user
        // $notifyUsers = User::where('role', 'PROVIDER')->get();
        // foreach ($notifyUsers as $notifyUser) {
        //     $notifyUser->notify(new CreateQuoteNotification($quote));
        // }

        return response()->json([
            'status' => true,
            'message' => 'Quote created successfully.',
            'quote' => $quote,
        ], 201);
    }



    // public function createQuote(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'service' => 'required|string|max:255',
    //         'describe_issue' => 'required|string',
    //         'property_type' => 'required|string|max:255',
    //         'service_type' => 'required|string|max:255',
    //         'pool_depth' => 'nullable|string|max:255',
    //         'date' => 'required|date',
    //         'time' => 'required|date_format:H:i',
    //         'zip_code' => 'required|string|max:5',
    //         'address' => 'required|string|max:255',
    //         'expected_budget' => 'nullable|numeric|min:0',

    //         'photo_1' => 'nullable',
    //         'photo_2' => 'nullable',
    //         'photo_3' => 'nullable',
    //         'photo_4' => 'nullable',
    //         'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime|max:10240',
    //     ]);

    //     // Custom validation: Prevent both video & images together
    //     $hasAnyPhoto = $request->hasFile('photo_1') || $request->hasFile('photo_2') ||
    //         $request->hasFile('photo_3') || $request->hasFile('photo_4');

    //     if ($hasAnyPhoto && $request->hasFile('video')) {
    //         $validator->after(function ($validator) {
    //             $validator->errors()->add('media', 'You can upload either photos or a video — not both.');
    //         });
    //     }

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     // Upload images (if any)
    //     $photoPaths = [];
    //     foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4'] as $photoField) {
    //         if ($request->hasFile($photoField)) {
    //             $photoPaths[] = '/storage/' . $request->file($photoField)->store('quotes/photos', 'public');
    //         }
    //     }

    //     // Upload video (if any)
    //     $videoPath = null;
    //     if ($request->hasFile('video')) {
    //         $videoPath = '/storage/' . $request->file('video')->store('quotes/videos', 'public');
    //     }

    //     // Format date & time
    //     $date = Carbon::createFromFormat('m/d/Y', $request->date)->format('Y-m-d');
    //     $time = Carbon::parse($request->time)->format('H:i');

    //     // Create Quote
    //     $quote = Quote::create([
    //         'user_id' => Auth::id(),
    //         'service' => $request->service,
    //         'describe_issue' => $request->describe_issue,
    //         'property_type' => $request->property_type,
    //         'service_type' => $request->service_type,
    //         'pool_depth' => $request->pool_depth ?? null,
    //         'date' => $date,
    //         'time' => $time,
    //         'zip_code' => $request->zip_code,
    //         'address' => $request->address,
    //         'expected_budget' => $request->expected_budget ?? 0,
    //         'photos' => json_encode($photoPaths),
    //         'video' => $videoPath,
    //         'status' => 'Pending',
    //         'is_paid' => false,
    //     ]);

    //     $quote->photos = $photoPaths;

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Quote created successfully.',
    //         'quote' => $quote,
    //     ], 201);
    // }

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

            $quote->user->avatar = $quote->user->avatar
                ? asset($quote->user->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($quote->user->full_name);
        }

        return response()->json([
            'status' => true,
            'message' => 'Get all quotes',
            'quotes' => $quotes
        ]);
    }
    public function searchProvider(Request $request)
    {
        $search = trim($request->query('search'));

        if (empty($search)) {
            return response()->json([
                'status' => true,
                'message' => 'No search keyword provided.',
                'data' => [],
            ]);
        }

        $providers = User::where('role', 'PROVIDER')
            ->where('full_name', 'like', '%' . $search . '%')
            ->get();

        foreach ($providers as $provider) {
            $ratingStats = Review::where('provider_id', $provider->id)
                ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
                ->first();

            $provider->average_rating = $ratingStats->average_rating
                ? number_format($ratingStats->average_rating, 1)
                : 0;
        }

        return response()->json([
            'status' => true,
            'message' => 'Provider search result',
            'data' => $providers,
        ]);

    }
    public function getMyQuotes(Request $request)
    {
        $query = Quote::where('user_id', Auth::id())->where('status', 'Pending');
        $quotes = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        foreach ($quotes as $quote) {
            $quote->photos = json_decode($quote->photos);
            $quote->scheduled_date = Carbon::createFromFormat('Y-m-d', $quote->date)
                ->format('M d, Y h:i A');
        }

        return response()->json([
            'status' => true,
            'isStatus' => 'Pending',
            'message' => 'Get my all quotes',
            'quotes' => $quotes
        ]);
    }
    public function viewQuote($id = null)
    {
        $quote = Quote::with('user.profile')->find($id);

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found.',
            ], 404);
        }

        // ✅ Decode photos safely
        $quote->photos = is_array($quote->photos)
            ? $quote->photos
            : json_decode($quote->photos, true) ?? [];

        // ✅ Job accept rate
        $profile = $quote->user->profile ?? null;

        if ($profile) {
            $totalOrders = $profile->order_accept + $profile->canceled_order;
            $acceptRate = $totalOrders > 0
                ? round(($profile->order_accept / $totalOrders) * 100)
                : 0;

            $profile->job_accept_rate = $acceptRate . '%';
            $profile->total_job_posted = Quote::where('user_id', $quote->user_id)->count();
        }

        return response()->json([
            'status' => true,
            'message' => 'View quote',
            'data' => $quote,
        ]);
    }
    public function deleteQuote($id = null)
    {
        $quote = Quote::find($id);

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote not found.'
            ], 404);
        }

        // 🔒 Check: Logged-in user কি এই quote-এর মালিক?
        if ($quote->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to delete this quote.'
            ], 403);
        }

        // ✅ Delete quote
        $quote->delete();

        return response()->json([
            'status' => true,
            'message' => 'Quote deleted successfully.'
        ]);
    }




}
