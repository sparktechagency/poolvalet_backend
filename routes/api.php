<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// public route for user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// private route for user
Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/get-profile', [AuthController::class, 'get-profile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // ADMIN
    Route::middleware('admin')->prefix('admin')->group(function () {
        //
    });

    // COMPANY
    Route::middleware('provider')->prefix('provider')->group(function () {
        //
    });

    // USER
    Route::middleware('user')->prefix('user')->group(function () {
        // 
    });
});