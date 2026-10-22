<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use Illuminate\Console\Command;
use RuntimeException;

class ListenForTelegram extends Command
{
    protected $signature = 'telegram:listen';

    protected $description = 'Receive Telegram messages while this app is running on this computer';

    public function handle(TelegramBot $bot): int
    {
        if (! $bot->configured()) {
            $this->components->error('Telegram bot token is not configured.');

            return self::FAILURE;
        }

        try {
            $bot->deleteWebhook();
        } catch (RuntimeException) {
            $this->components->error('Telegram rejected the bot token.');

            return self::FAILURE;
        }

        $this->components->info('Listening for Telegram messages. Leave this running.');
        $offset = 0;

        while (true) {
            try {
                foreach ($bot->updates($offset) as $update) {
                    $offset = ((int) ($update['update_id'] ?? $offset)) + 1;
                    $bot->handleUpdate($update);
                }
            } catch (RuntimeException) {
                $this->components->warn('Telegram connection paused. Retrying.');
                sleep(3);
            }
        }
    }
}
