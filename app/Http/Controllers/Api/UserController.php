<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\IncomeResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Subscription;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = UserProfileHelper::resolveFromRequest($request);

        if (!$user) {
            return response()->json(['data' => null]);
        }

        return new UserResource($user);
    }

    public function fetchAll(Request $request)
    {
        $user = UserProfileHelper::resolveFromRequest($request);

        $expenseQuery = $user
            ? Expense::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->orderByDesc('date')
                ->orderByDesc('id')
            : null;

        $incomeQuery = $user
            ? Income::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->orderByDesc('date')
                ->orderByDesc('id')
            : null;

        if ($expenseQuery && $request->filled('from')) {
            $expenseQuery->whereDate('date', '>=', $request->query('from'));
        }

        if ($expenseQuery && $request->filled('to')) {
            $expenseQuery->whereDate('date', '<=', $request->query('to'));
        }

        if ($incomeQuery && $request->filled('from')) {
            $incomeQuery->whereDate('date', '>=', $request->query('from'));
        }

        if ($incomeQuery && $request->filled('to')) {
            $incomeQuery->whereDate('date', '<=', $request->query('to'));
        }

        return response()->json([
            'data' => [
                'user' => $user ? (new UserResource($user))->resolve() : null,
                'expenses' => ExpenseResource::collection(
                    $expenseQuery ? $expenseQuery->get() : collect()
                )->resolve(),
                'incomes' => IncomeResource::collection(
                    $incomeQuery ? $incomeQuery->get() : collect()
                )->resolve(),
                'subscriptions' => SubscriptionResource::collection(
                    $user
                        ? Subscription::query()
                            ->where('user_id', $user->id)
                            ->orderByDesc('id')
                            ->get()
                        : collect()
                )->resolve(),
            ],
        ]);
    }

}
