<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        // ✅ Step 1: Validate input
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|numeric|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'payment_method_types' => 'required|string', // example: card
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // ✅ Step 2: Find provider
        $provider = User::find($request->provider_id);

        if (!$provider->stripe_account_id) {
            return response()->json([
                'status' => false,
                'message' => 'Provider does not have a connected Stripe account.'
            ], 400);
        }

        // ✅ Step 3: Stripe setup
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $amountInCents = (int) ($request->amount * 100);
        $applicationFee = (int) round($amountInCents * 0.05); // 5% fee to admin

        try {
            // ✅ Step 4: Create PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'usd',
                'payment_method_types' => [$request->payment_method_types],
                'application_fee_amount' => $applicationFee,
                'transfer_data' => [
                    'destination' => $provider->stripe_account_id, // payout to provider
                ],
                'metadata' => [
                    'provider_id' => $provider->id,
                    'payer_id' => Auth::id(),
                    'description' => 'Payment from user to provider with 5% admin fee'
                ],

                'payment_method' => 'pm_card_visa', // ✅ Add this line
                'confirm' => true, // ✅ This forces confirm step
            ]);

            // ✅ Step 5: Return intent info
            return response()->json([
                'status' => true,
                'message' => 'PaymentIntent created successfully.',
                'client_secret' => $paymentIntent->client_secret, // for frontend
                'payment_intent' => $paymentIntent
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function paymentSuccess(Request $request)
    {
        //
    }
}
