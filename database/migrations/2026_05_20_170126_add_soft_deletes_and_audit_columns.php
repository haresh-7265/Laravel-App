<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add soft deletes to users
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes + audit columns to products
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
            $table->nullableMorphs('created_by');
            $table->nullableMorphs('updated_by');
        });

        // Add soft deletes + audit columns to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropMorphs('created_by');
            $table->dropMorphs('updated_by');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['deleted_at', 'created_by']);
            $table->dropMorphs('updated_by');
        });
    }
};
