<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function getSubscriptions()
    {
        $subscriptions = Subscription::orderBy('id', 'asc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Get subscriptions',
            'subscriptions' => $subscriptions
        ]);
    }

    public function updateSubscription(Request $request, $id = null)
    {
        $request->validate([
            'number_of_quotes' => 'nullable|integer|min:0',
            'price' => 'nullable|integer|min:0',
        ]);

        $subscription = Subscription::where('id', $id)->first();

        if ($subscription) {
            $subscription->update([
                'number_of_quotes' => $request->number_of_quotes,
                'price' => $request->price,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Subscription updated successfully.',
            'subscription' => $subscription
        ]);
    }

    public function viewSubscription($id = null)
    {
        $subscription = Subscription::find($id);
        if (!$subscription) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription not found'
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'View subscription',
            'data' => $subscription
        ]);
    }

}
