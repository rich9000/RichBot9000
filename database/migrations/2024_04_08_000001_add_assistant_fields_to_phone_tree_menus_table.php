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
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            $table->unsignedBigInteger('assistant_id')->nullable();
            $table->string('pipeline_id')->nullable();
            
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
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            $table->dropForeign(['assistant_id']);
            $table->dropColumn(['assistant_id', 'pipeline_id']);
        });
    }
}; 