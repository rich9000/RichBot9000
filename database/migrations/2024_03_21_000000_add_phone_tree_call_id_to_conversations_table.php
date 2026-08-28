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
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'phone_tree_call_id')) {
                $table->unsignedBigInteger('phone_tree_call_id')->nullable();
                $table->foreign('phone_tree_call_id')
                      ->references('id')
                      ->on('phone_tree_calls')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'phone_tree_call_id')) {
                $table->dropForeign(['phone_tree_call_id']);
                $table->dropColumn('phone_tree_call_id');
            }
        });
    }
}; 