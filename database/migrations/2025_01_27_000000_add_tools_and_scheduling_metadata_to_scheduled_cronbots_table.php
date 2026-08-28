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
            // Add tools JSON field to store the workflow tools
            $table->json('tools')->nullable()->after('prompt');
            
            // Add scheduling metadata JSON field
            $table->json('scheduling_metadata')->nullable()->after('schedule');
            
            // Add name and description fields
            $table->string('name')->nullable()->after('assistant_id');
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_cronbots', function (Blueprint $table) {
            $table->dropColumn(['tools', 'scheduling_metadata', 'name', 'description']);
        });
    }
}; 