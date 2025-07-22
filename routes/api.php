<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Admin\ProfileController;
use App\Http\Controllers\Api\Admin\QuoteListingController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Admin\UserManageController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\QuoteController;
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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/get-profile', [AuthController::class, 'getProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // ADMIN
    Route::middleware('admin')->prefix('admin')->group(function () {
        // users manage
        Route::get('/get-users', [UserManageController::class, 'getUsers']);
        Route::delete('/delete-user/{id?}', [UserManageController::class, 'deleteUser']);
        Route::get('/view-user/{id?}', [UserManageController::class, 'viewUser']);

        // quote listing
        Route::get('/get-quote-listing', [QuoteListingController::class, 'getQuoteListing']);
        Route::get('/view-quote/{id?}', [QuoteListingController::class, 'viewQuote']);

        // categories
        Route::post('/add-category', [CategoryController::class, 'addCategory']);
        Route::put('/edit-category/{id?}', [CategoryController::class, 'editCategory']);
        Route::delete('/delete-category/{id?}', [CategoryController::class, 'deleteCategory']);

        // subscriptions
        Route::get('/get-subscriptions', [SubscriptionController::class, 'getSubscriptions']);
        Route::put('/update-subscription/{id}', [SubscriptionController::class, 'updateSubscription']);

        // settings
        Route::patch('/update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('/create-page', [PageController::class, 'createPage']);
        Route::get('/get-page', [PageController::class, 'getPage']);
    });

    // COMPANY
    Route::middleware('provider')->prefix('provider')->group(function () {
        //
    });

    // USER
    Route::middleware('user')->prefix('user')->group(function () {
        Route::post('/create-quote', [QuoteController::class, 'createQuote']);
        Route::get('/get-quotes', [QuoteController::class, 'getQuotes']);
        Route::get('/get-my-quotes', [QuoteController::class, 'getMyQuotes']);
    });
});