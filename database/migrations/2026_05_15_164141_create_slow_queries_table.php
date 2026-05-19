<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'analytics';

    public function up(): void
    {
        Schema::connection($this->connection)->create('slow_queries', function (Blueprint $table) {
            $table->id();

            // The raw SQL query
            $table->text('sql');

            // Bound parameter values
            $table->json('bindings')->nullable();

            // Query execution time in milliseconds
            $table->float('time_ms');

            // Where the query was triggered from
            $table->string('url', 2048)->nullable();
            $table->string('method', 10)->nullable();       // GET, POST, etc.

            // Which DB connection was used (useful for multi-DB apps)
            $table->string('connection')->nullable();

            // Request context
            $table->string('ip_address', 45)->nullable();   // supports IPv6
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // logged-in user if any

            $table->timestamps();

            // Indexes for common queries
            $table->index('time_ms');
            $table->index('created_at');
            $table->index('user_id');
            $table->index('connection');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('slow_queries');
    }
};