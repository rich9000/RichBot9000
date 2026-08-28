<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_recordings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_call_id');
            $table->string('recording_sid');
            $table->string('recording_url');
            $table->integer('duration');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_call_id')->references('id')->on('phone_tree_calls')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_tree_recordings');
    }
}; 