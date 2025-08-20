<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BuyPlanController extends Controller
{

    // public function PaymentIntent(Request $request)
    // {

    //     $plan_status = Plan::where('provider_id',Auth::user()->id)->get()->pluck('status');

    //     $active_plan = Plan::where('provider_id',Auth::user()->id)->where('status','Active')->first();

    //     if (in_array($plan_status, ['Active'])) {
    //             return response()->json([
    //                 'status'=> true,
    //                 'message'=> 'You have already a plan.',
    //                 'current_plan' => $active_plan
    //             ]);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         // 'user_id'        => 'required|numeric',
    //         'amount' => 'required',
    //         'payment_method_types' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'ok' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     Stripe::setApiKey(env('STRIPE_SECRET'));


    //     try {
    //         $paymentIntent = PaymentIntent::create([
    //             'amount' => $request->amount * 100,
    //             'currency' => 'usd',
    //             'payment_method_types' => [$request->payment_method_types],
    //             'metadata' => [
    //                 'user_id' => Auth::id(),
    //             ],
    //         ]);

    //         return response()->json([
    //             'ok' => true,
    //             'message' => 'Payment intent successfully created',
    //             'data' => $paymentIntent,
    //         ], 201);
    //     } catch (Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }


    // }

    public function buyPlanIntent(Request $request)
    {
        // ✅ Check if user already has an Active plan
        $activePlan = Plan::where('provider_id', Auth::id())
            ->where('status', 'Active')
            ->first();

        if ($activePlan) {
            return response()->json([
                'status' => true,
                'message' => 'You already have an active plan.',
                'current_plan' => $activePlan
            ]);
        }

        // ✅ Validate input
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'payment_method_types' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // ✅ Set Stripe API Key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // cents
                'currency' => 'usd',
                'payment_method_types' => [$request->payment_method_types], // example: 'card'
                'metadata' => [
                    'user_id' => Auth::id(),
                ],
            ]);

            $adminProfile = Profile::where('user_id', 1)->first();
            $adminEarning = $request->amount;
            $adminProfile->total_earnings = $adminProfile->total_earnings + $adminEarning;
            $adminProfile->save();

            return response()->json([
                'status' => true,
                'message' => 'Payment intent successfully created',
                'data' => $paymentIntent,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buyPlanSuccess(Request $request)
    {


        $subscription = Subscription::where('id', $request->subscription_id)->first();

        if ($subscription->id == '1') {

            // // ✅ Check if user already has an Active plan
            // $current_plan = Plan::where('provider_id', Auth::id())
            //     ->where('status', 'Active')
            //     ->first();
                
            // if ($current_plan) {
            //     return response()->json([
            //         'status' => true,
            //         'message' => 'You current plan.',
            //         'current_plan' => $current_plan
            //     ]);
            // }

            $check_free_plan = Plan::where('provider_id', Auth::id())->pluck('subscription_id')->toArray();

            if (in_array(1, $check_free_plan)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your already used free plan. Please buy a plan.'
                ]);
            }

            $plan = Plan::Create([
                'payment_intent_id' => $request->payment_intent_id ?? null,
                'provider_id' => Auth::id(),
                'subscription_id' => $request->subscription_id,
                'plan_name' => $subscription->plan_name,
                'price' => $subscription->price,
                'total_quotes' => $subscription->number_of_quotes,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Free plan created successfully',
                'data' => $plan,
            ], 200);
        }


        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required',
            'subscription_id' => 'required'
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
            if ($paymentIntent->status === 'succeeded') {  // succeeded or requires_payment_method

                $plan = Plan::Create([
                    'payment_intent_id' => $request->payment_intent_id,
                    'provider_id' => Auth::id(),
                    'subscription_id' => $request->subscription_id,
                    'plan_name' => $subscription->plan_name,
                    'price' => $subscription->price,
                    'total_quotes' => $subscription->number_of_quotes,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Payment done and plan created successfully',
                    'data' => $plan,
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

    public function currentPlan(Request $request)
    {
        // ✅ Check if user already has an Active plan
        $current_plan = Plan::where('provider_id', Auth::id())
            ->where('status', 'Active')
            ->first();

        if (!$current_plan) {
            return response()->json([
                'status' => false,
                'message' => 'You have no current plan.'
            ]);
        }

        if ($current_plan) {
            return response()->json([
                'status' => true,
                'message' => 'You current plan.',
                'current_plan' => $current_plan
            ]);
        }
    }
}
