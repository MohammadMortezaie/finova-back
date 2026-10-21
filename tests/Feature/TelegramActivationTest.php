<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_telegram_status_and_activate_saves_a_request(): void
    {
        $user = User::factory()->create([
            'name' => 'John Snow',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Telegram bot')
            ->assertSee('Not activated')
            ->assertSee('Activate bot');

        $this->actingAs($user)
            ->post(route('dashboard.telegram.activate'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('open_screen', 'profile');

        $user->refresh();
        $this->assertNotNull($user->telegram_activation_requested_at);
        $this->assertNotNull($user->telegram_link_token);
        $this->assertNull($user->telegram_activated_at);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('Pending')
            ->assertDontSee('Activate bot');
    }

    public function test_activated_telegram_shows_connected_status(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'telegram_chat_id' => '1001',
            'telegram_activated_at' => '2026-10-21 12:00:00',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Activated')
            ->assertSee('Connected Oct 21, 2026')
            ->assertDontSee('Activate bot');
    }

    public function test_guest_cannot_activate_telegram(): void
    {
        $this->post(route('dashboard.telegram.activate'))->assertRedirect('/login');
    }
}
