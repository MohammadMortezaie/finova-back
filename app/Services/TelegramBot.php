<?php

namespace App\Services;

use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\DashboardUploadController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBot
{
    public function configured(): bool
    {
        return $this->token() !== '';
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! is_array($message) || ($message['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        if ($chatId === '' || ! $this->configured()) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        if (preg_match('/^\/start(?:@\w+)?(?:\s+(\S+))?/u', $text, $matches) === 1) {
            $this->link($chatId, $matches[1] ?? '');

            return;
        }

        $file = $this->incomingFile($message);
        if ($file !== null) {
            $this->saveReceipt($chatId, $file['file_id'], $file['name']);

            return;
        }

        if ($text !== '') {
            $this->send($chatId, $this->linkedUser($chatId)
                ? 'Send a photo or file of a receipt and I will add it to your records.'
                : 'Open Money Management, tap Activate bot, then tap Start from that Telegram link.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function updates(int $offset): array
    {
        $result = $this->call('getUpdates', [
            'offset' => $offset,
            'timeout' => 25,
            'allowed_updates' => ['message'],
        ], 40);
        $updates = $result['result'] ?? [];

        return is_array($updates) ? array_values(array_filter($updates, 'is_array')) : [];
    }

    public function deleteWebhook(): void
    {
        $this->call('deleteWebhook', ['drop_pending_updates' => false]);
    }

    private function link(string $chatId, string $token): void
    {
        $token = trim($token);
        if ($token === '') {
            $this->send($chatId, $this->linkedUser($chatId)
                ? 'This chat is already connected. Send a receipt photo or file.'
                : 'Open Money Management, tap Activate bot, then tap Start from that Telegram link.');

            return;
        }

        $user = User::query()->where('telegram_link_token', $token)->first();
        if (! $user) {
            $this->send($chatId, $this->linkedUser($chatId)
                ? 'This chat is already connected. Send a receipt photo or file.'
                : 'That activation link is no longer valid. Tap Activate bot in the app and try again.');

            return;
        }

        User::query()
            ->where('telegram_chat_id', $chatId)
            ->whereKeyNot($user->id)
            ->get()
            ->each(function (User $other): void {
                $other->forceFill([
                    'telegram_chat_id' => null,
                    'telegram_activated_at' => null,
                ])->save();
            });

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_activated_at' => now(),
            'telegram_link_token' => null,
        ])->save();

        $this->send($chatId, 'Connected. Send a receipt photo or file and I will add it to your records.');
    }

    private function saveReceipt(string $chatId, string $fileId, string $filename): void
    {
        $user = $this->linkedUser($chatId);
        if (! $user) {
            $this->send($chatId, 'Open Money Management, tap Activate bot, then tap Start from that Telegram link.');

            return;
        }

        $this->send($chatId, 'Reading that receipt…');

        try {
            $saved = $this->recordDownloadedFile($user, $fileId, $filename);
        } catch (RuntimeException) {
            $this->send($chatId, 'I could not read that file. Try a clearer photo of the receipt.');

            return;
        }

        $this->send($chatId, "Saved {$saved['kind']}: {$saved['title']}, {$saved['amount']}.");
    }

    /**
     * @return array{kind: string, title: string, amount: string, date: string, detail: string, fileUrl: ?string}
     */
    private function recordDownloadedFile(User $user, string $fileId, string $filename): array
    {
        $file = $this->call('getFile', ['file_id' => $fileId]);
        $path = (string) data_get($file, 'result.file_path', '');
        $size = (int) data_get($file, 'result.file_size', 0);
        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            throw new RuntimeException('Telegram did not return a file.');
        }
        if ($size > 20 * 1024 * 1024) {
            throw new RuntimeException('That file is larger than 20 MB.');
        }

        $download = Http::withOptions(['allow_redirects' => false])
            ->timeout(60)
            ->get('https://api.telegram.org/file/bot'.$this->token().'/'.$path);
        if (! $download->successful() || $download->body() === '') {
            throw new RuntimeException('Telegram did not return a file.');
        }

        $temp = tempnam(sys_get_temp_dir(), 'receipt');
        if ($temp === false) {
            throw new RuntimeException('Could not store that file.');
        }
        file_put_contents($temp, $download->body());
        $uploaded = new UploadedFile($temp, $this->safeFilename($filename), null, UPLOAD_ERR_OK, true);

        try {
            $request = Request::create('/receipt/analyze', 'POST', [], [], ['file' => $uploaded]);
            $response = app(ReceiptController::class)->analyze($request);
        } finally {
            @unlink($temp);
        }

        $payload = $response->getData(true);
        $analysis = $payload['data'] ?? null;
        if ($response->getStatusCode() >= 400 || ! is_array($analysis)) {
            throw new RuntimeException('Could not read that file.');
        }

        return app(DashboardUploadController::class)->recordAnalysis($user, $analysis);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{file_id: string, name: string}|null
     */
    private function incomingFile(array $message): ?array
    {
        $photos = $message['photo'] ?? null;
        if (is_array($photos) && $photos !== []) {
            $photo = $photos[array_key_last($photos)];
            $fileId = is_array($photo) ? (string) ($photo['file_id'] ?? '') : '';

            return $fileId === '' ? null : ['file_id' => $fileId, 'name' => 'receipt.jpg'];
        }

        $document = $message['document'] ?? null;
        if (! is_array($document)) {
            return null;
        }
        $fileId = (string) ($document['file_id'] ?? '');

        return $fileId === '' ? null : [
            'file_id' => $fileId,
            'name' => (string) ($document['file_name'] ?? 'receipt'),
        ];
    }

    private function linkedUser(string $chatId): ?User
    {
        return User::query()
            ->where('telegram_chat_id', $chatId)
            ->whereNotNull('telegram_activated_at')
            ->first();
    }

    private function send(string $chatId, string $text): void
    {
        try {
            $this->call('sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (RuntimeException) {
            // The account change is already saved. The next message can try the reply again.
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function call(string $method, array $parameters = [], int $timeout = 20): array
    {
        $response = Http::acceptJson()
            ->timeout($timeout)
            ->post('https://api.telegram.org/bot'.$this->token().'/'.$method, $parameters);
        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || ($body['ok'] ?? false) !== true) {
            throw new RuntimeException('Telegram could not complete that request.');
        }

        return $body;
    }

    private function token(): string
    {
        return trim((string) config('services.telegram.bot_token'));
    }

    private function safeFilename(string $filename): string
    {
        $name = basename(str_replace('\\', '/', $filename));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $name) ?: 'receipt';

        return $name === '' || $name === '.' || $name === '..' ? 'receipt' : $name;
    }
}
