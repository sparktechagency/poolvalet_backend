<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $users = User::where('role', '!=', 'ADMIN')->paginate($perPage);

        foreach ($users as $user) {
            $user->avatar = $user->avatar != null ? $user->avatar : 'https://ui-avatars.com/api/?background=random&name=' . $user->full_name;
        }

        return response()->json([
            'status' => true,
            'message' => 'Get all users',
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
        $user = User::with('profile')->where('id', $id)->where('role', '!=', 'ADMIN')->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $user->avatar = $user->avatar != null ? $user->avatar : 'https://ui-avatars.com/api/?background=random&name=' . $user->full_name;

        return response()->json([
            'status' => true,
            'message' => 'View user with profile',
            'user' => $user
        ]);
    }


}
