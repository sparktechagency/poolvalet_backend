<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManageController extends Controller
{
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        // ✅ Query builder শুরু
        $query = User::where('role', '!=', 'ADMIN')->select('id', 'full_name', 'email', 'avatar', 'role');

        // ✅ Search functionality: name বা email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($perPage);

        // ✅ Format each user
        foreach ($users as $user) {
            $user->role = $user->role === 'USER' ? 'HOME OWNER' : 'PROVIDER';

            $user->avatar = $user->avatar
                ? asset($user->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name);
        }

        return response()->json([
            'status' => true,
            'message' => 'User list fetched successfully.',
            'users' => $users
        ]);
    }

    public function deleteUser($id = null)
    {
        $user = User::where('id', $id)->where('role', '!=', 'ADMIN')->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Optional: delete related quotes if needed
        // $user->quotes()->delete();

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully.'
        ]);
    }

    public function viewUser($id = null)
    {
        // User fetch with profile relationship, excluding admin role
        $user = User::with('profile')
            ->where('id', $id)
            ->where('role', '!=', 'ADMIN')
            ->select('id', 'full_name', 'email', 'avatar', 'role')
            ->first();

        // User not found
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $user->avatar = $user->avatar
            ? asset($user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name);

        // Set readable role name
        $user->role = match ($user->role) {
            'USER' => 'HOME OWNER',
            'PROVIDER' => 'PROVIDER',
            default => ucfirst(strtolower($user->role)),
        };

        if (User::where('id', $id)->first()->role == 'USER') {
            $col = 'user_id';
            $with = 'provider';
        } elseif (User::where('id', $id)->first()->role == 'PROVIDER') {
            $col = 'provider_id';
            $with = 'user';
        }

        $transactions = Transaction::with($with)->where($col, $id)->get();

        foreach ($transactions as $transaction) {
            $transaction->$with->avatar = $transaction->$with->avatar
                ? asset($transaction->$with->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($transaction->$with->full_name);
        }

        return response()->json([
            'status' => true,
            'message' => 'User with profile loaded successfully.',
            'user' => $user,
            'transactions' => $transactions
        ]);
    }


}
