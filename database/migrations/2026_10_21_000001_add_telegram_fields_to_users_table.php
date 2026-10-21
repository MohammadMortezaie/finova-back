<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('stripe_subscription_id');
            $table->timestamp('telegram_activated_at')->nullable()->after('telegram_chat_id');
            $table->timestamp('telegram_activation_requested_at')->nullable()->after('telegram_activated_at');
            $table->string('telegram_link_token', 80)->nullable()->unique()->after('telegram_activation_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_link_token']);
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_activated_at',
                'telegram_activation_requested_at',
                'telegram_link_token',
            ]);
        });
    }
};
