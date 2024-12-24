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





 Schema::create('scheduled_cronbots', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User relationship
            $table->foreignId('assistant_id')->constrained()->onDelete('cascade'); // User relationship
            $table->text('prompt'); // User-provided prompt for the assistant
            $table->boolean('is_repeating')->default(false); // Indicates if the cronbot repeats
            $table->string('schedule')->nullable(); // Cron expression for repeating runs
            $table->timestamp('next_run_at')->nullable(); // Timestamp for the next run
            $table->timestamp('last_run_at')->nullable(); // Timestamp for the last execution
            $table->timestamp('end_at')->nullable(); // If set, the cronbot stops after this timestamp

            // Tool IDs
            $table->foreignId('fail_tool_id')->nullable()->constrained('tools')->onDelete('set null');
            $table->foreignId('success_tool_id')->nullable()->constrained('tools')->onDelete('set null');
            $table->foreignId('pause_tool_id')->nullable()->constrained('tools')->onDelete('set null');

            // Self-destruction
            $table->boolean('is_active')->default(true); // Status to enable/disable the cronbot

            $table->timestamps(); // Created and updated timestamps
        });






    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_cronbots');
    }
};
