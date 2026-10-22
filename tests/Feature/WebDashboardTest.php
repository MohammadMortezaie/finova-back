<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_login_page_is_available_and_dashboard_requires_auth(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('class="motion"', false)
            ->assertSee('class="motion motion-right"', false);
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_log_in_and_see_their_dashboard(): void
    {
        Carbon::setTestNow('2026-10-21 12:00:00');

        $user = User::factory()->create([
            'name' => 'John Snow',
            'currency' => 'CAD',
            'is_active' => true,
            'plan' => 'pro',
        ]);

        Income::query()->create([
            'user_id' => $user->id,
            'source' => 'Salary',
            'amount' => 1000,
            'date' => '2026-10-05',
            'note' => 'https://example.test/receipt/file/receipts/salary.jpg',
        ]);
        Income::query()->create([
            'user_id' => $user->id,
            'source' => 'Cash not uploaded',
            'amount' => 500,
            'date' => '2026-10-06',
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'vendor_name' => 'Market',
            'category_id' => 'food',
            'total_amount' => -250,
            'date' => '2026-10-08',
            'receipt_uri' => 'https://example.test/receipt/file/receipts/market.jpg',
        ]);
        Subscription::query()->create([
            'user_id' => $user->id,
            'name' => 'Spotify',
            'amount' => 40.70,
            'billing_day' => 22,
            'active' => true,
            'category_id' => 'entertainment',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('John Snow')
            ->assertSee('Add a shortcut?')
            ->assertSee('Upload file')
            ->assertSee('Financial records history')
            ->assertSee('Salary')
            ->assertSee('Market')
            ->assertSee('aria-label="View file"', false)
            ->assertSee('target="_blank"', false)
            ->assertDontSee('>View file<', false)
            ->assertDontSee('Cash not uploaded')
            ->assertSee('Total income')
            ->assertSee('$1,000')
            ->assertSee('Total expense')
            ->assertSee('$250')
            ->assertSee('Monthly activity')
            ->assertSee('1k', false)
            ->assertSee('Oct income 1,000', false)
            ->assertSee('Oct expense 250', false)
            ->assertDontSee('$500')
            ->assertSee('Spending Footprint')
            ->assertSee('Essentials')
            ->assertSee('100%')
            ->assertSee('Spotify')
            ->assertSee('$40.70')
            ->assertSee('Due in 1 Day')
            ->assertSee('class="side side-left"', false)
            ->assertSee('class="motion"', false)
            ->assertDontSee('This year')
            ->assertDontSee('Next bills');
    }

    public function test_invalid_inactive_and_unverified_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $inactive = User::factory()->create(['is_active' => false]);
        $this->post('/login', [
            'email' => $inactive->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $pending = User::factory()->unverified()->create([
            'email_verification_code' => '123456',
            'is_active' => false,
        ]);
        $this->post('/login', [
            'email' => $pending->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->get('/dashboard')->assertRedirect('/login');
    }
}
