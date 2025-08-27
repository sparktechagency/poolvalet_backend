<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {

        // ✅ Validation Rules
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('id', Auth::id())->first();

        // ✅ Avatar Upload (if provided)
        if ($request->hasFile('avatar')) {

            $relativePath = parse_url($user->avatar, PHP_URL_PATH);

            // $paths = array_map(fn($url) => parse_url($url, PHP_URL_PATH), $urls);
            // foreach ($paths as $path) {
            //     Storage::disk('public')->delete(str_replace('/storage/', '', $path));
            // }
            // $relativePath = str_replace(url('/'), '', $user->avatar);

            // 🗑 Delete old avatar
            if ($relativePath && Storage::disk('public')->exists(str_replace('/storage/', '', $relativePath))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $relativePath));
            }

            // ✅ Upload new avatar
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('avatars', $filename, 'public');

            $avatarPath = '/storage/' . $filepath;
        } else {
            $avatarPath = $user->avatar;
        }

        // ✅ Update user fields safely

        $user->full_name = $request->full_name ?? $user->full_name;
        $user->contact_number = $request->contact_number ?? $user->contact_number;
        $user->location = $request->location ?? $user->location;
        $user->avatar = $avatarPath;
        $user->save();

        $user->avatar = $user->avatar
            ? asset($user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name);

        return response()->json([
            'status' => true,
            'message' => 'Admin profile updated successfully.',
            'data' => $user
        ]);
    }

    public function editAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('id', Auth::id())->first();

        // ✅ Avatar Upload (if provided)
        if ($request->hasFile('avatar')) {

            $relativePath = parse_url($user->avatar, PHP_URL_PATH);

            if ($relativePath && Storage::disk('public')->exists(str_replace('/storage/', '', $relativePath))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $relativePath));
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('avatars', $filename, 'public');

            $avatarPath = '/storage/' . $filepath;
        } else {
            $avatarPath = $user->avatar;
        }

        $user->full_name = $request->full_name ?? $user->full_name;
        $user->email = $request->email ?? $user->email;
        $user->bio = $request->bio ?? $user->bio;
        $user->avatar = $avatarPath;
        $user->save();

        $user->avatar = $user->avatar
            ? asset($user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name);


        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user
        ]);
    }

    public function editAddress(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:255',
            'user_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone_number' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:5',
            'country' => 'nullable|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Profile::where('user_id', Auth::id())->first();

        $user->display_name = $request->display_name ?? $user->display_name;
        $user->user_name = $request->user_name ?? $user->user_name;
        // $user->full_name = $request->full_name ?? $user->full_name;
        $user->email = $request->email ?? $user->email;
        $user->phone_number = $request->phone_number ?? $user->phone_number;
        $user->state = $request->state ?? $user->state;
        $user->zip_code = $request->zip_code ?? $user->zip_code;
        $user->country = $request->country ?? $user->country;
        $user->save();


        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user
        ]);
    }

    public function orderInfo(Request $request)
    {

        $total_order_user = Quote::where('user_id', Auth::id())->count();

        $total_order_provider = Bid::where('provider_id', Auth::id())->where('bid_status', 'public')->count();

        $completed_order = Profile::where('user_id', Auth::id())->first()->completed_services;

        return response()->json([
            'status' => true,
            'message' => 'Order info',
            'total order' => Auth::user()->role == 'USER' ? $total_order_user : $total_order_provider,
            'pending order' => Auth::user()->role == 'USER' ? $total_order_user - $completed_order : $total_order_provider - $completed_order,
            'completed order' => $completed_order,
        ]);
    }
}
