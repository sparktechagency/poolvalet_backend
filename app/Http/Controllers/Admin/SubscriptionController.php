<?php

namespace App\Http\Controllers\Admin;

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

    public function updateSubscription(Request $request, $id)
    {
        $request->validate([
            'number_of_quotes' => 'nullable|integer|min:0',
        ]);

        $subscription = Subscription::where('id', $id)->first();

        if ($subscription) {
            $subscription->update([
                'number_of_quotes' => $request->number_of_quotes,
            ]);
        } 

        return response()->json([
            'status' => true,
            'message' => 'Subscription updated successfully.',
            'subscription' => $subscription
        ]);
    }

}
