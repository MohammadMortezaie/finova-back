<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WebRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_register_page(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('At least 8 characters');
    }

    public function test_registration_requires_a_unique_email_and_a_strong_password(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/register', [
            'email' => 'ada@example.com',
            'password' => 'Correct-Horse-1',
            'password_confirmation' => 'Correct-Horse-1',
        ])->assertSessionHasErrors('email');

        $this->post('/register', [
            'email' => 'new@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertNull(User::query()->where('email', 'new@example.com')->first());
    }

    public function test_registration_sends_a_code_and_verification_activates_the_account(): void
    {
        Mail::fake();

        $this->post('/register', [
            'email' => 'Ada@Example.com',
            'password' => 'Correct-Horse-1',
            'password_confirmation' => 'Correct-Horse-1',
        ])->assertRedirect(route('register.verify', ['email' => 'ada@example.com']));

        $user = User::query()->where('email', 'ada@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code);
        $this->assertTrue(Hash::check('Correct-Horse-1', $user->password));
        Mail::assertSent(VerificationCodeMail::class);

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'Correct-Horse-1',
        ])->assertSessionHasErrors('email');

        $this->post('/register/verify', [
            'email' => 'ada@example.com',
            'code' => '000000',
        ])->assertSessionHasErrors('code');
        $this->assertFalse($user->refresh()->is_active);

        $this->post('/register/verify', [
            'email' => 'ada@example.com',
            'code' => $user->email_verification_code,
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_code);

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'Correct-Horse-1',
        ])->assertRedirect(route('dashboard'));
    }
}
