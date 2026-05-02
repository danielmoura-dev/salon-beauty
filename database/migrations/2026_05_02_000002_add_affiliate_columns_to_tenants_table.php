<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignUuid('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->unsignedTinyInteger('affiliate_discount_months_remaining')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Affiliate::class);
            $table->dropColumn(['affiliate_id', 'affiliate_discount_months_remaining']);
        });
    }
};
