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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slack_message_ts')->nullable();
            $table->string('slack_channel_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('slack_username')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slack_username');
        });
    }
};
