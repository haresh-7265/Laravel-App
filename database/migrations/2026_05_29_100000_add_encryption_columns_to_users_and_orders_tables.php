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
        Schema::table('users', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('phone_blind_index')->nullable()->index();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable()->change();
            $table->string('shipping_phone_blind_index')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address', 'tax_id', 'phone_blind_index']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('billing_address');
            $table->string('shipping_address')->nullable(false)->change();
            $table->dropColumn('shipping_phone_blind_index');
        });
    }
};
