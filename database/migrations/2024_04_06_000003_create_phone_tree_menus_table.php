<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_id');
            $table->unsignedBigInteger('parent_menu_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('prompt_message');
            $table->text('timeout_message')->nullable();
            $table->text('invalid_input_message')->nullable();
            $table->integer('max_retries')->default(3);
            $table->integer('timeout_seconds')->default(10);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_id')->references('id')->on('phone_trees')->onDelete('cascade');
            $table->foreign('parent_menu_id')->references('id')->on('phone_tree_menus')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_tree_menus');
    }
}; 