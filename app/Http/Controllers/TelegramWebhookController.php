<?php

namespace App\Http\Controllers;

use App\Services\TelegramBot;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot): Response
    {
        $secret = (string) config('services.telegram.webhook_secret');
        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret === '' || ! hash_equals($secret, $given)) {
            abort(403);
        }

        $update = $request->json()->all();
        if ($update === []) {
            $update = $request->all();
        }
        $bot->handleUpdate($update, $request->getSchemeAndHttpHost());

        return response()->noContent();
    }
}
