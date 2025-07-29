<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
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

                // 'payment_method' => 'pm_card_visa', // ✅ Add this line
                // 'confirm' => true, // ✅ This forces confirm step
            ]);

            $userProfile = Profile::where('user_id', Auth::id())->first();
            $userProfile->increment('completed_services');

            $userProfile->total_pay = $userProfile->total_pay + $amountInCents / 100;
            $userProfile->save();

            $providerProfile = Profile::where('user_id', $request->provider_id)->first();
            $providerEarning = ($amountInCents * 0.95) / 100;
            $providerProfile->total_earnings = $userProfile->total_earnings + $providerEarning;
            $providerProfile->save();

            $adminProfile = Profile::where('user_id', 1)->first();
            $adminEarning = ($amountInCents * 0.05) / 100;
            $adminProfile->total_earnings = $adminProfile->total_earnings + $adminEarning;
            $adminProfile->save();

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
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required',
            'provider_id' => 'required|numeric|exists:users,id',
            'quote_id' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {

           $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'requires_payment_method') {  // succeeded or requires_payment_method

                $transaction = Transaction::create([
                    'payment_intent_id' => $request->payment_intent_id,
                    'user_id' => Auth::id(),
                    'provider_id' => $request->provider_id,
                    'quote_id' => $request->quote_id,
                    'date' => Carbon::now(),
                    'name' => Auth::user()->full_name,
                    'service_name' => Quote::where('id',$request->quote_id)->first()->service,
                    'amount' => $request->amount,
                    'status' => 'Completed',

                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Transaction recorded successfully',
                    'data' => $transaction,
                ], 200);

            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment failed. Status: ' . $paymentIntent->status,
                ], 400);
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
