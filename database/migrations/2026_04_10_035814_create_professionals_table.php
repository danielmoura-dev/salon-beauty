<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('photo')->nullable();
            $table->string('specialty')->nullable();
            $table->date('birthday')->nullable();
            $table->boolean('show_on_agenda')->default(true);
            $table->boolean('receives_commission')->default(true);
            $table->decimal('commission_pct', 5, 2)->default(0); // comissão padrão
            $table->json('work_schedule')->nullable(); // grade de horários semanal
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};