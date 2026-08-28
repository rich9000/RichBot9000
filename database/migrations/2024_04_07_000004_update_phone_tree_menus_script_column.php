<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            // Only add script_id if it doesn't exist
            if (!Schema::hasColumn('phone_tree_menus', 'script_id')) {
                $table->unsignedBigInteger('script_id')->nullable();
            }
            
            // Only add foreign key if it doesn't exist
            if (!Schema::hasColumn('phone_tree_menus', 'script_id')) {
                $table->foreign('script_id')
                    ->references('id')
                    ->on('phone_tree_scripts')
                    ->onDelete('set null');
            }
        });

        // Drop the script_path column in a separate operation
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            if (Schema::hasColumn('phone_tree_menus', 'script_path')) {
                $table->dropColumn('script_path');
            }
        });
    }

    public function down()
    {
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            // Only drop foreign key and column if they exist
            if (Schema::hasColumn('phone_tree_menus', 'script_id')) {
                $table->dropForeign(['script_id']);
                $table->dropColumn('script_id');
            }
            
            // Only add back script_path if it doesn't exist
            if (!Schema::hasColumn('phone_tree_menus', 'script_path')) {
                $table->string('script_path')->nullable();
            }
        });
    }
}; 