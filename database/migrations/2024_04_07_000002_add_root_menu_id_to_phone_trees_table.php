<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('phone_trees', function (Blueprint $table) {
            $table->unsignedBigInteger('root_menu_id')->nullable()->after('id');
            $table->foreign('root_menu_id')->references('id')->on('phone_tree_menus')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('phone_trees', function (Blueprint $table) {
            $table->dropForeign(['root_menu_id']);
            $table->dropColumn('root_menu_id');
        });
    }
}; 