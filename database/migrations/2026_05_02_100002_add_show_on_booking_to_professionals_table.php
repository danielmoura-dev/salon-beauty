<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->boolean('show_on_booking')->default(false)->after('show_on_agenda');
        });
    }

    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropColumn('show_on_booking');
        });
    }
};
