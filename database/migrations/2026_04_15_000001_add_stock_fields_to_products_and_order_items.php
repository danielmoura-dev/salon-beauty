<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('track_stock')->default(false)->after('active');
            $table->unsignedInteger('stock_qty')->nullable()->after('track_stock');
            $table->unsignedInteger('stock_alert_qty')->nullable()->after('stock_qty');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->after('order_id');
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['track_stock', 'stock_qty', 'stock_alert_qty']);
        });
    }
};
