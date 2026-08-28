<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Update phone_tree_menus table
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            // Audio and message fields
            $table->unsignedBigInteger('welcome_audio_id')->nullable()->after('prompt_message');
            $table->text('welcome_message')->nullable()->after('welcome_audio_id');
            $table->unsignedBigInteger('prompt_audio_id')->nullable()->after('welcome_message');
            $table->unsignedBigInteger('finish_audio_id')->nullable()->after('prompt_audio_id');
            $table->text('finish_message')->nullable()->after('finish_audio_id');
            
            // Navigation and control fields
            $table->unsignedBigInteger('finish_menu_id')->nullable()->after('finish_message');
            $table->unsignedBigInteger('websocket_id')->nullable()->after('finish_menu_id');
            $table->boolean('disconnect_on_finish')->default(false)->after('websocket_id');
            $table->string('transfer_number')->nullable()->after('disconnect_on_finish');
            $table->unsignedBigInteger('websocket_fail_menu_id')->nullable()->after('transfer_number');
            $table->string('script_path')->nullable()->after('websocket_fail_menu_id');
            $table->boolean('speak_options')->default(true)->after('script_path');

            // Add foreign key constraints
            $table->foreign('welcome_audio_id')->references('id')->on('audio_files')->onDelete('set null');
            $table->foreign('prompt_audio_id')->references('id')->on('audio_files')->onDelete('set null');
            $table->foreign('finish_audio_id')->references('id')->on('audio_files')->onDelete('set null');
            $table->foreign('finish_menu_id')->references('id')->on('phone_tree_menus')->onDelete('set null');
            $table->foreign('websocket_id')->references('id')->on('phone_tree_websockets')->onDelete('set null');
            $table->foreign('websocket_fail_menu_id')->references('id')->on('phone_tree_menus')->onDelete('set null');
        });

        // Update phone_tree_options table
        Schema::table('phone_tree_options', function (Blueprint $table) {
            $table->string('script_path')->nullable()->after('target_id');
        });
    }

    public function down()
    {
        // Remove new fields from phone_tree_menus
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            // Get all foreign keys for the table
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'phone_tree_menus' 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            // Drop each foreign key if it exists
            foreach ($foreignKeys as $key) {
                try {
                    $table->dropForeign($key->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    // Skip if foreign key doesn't exist
                    continue;
                }
            }

            // Drop columns if they exist
            $columns = [
                'welcome_audio_id',
                'welcome_message',
                'prompt_audio_id',
                'finish_audio_id',
                'finish_message',
                'finish_menu_id',
                'websocket_id',
                'disconnect_on_finish',
                'transfer_number',
                'websocket_fail_menu_id',
                'script_path',
                'speak_options'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('phone_tree_menus', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove new fields from phone_tree_options
        Schema::table('phone_tree_options', function (Blueprint $table) {
            if (Schema::hasColumn('phone_tree_options', 'script_path')) {
                $table->dropColumn('script_path');
            }
        });
    }
}; 