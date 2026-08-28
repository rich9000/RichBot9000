<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('phone_tree_options', function (Blueprint $table) {
            $table->text('welcome_message')->nullable()->after('description');
            $table->unsignedBigInteger('welcome_audio_id')->nullable()->after('welcome_message');
            $table->unsignedBigInteger('finish_menu_id')->nullable()->after('welcome_audio_id');
            
            $table->foreign('welcome_audio_id')->references('id')->on('audio_files')->onDelete('set null');
            $table->foreign('finish_menu_id')->references('id')->on('phone_tree_menus')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('phone_tree_options', function (Blueprint $table) {
            $table->dropForeign(['welcome_audio_id']);
            $table->dropForeign(['finish_menu_id']);
            $table->dropColumn(['welcome_message', 'welcome_audio_id', 'finish_menu_id']);
        });
    }
}; 