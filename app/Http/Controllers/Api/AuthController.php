<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
        if (! $name) {
            return response()->json([
                'message' => 'Name is required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $code = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'language' => $data['language'],
            'currency' => $data['currency'],
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(15),
            'is_active' => false,
            'plan' => 'pro',
            'plan_expires_at' => now()->addDays(30),
            'total_income' => 0,
            'total_expense' => 0,
            'total_subscription' => 0,
        ]);

        $user = UserProfileHelper::syncIncomeExpenseTotals($user) ?? $user;

        try {
            Mail::to($user->email, $user->name)->send(new VerificationCodeMail($user, $code));
        } catch (Throwable $e) {
            Log::warning('Registration verification email failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'data' => [
                'pendingVerification' => true,
                'email' => $user->email,
                'message' => 'Verification code sent.',
            ],
        ], Response::HTTP_CREATED);
    }

    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->email_verified_at) {
            $user->is_active = true;
            $user->save();

            return response()->json([
                'data' => [
                    'user' => (new UserResource($user))->resolve(),
                ],
            ]);
        }

        if (
            ! $user->email_verification_code ||
            ! hash_equals($user->email_verification_code, $data['code']) ||
            ! $user->email_verification_expires_at ||
            $user->email_verification_expires_at->isPast()
        ) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->is_active = true;
        $user->save();

        $user = UserProfileHelper::syncIncomeExpenseTotals($user) ?? $user;

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
            ],
        ]);
    }

    public function resendVerification(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user) {
            return response()->json([
                'message' => 'Account not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'data' => [
                    'message' => 'Email is already verified.',
                ],
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $user->email_verification_code = $code;
        $user->email_verification_expires_at = now()->addMinutes(15);
        $user->save();

        try {
            Mail::to($user->email, $user->name)->send(new VerificationCodeMail($user, $code));
        } catch (Throwable $e) {
            Log::warning('Verification email resend failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Could not send verification email. Please try again.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'data' => [
                'message' => 'Verification code sent.',
            ],
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->email_verified_at && $user->email_verification_code) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
                'pendingVerification' => true,
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $user->is_active) {
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
