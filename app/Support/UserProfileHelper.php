<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class UserProfileHelper
{
    public static function getFirst(): ?User
    {
        return User::query()->first();
    }

    public static function resolveFromRequest(Request $request): ?User
    {
        $email = $request->header('X-User-Email')
            ?? $request->query('email')
            ?? $request->input('email');

        if (!is_string($email) || $email === '') {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }

    public static function syncIncomeExpenseTotals(?User $user = null): ?User
    {
        $user = $user ?? self::getFirst();
        if (!$user) {
            return null;
        }

        self::claimOrphanedRecords($user);

        $user->total_income = (float) Income::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->sum('amount');
        $user->total_expense = (float) Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->sum('total_amount');
        $user->total_subscription = (float) Subscription::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('active', true)
            ->sum('amount');
        $user->save();

        return $user;
    }

    private static function claimOrphanedRecords(User $user): void
    {
        Expense::query()->whereNull('user_id')->update(['user_id' => $user->id]);
        Income::query()->whereNull('user_id')->update(['user_id' => $user->id]);
        Subscription::query()->whereNull('user_id')->update(['user_id' => $user->id]);
    }
}
