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
        // ── orders ───────────────────────────────────────────

        Schema::table('orders', function (Blueprint $table) {

            // 1. Single-column: orders.status
            $table->index('status', 'idx_orders_status');

            // 2. Single-column: orders.created_at
            $table->index('created_at', 'idx_orders_created_at');

            // 3. Composite: (user_id, status)
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
        });

        // ── products ─────────────────────────────────────────

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                // 4. Full-text: products(name, description)
                $table->fullText(['name', 'description'], 'idx_products_fulltext');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_created_at');
            $table->dropIndex('idx_orders_user_status');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropFullText('idx_products_fulltext');
            });
        }
    }
};
