<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function showRequest(Request $request): View
    {
        return view('password-forgot', [
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if ($user) {
            $code = (string) random_int(100000, 999999);
            $user->email_verification_code = $code;
            $user->email_verification_expires_at = now()->addMinutes(15);
            $user->save();

            try {
                $this->sendResetEmail($user, $code);
            } catch (Throwable) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Could not send reset email. Please try again.']);
            }
        }

        return redirect()->route('password.reset.form', ['email' => $data['email']])
            ->with('status', 'If an account exists for this email, a reset code was sent.');
    }

    public function showReset(Request $request): View
    {
        return view('password-reset', [
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (
            !$user ||
            !$user->email_verification_code ||
            !hash_equals($user->email_verification_code, $data['code']) ||
            !$user->email_verification_expires_at ||
            $user->email_verification_expires_at->isPast()
        ) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Invalid or expired reset code.']);
        }

        $user->password = Hash::make($data['password']);
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->save();

        return redirect()->route('password.reset.success');
    }

    public function success(): View
    {
        return view('password-reset-success');
    }

    private function sendResetEmail(User $user, string $code): void
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
              <div style="font-size:14px;color:#73d99f;font-weight:700;letter-spacing:.4px;">FINOVA</div>
              <h1 style="margin:10px 0 0;font-size:26px;line-height:32px;font-weight:800;">Reset your password</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;">
              <p style="margin:0 0 14px;font-size:16px;line-height:24px;">Hi {$escapedName},</p>
              <p style="margin:0 0 22px;font-size:16px;line-height:24px;color:#526158;">Use this 6-digit code to reset your password.</p>
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
                ->subject('Your Finova password reset code');
        });
    }
}
