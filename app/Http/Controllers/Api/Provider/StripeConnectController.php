<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;

class StripeConnectController extends Controller
{
    public function createConnectedAccount(Request $request)
    {
        $email = Auth::user()->email;

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $account = Account::create([
                'type' => 'express',
                'country' => 'US',
                // 'email' => $request->email,
                'email' => $email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);

            $customReturnUrl = url("/connected?status=success&email={$email}&account_id={$account->id}");

            $accountLink = AccountLink::create([
                'account' => $account->id,
                'refresh_url' => url('/vendor/reauth'),
                'return_url' => $customReturnUrl,
                'type' => 'account_onboarding',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Stripe Connect account created successfully',
                'onboarding_url' => $accountLink->url,
                'stripe_account_id' => $account->id,
            ]);
        } catch (Exception $e) {
            Log::error('Stripe Account Creation Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function handleConnectedAccount(Request $request)
    {
        $email = $request->email;
        $accountId = $request->account_id;

        if (!$email || !$accountId) {
            return response()->json([
                'status' => false,
                'message' => 'Missing required parameters.'
            ], 400);
        }

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $account = Account::retrieve($accountId);

            if (!$account->charges_enabled) {
                return response()->json([
                    'status' => false,
                    'message' => 'Stripe account is not yet verified. Please complete onboarding.',
                    'stripe_account' => $account,
                ]);
            }

            $user = User::where('email', $email)->first();
            if ($user) {
                $user->stripe_account_id = $accountId;
                $user->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Stripe account connected and verified successfully.',
                'stripe_account' => $account,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Connected Account Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
