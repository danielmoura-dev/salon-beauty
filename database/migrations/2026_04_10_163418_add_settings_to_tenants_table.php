<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Taxas de cartão (%)
            $table->decimal('credit_card_fee', 5, 2)->default(0)->after('logo');
            $table->decimal('debit_card_fee', 5, 2)->default(0)->after('credit_card_fee');
            // Configurações avançadas
            $table->boolean('allow_duplicate_phone')->default(false)->after('debit_card_fee');
            $table->boolean('show_pending_orders')->default(true)->after('allow_duplicate_phone');
            // Hora de início da agenda
            $table->tinyInteger('agenda_start_hour')->default(8)->after('show_pending_orders');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'credit_card_fee', 'debit_card_fee',
                'allow_duplicate_phone', 'show_pending_orders', 'agenda_start_hour',
            ]);
        });
    }
};