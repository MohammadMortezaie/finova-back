<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'language',
        'currency',
        'email_verification_code',
        'email_verification_expires_at',
        'is_active',
        'plan',
        'plan_expires_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'total_income',
        'total_expense',
        'total_subscription',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telegram_link_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'telegram_activated_at' => 'datetime',
            'telegram_activation_requested_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'total_income' => 'float',
            'total_expense' => 'float',
            'total_subscription' => 'float',
        ];
    }

    public function telegramBotStatus(): string
    {
        if ($this->telegram_activated_at && filled($this->telegram_chat_id)) {
            return 'activated';
        }

        if ($this->telegram_activation_requested_at) {
            return 'pending';
        }

        return 'inactive';
    }
}
