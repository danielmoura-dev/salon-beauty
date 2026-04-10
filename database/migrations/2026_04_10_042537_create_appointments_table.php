<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('status', [
                'scheduled',   // Agendado
                'confirmed',   // Confirmado
                'in_progress', // Em atendimento
                'completed',   // Finalizado
                'cancelled',   // Cancelado
                'no_show',     // Não compareceu
            ])->default('scheduled');

            $table->enum('recurrence', ['none', 'weekly', 'biweekly', 'monthly'])->default('none');
            $table->uuid('recurrence_group_id')->nullable(); // agrupa recorrentes
            $table->boolean('create_order')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'professional_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};