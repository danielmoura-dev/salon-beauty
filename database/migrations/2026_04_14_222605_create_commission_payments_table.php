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
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('professional_id')->constrained()->cascadeOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end');
            $table->decimal('total_services', 10, 2)->default(0);
            $table->decimal('total_products', 10, 2)->default(0);
            $table->decimal('total_others', 10, 2)->default(0);
            $table->decimal('total_vouchers', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'professional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payments');
    }
};
