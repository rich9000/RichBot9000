<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_id');
            $table->string('call_sid');
            $table->string('from_number');
            $table->string('to_number');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->string('status'); // active, completed, abandoned
            $table->unsignedBigInteger('current_menu_id')->nullable();
            $table->string('last_input')->nullable();
            $table->string('websocket_connection_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_id')->references('id')->on('phone_trees')->onDelete('cascade');
            $table->foreign('current_menu_id')->references('id')->on('phone_tree_menus')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_tree_calls');
    }
}; 