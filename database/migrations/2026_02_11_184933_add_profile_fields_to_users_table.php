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
            $table->string('fullname')->nullable()->after('name');
            $table->string('language', 8)->default('en')->after('email');
            $table->string('currency', 8)->default('CAD')->after('language');
            $table->boolean('is_active')->default(true)->after('currency');
            $table->string('plan')->nullable()->after('is_active');
            $table->decimal('total_income', 12, 2)->default(0)->after('plan');
            $table->decimal('total_expense', 12, 2)->default(0)->after('total_income');
            $table->decimal('total_subscription', 12, 2)->default(0)->after('total_expense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'fullname',
                'language',
                'currency',
                'is_active',
                'plan',
                'total_income',
                'total_expense',
                'total_subscription',
            ]);
        });
    }
};
