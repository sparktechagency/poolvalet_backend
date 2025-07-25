<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;

class StripeConnectController extends Controller
{
    public function createConnectedAccount()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $user = Auth::user();

        // Stripe account already exists?
        if ($user->stripe_account_id) {
            return response()->json([
                'status' => false,
                'message' => 'You already have a connected Stripe account.',
                'stripe_account_id' => $user->stripe_account_id
            ]);
        }

        // ✅ Create new Express account
        $account = Account::create([
            'type' => 'express',
            'country' => 'US',
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);

        // ✅ Store account ID to user
        $user->update([
            'stripe_account_id' => $account->id,
        ]);

        // ✅ Create onboarding link
        $accountLink = AccountLink::create([
            'account' => $account->id,
            'refresh_url' => route('stripe.refresh'),
            'return_url' => route('stripe.success'),
            'type' => 'account_onboarding',
        ]);

        // ✅ Redirect to onboarding
        return response()->json([
            'status'=> true,
            'message'=> 'Your connected account link',
            'link' => $accountLink->url
        ]);
    }

    public function onboardSuccess(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Stripe onboarding completed successfully.',
        ]);
    }

    public function onboardRefresh(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Stripe onboarding was canceled or needs to be restarted.',
        ]);
    }
}
