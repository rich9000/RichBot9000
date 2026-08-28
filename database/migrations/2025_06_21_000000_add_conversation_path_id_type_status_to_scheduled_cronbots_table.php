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
        Schema::table('scheduled_cronbots', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_path_id')->nullable()->after('assistant_id');
            $table->string('type')->default('assistant')->after('conversation_path_id');
            $table->string('status')->default('active')->after('type');
            
            // Add foreign key constraint for conversation_path_id
            $table->foreign('conversation_path_id')->references('id')->on('conversation_paths')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_cronbots', function (Blueprint $table) {
            $table->dropForeign(['conversation_path_id']);
            $table->dropColumn(['conversation_path_id', 'type', 'status']);
        });
    }
}; 