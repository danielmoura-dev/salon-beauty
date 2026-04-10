<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela pivot com comissão personalizada por profissional/serviço
        Schema::create('professional_service', function (Blueprint $table) {
            $table->foreignUuid('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('commission_pct', 5, 2)->nullable(); // null = usa o padrão do serviço
            $table->primary(['professional_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_service');
    }
};