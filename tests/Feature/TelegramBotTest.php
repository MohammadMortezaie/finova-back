<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $telegramMessages = [];

    public function test_activate_opens_the_bot_when_a_username_is_configured(): void
    {
        config(['services.telegram.bot_username' => 'finovawp_bot']);
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('dashboard.telegram.activate'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->telegram_link_token);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('Open Telegram and tap Start')
            ->assertSee('https://t.me/finovawp_bot?start='.$user->telegram_link_token, false);
    }

    public function test_start_command_connects_the_chat(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        $user = User::factory()->create(['is_active' => true]);
        $user->forceFill([
            'telegram_activation_requested_at' => now(),
            'telegram_link_token' => 'link-token',
        ])->save();
        $this->fakeTelegram();

        $this->postJson('/telegram/webhook', [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'text' => '/start link-token',
                'chat' => ['id' => 555, 'type' => 'private'],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertNoContent();

        $user->refresh();
        $this->assertSame('555', $user->telegram_chat_id);
        $this->assertNotNull($user->telegram_activated_at);
        $this->assertNull($user->telegram_link_token);
        $this->assertSame(['Connected. Send a receipt photo or file and I will add it to your records.'], $this->telegramMessages);
    }

    public function test_webhook_rejects_a_missing_secret(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        $user = User::factory()->create(['is_active' => true]);
        $user->forceFill(['telegram_link_token' => 'link-token'])->save();

        $this->postJson('/telegram/webhook', [
            'message' => [
                'text' => '/start link-token',
                'chat' => ['id' => 555, 'type' => 'private'],
            ],
        ])->assertForbidden();

        $this->assertNull($user->refresh()->telegram_chat_id);
    }

    public function test_a_receipt_photo_is_saved_for_the_connected_user(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        $user = User::factory()->create([
            'is_active' => true,
            'currency' => 'CAD',
        ]);
        $user->forceFill([
            'telegram_chat_id' => '555',
            'telegram_activated_at' => now(),
        ])->save();
        $this->fakeTelegram();

        $this->postJson('/telegram/webhook', [
            'update_id' => 2,
            'message' => [
                'message_id' => 11,
                'chat' => ['id' => 555, 'type' => 'private'],
                'photo' => [
                    ['file_id' => 'small', 'width' => 90],
                    ['file_id' => 'large', 'width' => 800],
                ],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertNoContent();

        $expense = Expense::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame('Demo Store', $expense->vendor_name);
        $this->assertSame(-11.2, $expense->total_amount);
        $this->assertSame(['Reading that receipt…', 'Saved Expense: Demo Store, $11.20.'], $this->telegramMessages);
    }

    private function fakeTelegram(): void
    {
        config(['services.telegram.webhook_secret' => 'test-secret']);
        $this->telegramMessages = [];
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, '/sendMessage')) {
                $this->telegramMessages[] = $request->data()['text'] ?? '';

                return Http::response(['ok' => true, 'result' => ['message_id' => 1]]);
            }
            if (str_contains($url, '/getFile')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['file_path' => 'photos/receipt.jpg', 'file_size' => 128],
                ]);
            }
            if (str_contains($url, '/file/bot')) {
                return Http::response('receipt-bytes');
            }
            if (str_contains($url, 'financial-management')) {
                return Http::response([
                    'success' => true,
                    'analysis' => [
                        'type' => 'Expense',
                        'amount' => 11.2,
                        'tax' => 1.2,
                        'date' => '2026-05-03',
                        'vendor_name' => 'Demo Store',
                        'category' => 'Meals',
                        'description' => 'Lunch receipt',
                    ],
                ]);
            }

            return Http::response(['ok' => false], 500);
        });
    }
}
