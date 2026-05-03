<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('booking_slug', 80)->nullable()->unique()->after('slug');
            $table->boolean('booking_active')->default(false)->after('booking_slug');
            $table->string('banner')->nullable()->after('logo');
            $table->unsignedSmallInteger('booking_interval_min')->default(30)->after('booking_active');
            $table->boolean('booking_show_prices')->default(true)->after('booking_interval_min');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['booking_slug']);
            $table->dropColumn([
                'booking_slug', 'booking_active', 'banner',
                'booking_interval_min', 'booking_show_prices',
            ]);
        });
    }
};
