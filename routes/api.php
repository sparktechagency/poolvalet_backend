<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Admin\TransactionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\QuoteListingController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Admin\UserManageController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\User\QuoteController;
use App\Http\Controllers\Api\Provider\BrowseQuoteController;
use App\Http\Controllers\Api\Provider\BuyPlanController;
use App\Http\Controllers\Api\Provider\MyServiceController;
use App\Http\Controllers\Api\Provider\StripeConnectController;
use App\Http\Controllers\Api\User\BidController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\TopProviderController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// public route for user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('/social-login', [AuthController::class, 'socialLogin']);
// get page
Route::get('/get-page', [PageController::class, 'getPage']);

// private route for user
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/get-profile', [AuthController::class, 'getProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

     // notification
    Route::get('/get-notifications', [NotificationController::class, 'getNotifications']);
    Route::patch('/read', [NotificationController::class, 'read']);
    Route::patch('/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notification-status', [NotificationController::class, 'status']);

    // get category lists
    Route::get('/get-category-lists', [CategoryController::class, 'getCategories']);

     // subscriptions
        Route::get('/get-subscriptions-lists', [SubscriptionController::class, 'getSubscriptions']);
        Route::get('/get-subscription/{id}', [SubscriptionController::class, 'viewSubscription']);

    // ADMIN
    Route::middleware('admin')->prefix('admin')->group(function () {
        // dashboard
        Route::get('get-data',[DashboardController::class,'getData']);
        Route::get('get-chart',[DashboardController::class,'getChart']);
        
        // users manage
        Route::get('/get-users', [UserManageController::class, 'getUsers']);
        Route::delete('/delete-user/{id?}', [UserManageController::class, 'deleteUser']);
        Route::get('/view-user/{id?}', [UserManageController::class, 'viewUser']);
        Route::get('/activities-chart', [UserManageController::class, 'activitiesChart']);

        // quote listing
        Route::get('/get-quote-listing', [QuoteListingController::class, 'getQuoteListing']);
        Route::get('/view-quote/{id?}', [QuoteListingController::class, 'viewQuote']);

        // transactions
        Route::get('/get-transactions', [TransactionController::class, 'getTransactions']);

        // categories
        Route::post('/add-category', [CategoryController::class, 'addCategory']);
        Route::get('/get-categories', [CategoryController::class, 'getCategories']);
        Route::get('/view-category/{id?}', [CategoryController::class, 'viewCategory']);
        Route::put('/edit-category/{id?}', [CategoryController::class, 'editCategory']);
        Route::delete('/delete-category/{id?}', [CategoryController::class, 'deleteCategory']);

        // subscriptions
        Route::get('/get-subscriptions', [SubscriptionController::class, 'getSubscriptions']);
        Route::put('/update-subscription/{id}', [SubscriptionController::class, 'updateSubscription']);
        Route::get('/view-subscription/{id}', [SubscriptionController::class, 'viewSubscription']);

        // settings
        Route::patch('/update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('/create-page', [PageController::class, 'createPage']);
    });

    // RPOVIDER
    Route::middleware('provider')->prefix('provider')->group(function () {
        // browse quotes
        Route::get('/browse-quotes', [BrowseQuoteController::class, 'browseQuotes']);
        Route::get('/view-browse-quote/{id?}', [BrowseQuoteController::class, 'viewBrowseQuote']);
        Route::post('/accept-budget', [BrowseQuoteController::class, 'acceptBudget']);
        Route::post('/apply-bid', [BrowseQuoteController::class, 'applyBid']);
        Route::get('/bidding-lists', [BrowseQuoteController::class, 'biddingLists']);
        Route::get('/get-your-bid', [BrowseQuoteController::class, 'getYourBid']);
        Route::put('/edit-your-bid', [BrowseQuoteController::class, 'editYourBid']);
        Route::patch('/make-final-save-your-bid', [BrowseQuoteController::class, 'makeFinalSaveYourBid']);

        // my services
        Route::get('/my-service-quotes', [MyServiceController::class, 'myServiceQuotes']);
        Route::delete('/cancel-bid/{id?}', [MyServiceController::class, 'cancelBid']);
        Route::patch('/mark-as-complete', [MyServiceController::class, 'markAsComplete']);
        Route::get('/my-earnings', [MyServiceController::class, 'myEarnings']);

        // buy plan
        Route::post('/buy-plan-intent', [BuyPlanController::class, 'buyPlanIntent']);
        Route::post('/buy-plan-success', [BuyPlanController::class, 'buyPlanSuccess']);
        Route::get('/current-plan', [BuyPlanController::class, 'currentPlan']);

        // connented account
        Route::get('/create-connected-account', [StripeConnectController::class, 'createConnectedAccount']);
    });

    // USER
    Route::middleware('user')->prefix('user')->group(function () {
        // quotes
        Route::post('/create-quote', [QuoteController::class, 'createQuote']);
        Route::get('/get-quotes', [QuoteController::class, 'getQuotes']);
        Route::get('/search-provider', [QuoteController::class, 'searchProvider']);

        // my quote
        Route::get('/get-my-quotes', [QuoteController::class, 'getMyQuotes']);
        Route::get('/view-quote/{id?}', [QuoteController::class, 'viewQuote']);
        Route::delete('/delete-quote/{id?}', [QuoteController::class, 'deleteQuote']);


        // bids
        Route::get('get-check-bids', [BidController::class, 'getCheckBids']);
        Route::get('get-accepted-bids', [BidController::class, 'getAcceptedBids']);
        Route::patch('accept-request', [BidController::class, 'acceptRequest']);
        Route::patch('cancel-order', [BidController::class, 'cancelOrder']);



        // review
        Route::post('/create-review', [ReviewController::class, 'createReview']);
        Route::get('/view-review/{id}', [ReviewController::class, 'viewReview']);
        Route::get('/get-reviews', [ReviewController::class, 'getReviews']);

        // top providers
        Route::get('/top-providers', [TopProviderController::class, 'topProviders']);
        Route::get('/view-provider/{id?}', [TopProviderController::class, 'viewProvider']);

        // payment
        Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/payment-success', [PaymentController::class, 'paymentSuccess']);
    });

    Route::middleware('user.provider')->group(function () {
        // profile for user/
        Route::patch('/edit-account', [ProfileController::class, 'editAccount']);
        Route::patch('/edit-address', [ProfileController::class, 'editAddress']);
        Route::get('/order-info', [ProfileController::class, 'orderInfo']);

        // chat
        Route::post('/store-message', [ChatController::class, 'storeMessage']);
        Route::get('/get-messages', [ChatController::class, 'getMessages']);
        Route::get('/chat-lists', [ChatController::class, 'chatLists']);
        Route::get('/unread-count', [ChatController::class, 'unreadCount']);
        Route::post('/mark-as-read', [ChatController::class, 'markAsRead']);
        Route::delete('/delete-conversation', [ChatController::class, 'deleteConversation']);
        Route::get('/last-message-time', [ChatController::class, 'lastMessageTime']);
        Route::post('/send-files', [ChatController::class, 'sendFiles']);
    });
});