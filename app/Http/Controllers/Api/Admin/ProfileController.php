<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
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

        return response()->json([
            'status' => true,
            'message' => 'Admin profile updated successfully.',
            'data' => $user
        ]);
    }
}
