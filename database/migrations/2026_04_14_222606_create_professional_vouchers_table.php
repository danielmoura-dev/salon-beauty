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
        Schema::create('professional_vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('professional_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->date('issued_at');
            $table->foreignUuid('commission_payment_id')->nullable()
                  ->constrained('commission_payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'professional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_vouchers');
    }
};
