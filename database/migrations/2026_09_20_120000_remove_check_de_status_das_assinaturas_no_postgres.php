<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A migration que trocou `subscriptions.status` de enum para string não remove a
     * constraint CHECK criada pelo enum no PostgreSQL, então valores como 'pending'
     * (PIX aguardando pagamento) eram rejeitados. No MySQL e no SQLite não há o que fazer.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');
        }
    }

    public function down(): void
    {
        // Não recria a constraint: ela era um resquício do enum antigo.
    }
};
