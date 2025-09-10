<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Image;

class ChatController extends Controller
{
    public function storeMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        // Check if user is trying to send files to themselves
        if ($request->receiver_id == Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot send files to yourself'
            ], 422);
        }

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

        foreach ($messages as $item) {
            $item->files = json_decode($item->files);
        }

        $user = User::where('id', $request->receiver_id)->select('id', 'full_name', 'avatar', 'role')->first();
        $user->avatar = $user->avatar
            ? asset($user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name);


        return response()->json([
            'status' => true,
            'receiver_user' => $user,
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
            // Get the last message for the specific user
            $lastMessage = Chat::where(function ($query) use ($chatUser) {
                $query->where('sender_id', Auth::id())
                    ->where('receiver_id', $chatUser->id);
            })
                ->orWhere(function ($query) use ($chatUser) {
                    $query->where('sender_id', $chatUser->id)
                        ->where('receiver_id', Auth::id());
                })
                ->latest()
                ->first();

            if ($lastMessage) {
                $chatUser->last_message = $lastMessage->message;
                $chatUser->last_message_date = $lastMessage->created_at;
            }

            $chatUser->avatar = $chatUser->avatar
                ? asset($chatUser->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($chatUser->full_name);

            // $chatUser->unreadCount = Chat::where('receiver_id', Auth::id())->where('sender_id',5)
            //     ->where('is_read', false)
            //     ->count();

            // ✅ Count unread messages **from this chat user only**
    $chatUser->unreadCount = Chat::where('receiver_id', Auth::id())
        ->where('sender_id', $chatUser->id)
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
        // $request->validate([
        //     'sender_id' => 'required|exists:users,id',
        // ]);


        $users = Chat::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->where('sender_id',$request->sender_id)
            ->get();

        foreach ($users as $user) {
            $user->update(['is_read' => true]);
            $user->save();
        }

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

        //    Chat::where('sender_id',Auth::id())->where('receiver_id',$request->receiver_id)->delete();




        // foreach ($myChats as $myChat) {


        //     $myChat->message = 'This message was deleted';
        //     $myChat->save();
        // }


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
        $lastMessage = Chat::where(function ($q) {
            $q->where('sender_id', Auth::id())
                ->orWhere('receiver_id', Auth::id());
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
    public function sendFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime|max:512000',
        ]);

        // Custom validation: Prevent both photos and video
        if ($request->hasFile('photos') && $request->hasFile('video')) {
            $validator->after(function ($validator) {
                $validator->errors()->add('media', 'You can upload either photos or a video — not both.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Check if user is trying to send files to themselves
        if ($request->receiver_id == Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot send files to yourself'
            ], 422);
        }

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = '/storage/' . $photo->store('quotes/photos', 'public');
            }
        }

        // Upload video
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = '/storage/' . $request->file('video')->store('quotes/videos', 'public');
        }

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'files' => $request->photos ? json_encode($photoPaths) : json_encode($videoPath)
        ]);

        $chat->files = json_decode($chat->files);

        return response()->json([
            'status' => true,
            'message' => 'Files sent',
            'data' => $chat,
        ]);
    }
}
