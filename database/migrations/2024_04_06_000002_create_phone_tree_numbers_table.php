<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_id');
            $table->string('phone_number');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_id')->references('id')->on('phone_trees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_tree_numbers');
    }
}; 