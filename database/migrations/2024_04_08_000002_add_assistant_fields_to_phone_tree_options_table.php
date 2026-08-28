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
        Schema::table('phone_tree_options', function (Blueprint $table) {
            // Add assistant as a new target type
            $table->unsignedBigInteger('assistant_id')->nullable()->after('target_id');
            $table->string('pipeline_id')->nullable()->after('assistant_id');
            
            // Add foreign key
            $table->foreign('assistant_id')
                ->references('id')
                ->on('assistants')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_tree_options', function (Blueprint $table) {
            $table->dropForeign(['assistant_id']);
            $table->dropColumn(['assistant_id', 'pipeline_id']);
        });
    }
}; 