<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'fullname')) {
            return;
        }

        DB::table('users')
            ->whereNull('name')
            ->whereNotNull('fullname')
            ->update(['name' => DB::raw('fullname')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fullname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fullname')->nullable()->after('name');
        });

        DB::table('users')
            ->whereNull('fullname')
            ->whereNotNull('name')
            ->update(['fullname' => DB::raw('name')]);
    }
};
