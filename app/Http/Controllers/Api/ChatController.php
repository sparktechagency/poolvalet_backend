<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function storeMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Message sent',
            'data' => $chat,
        ]);
    }

    public function getMessages(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $messages = Chat::where(function ($q) use ($request) {
            $q->where('sender_id', Auth::id())
                ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($q) use ($request) {
            $q->where('sender_id', $request->receiver_id)
                ->where('receiver_id', Auth::id());
        })->orderBy('created_at')->get();

        return response()->json([
            'status' => true,
            'data' => $messages,
        ]);
    }

    public function chatLists(Request $request)
    {
        $userId = Auth::id();

        $chatUsers = Chat::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender:id,full_name,role,avatar', 'receiver:id,full_name,role,avatar'])
            ->latest('updated_at')
            ->get()
            ->map(function ($chat) use ($userId) {
                return $chat->sender_id == $userId ? $chat->receiver : $chat->sender;
            })
            ->unique('id')
            ->filter(function ($user) use ($request) {
                return stripos($user->full_name, $request->search) !== false;
            })
            ->filter(function ($user) use ($request) {
                return stripos($user->role, $request->role) !== false;
            })
            ->values();



        foreach ($chatUsers as $chatUser) {
            $chatUser->last_message = Chat::where('sender_id', Auth::id())->where('receiver_id', $chatUser->id)->latest()->first()->message;

            $chatUser->avatar = $chatUser->avatar
                ? asset($chatUser->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($chatUser->full_name);
        }

        foreach ($chatUsers as $chatUser) {
            $chatUser->unreadCount = Chat::where('receiver_id', $chatUser->id)
                ->where('is_read', false)
                ->count();
        }

        return response()->json([
            'status' => true,
            'message' => $request->search ? 'Search results' : 'My chat lists',
            'data' => $chatUsers
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = Chat::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => true,
            'unread' => $count,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
        ]);

        Chat::where('sender_id', $request->sender_id)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Messages marked as read',
        ]);
    }

    public function deleteConversation(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        Chat::where(function ($q) use ($request) {
            $q->where('sender_id', Auth::id())
                ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($q) use ($request) {
            $q->where('sender_id', $request->receiver_id)
                ->where('receiver_id', Auth::id());
        })->delete();

        return response()->json([
            'status' => true,
            'message' => 'Conversation deleted',
        ]);
    }

    public function lastMessageTime(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $lastMessage = Chat::where(function ($q) use ($request) {
            $q->where('sender_id', $request->user_id)
                ->orWhere('receiver_id', $request->user_id);
        })->latest()->first();

        if (!$lastMessage) {
            return response()->json([
                'status' => true,
                'message' => 'No activity'
            ]);
        }

        $lastMessageTime = $lastMessage->created_at;
        $currentTime = now();
        $diff = $lastMessageTime->diffInMinutes($currentTime);
        $isActive = $diff < 1 ? 'active' : $lastMessageTime->diffForHumans();

        return response()->json([
            'status' => true,
            // 'last_message_time' => $lastMessageTime->format('H:i:s'),
            // 'now_time' => $currentTime->format('H:i:s'),
            // 'diff' => $diff,
            'message' => $isActive
        ]);

    }

}
