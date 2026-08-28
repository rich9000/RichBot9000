<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_call_id');
            $table->string('transcription_sid');
            $table->text('transcription_text');
            $table->string('language');
            $table->float('confidence');
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_call_id')->references('id')->on('phone_tree_calls')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phone_tree_transcriptions');
    }
}; 