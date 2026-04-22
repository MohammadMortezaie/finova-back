<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'fullname' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'language' => 'required|string|max:8',
            'currency' => 'required|string|max:8',
        ]);

        $name = $data['name'] ?? $data['fullname'] ?? null;
        if (!$name) {
            return response()->json([
                'message' => 'Name is required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'language' => $data['language'],
            'currency' => $data['currency'],
            'is_active' => true,
            'plan' => null,
            'total_income' => 0,
            'total_expense' => 0,
            'total_subscription' => 0,
        ]);

        $user = UserProfileHelper::syncIncomeExpenseTotals($user) ?? $user;

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'This account is inactive.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user = UserProfileHelper::syncIncomeExpenseTotals($user) ?? $user;

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
            ],
        ]);
    }
}
