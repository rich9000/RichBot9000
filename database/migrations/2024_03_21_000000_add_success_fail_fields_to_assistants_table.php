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
        Schema::table('assistants', function (Blueprint $table) {
            $table->foreignId('success_assistant_id')->nullable()->constrained('assistants')->nullOnDelete();
            $table->foreignId('fail_tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->foreignId('fail_assistant_id')->nullable()->constrained('assistants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropForeign(['success_assistant_id']);
            $table->dropForeign(['fail_tool_id']);
            $table->dropForeign(['fail_assistant_id']);
            $table->dropColumn(['success_assistant_id', 'fail_tool_id', 'fail_assistant_id']);
        });
    }
}; 