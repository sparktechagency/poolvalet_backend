<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyOTPMail;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'google_id' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 400);
        }

        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            $socialMatch = $request->has('google_id') && $existingUser->google_id === $request->google_id;

            if ($socialMatch) {

                Auth::login($existingUser);

                $tokenExpiry = Carbon::now()->addDays(7);
                $customClaims = ['exp' => $tokenExpiry->timestamp];
                $token = JWTAuth::customClaims($customClaims)->fromUser($existingUser);

                return response()->json([
                    'status' => true,
                    'message' => 'Login successful',
                    'token' => $token
                ], 200);
            } elseif (is_null($existingUser->google_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User already exists. Please sign in manually.'
                ], 422);
            } else {
                $existingUser->update([
                    'google_id' => $request->google_id ?? $existingUser->google_id,
                ]);

                Auth::login($existingUser);

                $tokenExpiry = Carbon::now()->addDays(7);
                $customClaims = ['exp' => $tokenExpiry->timestamp];
                $token = JWTAuth::customClaims($customClaims)->fromUser($existingUser);

                return response()->json([
                    'status' => true,
                    'message' => 'Login successful',
                    'token' => $token
                ], 200);
            }
        }

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('avatars', $filename, 'public');
            $avatarPath = '/storage/' . $filepath;
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(16)),
            'avatar' => $avatarPath ?? null,
            'role' => 'USER',
            'google_id' => $request->google_id ?? null,
            'status' => 'active',
        ]);

        Profile::create([
            'user_id' => $user->id,
        ]);

        Auth::login($user);

        $tokenExpiry = Carbon::now()->addDays(7);
        $customClaims = ['exp' => $tokenExpiry->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        return response()->json([
            'status' => true,
            'message' => 'User registered and logged in successfully.',
            'token' => $token,
            'data' => $user
        ], 200);
    }

    public function register(Request $request)
    {

        // create otp
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        // Send OTP Email
        $email_otp = [
            'userName' => explode('@', $request->email)[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];

        // validation roles
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:1,2',
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'role' => $request->role == 1 ? 'USER' : 'PROVIDER',
            'full_name' => ucfirst($request->full_name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => $otp_expires_at,
        ]);

        Profile::create([
            'user_id' => $user->id,
        ]);

        try {
            Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        // json response
        return response()->json([
            'status' => true,
            'message' => 'Register successfully, OTP send you email, please verify your account'
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        // check otp
        if ($user->otp_expires_at > Carbon::now()) {

            // user status update
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->otp_verified_at = Carbon::now();
            $user->status = 'active';
            $user->save();

            // custom token time
            $tokenExpiry = Carbon::now()->addDays(7);
            $customClaims = ['exp' => $tokenExpiry->timestamp];
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);

            // json response
            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $tokenExpiry,
                // 'expires_in' => $tokenExpiry->diffInSeconds(Carbon::now()),
                // 'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ], 200);
        } else {

            return response()->json([
                'status' => false,
                'message' => 'OTP expired time out'
            ], 401);
        }
    }

    public function resendOtp(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Check if User Exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        // update otp and otp expired at
        $user->otp = $otp;
        $user->otp_expires_at = $otp_expires_at;
        $user->otp_verified_at = null;
        $user->save();

        // Send OTP Email
        $data = [
            'userName' => explode('@', $request->email)[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];

        try {
            Mail::to($user->email)->send(new VerifyOTPMail($data));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP resend to your email'
        ], 200);
    }

    public function login(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'remember_me' => 'sometimes|boolean'
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Check if User Exists
        $user = User::where('email', $request->email)->first();

        // User Not Found
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Not found',
            ], 404);
        }

        // Check account status
        if ($user->status !== 'Active') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        // Generate JWT Token with remember me
        $tokenExpiry = $request->remember_me == '1' ? Carbon::now()->addDays(30) : Carbon::now()->addDays(7);
        $customClaims = ['exp' => $tokenExpiry->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        // Return Success Response
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $tokenExpiry,
            // 'expires_in' => $tokenExpiry->diffInSeconds(Carbon::now()),
            'user' => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => true,
                'message' => 'Logged out successful'
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        // Return Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Check if User Exists
        $user = User::where('email', $request->email)->first();

        // User Not Found
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        // create otp
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        // update otp and otp veridied and otp expired at
        $user->otp_verified_at = null;
        $user->otp = $otp;
        $user->otp_expires_at = $otp_expires_at;
        $user->save();

        $data = [
            'userName' => explode('@', $request->email)[0],
            'otp' => $otp,
            'validity' => '10 minutes'
        ];

        try {
            Mail::to($request->email)->send(new VerifyOTPMail($data));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP send to your email'
        ], 200);
    }

    public function changePassword(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed'
        ]);

        // Return Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Check if User Exists
        $user = User::where('id', Auth::id())->first();

        // User Not Found
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 404);
        }

        if ($user->status == 'Active') {
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'Password change successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized user'
            ]);
        }
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:8',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid current password!',
            ]);
        }
    }

    public function getProfile()
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Not found'
            ], 404);
        }

        $user->avatar = $user->avatar != null ? $user->avatar : 'https://ui-avatars.com/api/?background=random&name=' . $user->full_name;

        return response()->json([
            'status' => true,
            'message' => 'Your profile',
            'data' => $user
        ], 200);
    }
}
