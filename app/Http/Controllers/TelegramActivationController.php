<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramActivationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->telegramBotStatus() === 'activated') {
            return redirect()->route('dashboard')->with('open_screen', 'profile');
        }

        $user->forceFill([
            'telegram_activation_requested_at' => now(),
            'telegram_link_token' => $user->telegram_link_token ?: Str::random(40),
        ])->save();

        $username = ltrim((string) config('services.telegram.bot_username'), '@');
        if ($username !== '') {
            return redirect()->away('https://t.me/'.$username.'?start='.$user->telegram_link_token);
        }

        return redirect()
            ->route('dashboard')
            ->with('open_screen', 'profile')
            ->with('telegram_notice', 'Activation is saved. The Telegram link will show here once the bot is connected.');
    }
}
