<?php

use App\Http\Controllers\Api\Provider\StripeConnectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/connected', [StripeConnectController::class, 'handleConnectedAccount']);

// Route::get('/stripe/success', [StripeConnectController::class, 'onboardSuccess'])->name('stripe.success');
// Route::get('/stripe/refresh', [StripeConnectController::class, 'onboardRefresh'])->name('stripe.refresh');