<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_menu_id');
            $table->string('digit');
            $table->string('action_type'); // menu, transfer, voicemail, websocket, hangup
            $table->string('target_id'); // ID of target menu, phone number, or websocket endpoint
            $table->text('description');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_menu_id')->references('id')->on('phone_tree_menus')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_tree_options');
    }
}; 