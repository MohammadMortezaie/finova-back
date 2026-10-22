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
                ? $this->receiptPrompt()
                : $this->connectPrompt());
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
                ? $this->alreadyConnected()
                : $this->connectPrompt());

            return;
        }

        $user = User::query()->where('telegram_link_token', $token)->first();
        if (! $user) {
            $this->send($chatId, $this->linkedUser($chatId)
                ? $this->alreadyConnected()
                : $this->expiredLink());

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

        $this->send($chatId, $this->connectedMessage($user));
    }

    private function saveReceipt(string $chatId, string $fileId, string $filename): void
    {
        $user = $this->linkedUser($chatId);
        if (! $user) {
            $this->send($chatId, $this->connectPrompt());

            return;
        }

        $this->send($chatId, '<b>👀 Reading your receipt…</b>');

        try {
            $saved = $this->recordDownloadedFile($user, $fileId, $filename);
        } catch (RuntimeException $exception) {
            $this->send($chatId, str_contains($exception->getMessage(), '20 MB')
                ? $this->fileTooLarge()
                : $this->couldNotRead());

            return;
        }

        $this->send($chatId, $this->savedMessage($saved));
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

    private function connectedMessage(User $user): string
    {
        $name = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $first = $this->escape((string) ($name[0] ?? ''));
        $welcome = $first === '' ? 'Your account is linked.' : "Welcome, <b>{$first}</b>.";

        return implode("\n", [
            '<b>✅ You\'re connected</b>',
            '',
            $welcome,
            'This chat is now your receipt inbox.',
            '',
            '<blockquote>Send a photo or PDF anytime. I\'ll read the store, date, and amount, then add it to your records.</blockquote>',
        ]);
    }

    private function alreadyConnected(): string
    {
        return implode("\n", [
            '<b>✅ Already connected</b>',
            '',
            '<blockquote>Send a receipt photo or PDF whenever you are ready.</blockquote>',
        ]);
    }

    private function connectPrompt(): string
    {
        return implode("\n", [
            '<b>🔗 Connect your account</b>',
            '',
            'Open <b>Finova</b>, tap <b>Activate bot</b>, then tap <b>Start</b> from that link.',
        ]);
    }

    private function expiredLink(): string
    {
        return implode("\n", [
            '<b>⏳ This link has expired</b>',
            '',
            'Open <b>Finova</b> and tap <b>Activate bot</b> for a fresh link.',
        ]);
    }

    private function receiptPrompt(): string
    {
        return implode("\n", [
            '<b>📎 Send a receipt</b>',
            '',
            'A photo or PDF is enough. I\'ll add the amount to your records.',
        ]);
    }

    private function fileTooLarge(): string
    {
        return implode("\n", [
            '<b>📎 That file is too large</b>',
            '',
            'Send a photo or PDF under <b>20 MB</b>.',
        ]);
    }

    private function couldNotRead(): string
    {
        return implode("\n", [
            '<b>📷 I couldn\'t read that file</b>',
            '',
            'Try a clearer photo, with the store name and total visible.',
        ]);
    }

    /**
     * @param  array{kind: string, title: string, amount: string, date: string, detail: string, fileUrl: ?string}  $saved
     */
    private function savedMessage(array $saved): string
    {
        $income = $saved['kind'] === 'Income';
        $icon = $income ? '💚' : '🧾';
        $label = $income ? 'Income saved' : 'Expense saved';

        return implode("\n", [
            "<b>{$icon} {$label}</b>",
            '',
            '<b>'.$this->escape($saved['title']).'</b>',
            $this->escape($saved['amount']).' · '.$this->escape($saved['detail']),
            $this->escape($saved['date']),
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function send(string $chatId, string $text): void
    {
        try {
            $this->call('sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
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
