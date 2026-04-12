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
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('fee_pct', 5, 2)->default(0)->after('amount');
            $table->decimal('fee_amount', 10, 2)->default(0)->after('fee_pct');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['fee_pct', 'fee_amount']);
        });
    }
};
