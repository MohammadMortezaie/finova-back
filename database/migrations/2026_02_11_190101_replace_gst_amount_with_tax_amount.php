<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        $hasTax = Schema::hasColumn('expenses', 'tax_amount');
        $hasGst = Schema::hasColumn('expenses', 'gst_amount');

        if (!$hasTax) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('tax_amount', 12, 2)->nullable()->after('total_amount');
            });
        }

        if ($hasGst) {
            DB::table('expenses')->update(['tax_amount' => DB::raw('gst_amount')]);

            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('gst_amount');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        $hasTax = Schema::hasColumn('expenses', 'tax_amount');
        $hasGst = Schema::hasColumn('expenses', 'gst_amount');

        if (!$hasGst) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('gst_amount', 12, 2)->nullable()->after('total_amount');
            });
        }

        if ($hasTax) {
            DB::table('expenses')->update(['gst_amount' => DB::raw('tax_amount')]);

            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('tax_amount');
            });
        }
    }
};
