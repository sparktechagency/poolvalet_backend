<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate($request->per_page ?? 10);


        return response()->json([
            'status' => true,
            'message' => 'Latest notifications',
            'data' => $notifications
        ]);
    }

    public function read(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $notification = DatabaseNotification::find($request->notification_id);

        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Notification readed'
        ]);
    }
    public function readAll(Request $request)
    {
        $ids = Auth::user()->unreadNotifications()->pluck('id')->toArray();
        DatabaseNotification::whereIn('id', $ids)->update(['read_at' => now()]);
        return response()->json([
            'status' => true,
            'message' => 'All Notifications are readed'
        ]);
    }
    public function status()
    {
        return response()->json([
            'status' => true,
            'message' => 'How much unreaded notifications',
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
