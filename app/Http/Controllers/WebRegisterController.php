<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class WebRegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'email.unique' => 'An account with this email already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $email = $data['email'];
        $code = (string) random_int(100000, 999999);

        $user = User::query()->create([
            'name' => $this->nameFromEmail($email),
            'email' => $email,
            'password' => $data['password'],
            'language' => 'en',
            'currency' => 'CAD',
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(15),
            'is_active' => false,
        ]);

        try {
            Mail::to($user->email, $user->name)->send(new VerificationCodeMail($user, $code));
        } catch (Throwable) {
            return redirect()
                ->route('register.verify', ['email' => $user->email])
                ->withErrors(['email' => 'We could not send the verification email. Please try again.']);
        }

        return redirect()
            ->route('register.verify', ['email' => $user->email])
            ->with('status', 'We sent a 6-digit code to '.$user->email.'.');
    }

    public function showVerify(Request $request): View
    {
        return view('auth.verify', [
            'email' => old('email', (string) $request->query('email', '')),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()->where('email', Str::lower($data['email']))->first();

        if (! $user || ! $this->codeMatches($user, $data['code'])) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => 'That code is invalid or has expired.']);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'is_active' => true,
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', 'Your email is verified. You can log in.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', Str::lower($data['email']))->first();
        if (! $user || $user->email_verified_at) {
            return back()
                ->withInput($request->only('email'))
                ->with('status', 'If this account still needs verification, a new code is on the way.');
        }

        $code = (string) random_int(100000, 999999);
        $user->forceFill([
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(15),
        ])->save();

        try {
            Mail::to($user->email, $user->name)->send(new VerificationCodeMail($user, $code));
        } catch (Throwable) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not send the verification email. Please try again.']);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'We sent a new code to '.$user->email.'.');
    }

    private function normalizeEmail(Request $request): void
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);
    }

    private function codeMatches(User $user, string $code): bool
    {
        return is_string($user->email_verification_code)
            && hash_equals($user->email_verification_code, $code)
            && $user->email_verification_expires_at
            && $user->email_verification_expires_at->isFuture();
    }

    private function nameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true) ?: '';
        $words = trim((string) preg_replace('/[^A-Za-z0-9]+/', ' ', $local));

        return $words === '' ? 'Finova member' : mb_convert_case($words, MB_CASE_TITLE, 'UTF-8');
    }
}
