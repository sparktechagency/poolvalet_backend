<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManageController extends Controller
{
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $query = User::where('role', '!=', 'ADMIN')->select('id', 'full_name', 'email', 'avatar', 'role');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $users = $query->latest()->paginate($perPage);
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
        $user = User::with('profile')
            ->where('id', $id)
            ->where('role', '!=', 'ADMIN')
            ->select('id', 'full_name', 'email', 'avatar', 'role')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $user->avatar = $user->avatar
            ? asset($user->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->full_name);

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
    public function activitiesChart(Request $request)
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $allDates = collect();
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->copy()
        );
        foreach ($period as $date) {
            $allDates->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'total_transactions' => 0,
                'total_amount' => number_format(0, 2),
            ]);
        }

        $totalEarning = Transaction::selectRaw('
        DATE(created_at) as date,
        COUNT(*) as total_transactions,
        SUM(amount) as total_amount
    ')
            ->where('provider_id', $request->provider_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total_transactions', 'date')
            ->map(function ($transactions, $date) use ($request) {
                $totalAmount = Transaction::whereDate('created_at', $date)
                    ->where('provider_id', request()->provider_id)
                    ->sum('amount');

                return [
                    'date' => $date,
                    'day' => Carbon::parse($date)->format('D'),
                    'total_transactions' => (int) $transactions >= 1000
                        ? number_format($transactions / 1000) . 'k'
                        : number_format($transactions),
                    'total_amount' => (float) $totalAmount >= 1000
                        ? number_format($totalAmount / 1000, 2) . 'k'
                        : number_format($totalAmount, 2),
                ];
            });

        $totalEarningFull = $allDates->map(function ($day) use ($totalEarning) {
            return $totalEarning[$day['date']] ?? $day;
        });

        $completedService = Bid::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('provider_id', $request->provider_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->pluck('count', 'date');

        $completedServiceFull = $allDates->map(function ($day) use ($completedService) {
            return [
                'date' => $day['date'],
                'day' => $day['day'],
                'count' => isset($completedService[$day['date']])
                    ? ($completedService[$day['date']] >= 1000
                        ? number_format($completedService[$day['date']] / 1000) . 'k'
                        : number_format($completedService[$day['date']]))
                    : '0'
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Activities chart data',
            'completed_service' => $completedServiceFull,
            'total_earning' => $totalEarningFull,
        ]);
    }
}
