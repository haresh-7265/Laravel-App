<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use DB::unprepared() ONLY for migration-time operations (e.g. setting up triggers, views, or stored procedures).
        // DB::unprepared() executes raw SQL without any parameter binding or sanitization.
        // It must NEVER accept user input, beacause user input is untrusted data — unprepared() puts it directly into SQL without any escaping, making SQL injection trivially easy..
        if (DB::getDriverName() !== 'sqlite') {
            DB::unprepared('
                DROP VIEW IF EXISTS active_products_view;
                CREATE VIEW active_products_view AS
                SELECT id, name, price 
                FROM products 
                WHERE is_active = 1;
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::unprepared('DROP VIEW IF EXISTS active_products_view;');
        }
    }
};
