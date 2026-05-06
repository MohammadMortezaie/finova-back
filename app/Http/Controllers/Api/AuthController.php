<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
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
        if (!$name) {
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
            'plan' => null,
            'total_income' => 0,
            'total_expense' => 0,
            'total_subscription' => 0,
        ]);

        $user = UserProfileHelper::syncIncomeExpenseTotals($user) ?? $user;

        try {
            $this->sendVerificationEmail($user, $code);
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

        if (!$user) {
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
            !$user->email_verification_code ||
            !hash_equals($user->email_verification_code, $data['code']) ||
            !$user->email_verification_expires_at ||
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
        if (!$user) {
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
            $this->sendVerificationEmail($user, $code);
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

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->email_verified_at && $user->email_verification_code) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
                'pendingVerification' => true,
            ], Response::HTTP_FORBIDDEN);
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

    private function sendVerificationEmail(User $user, string $code): void
    {
        $escapedName = e($user->name);
        $escapedCode = e($code);

        $html = <<<HTML
<!doctype html>
<html>
<body style="margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#1c2520;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dfe7e2;">
          <tr>
            <td style="padding:28px 28px 18px;background:#111814;color:#ffffff;">
              <div style="font-size:14px;color:#73d99f;font-weight:700;letter-spacing:.4px;">WEBPULSE AI</div>
              <h1 style="margin:10px 0 0;font-size:26px;line-height:32px;font-weight:800;">Verify your email</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;">
              <p style="margin:0 0 14px;font-size:16px;line-height:24px;">Hi {$escapedName},</p>
              <p style="margin:0 0 22px;font-size:16px;line-height:24px;color:#526158;">Use this 6-digit code to finish creating your account.</p>
              <div style="text-align:center;margin:26px 0;">
                <div style="display:inline-block;padding:18px 28px;border-radius:14px;background:#eefaf2;border:1px solid #bce9ca;color:#111814;font-size:34px;font-weight:800;letter-spacing:8px;">{$escapedCode}</div>
              </div>
              <p style="margin:0;font-size:14px;line-height:22px;color:#6a766f;">This code expires in 15 minutes. If you did not request it, you can ignore this email.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        Mail::html($html, function ($message) use ($user) {
            $message
                ->to($user->email, $user->name)
                ->subject('Your Webpulse AI verification code');
        });
    }
}
