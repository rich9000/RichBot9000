<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('richbot_id');
            $table->string('type'); // e.g., 'image_notify', 'image_alarm', 'audio_note'
            $table->text('prompt'); // The condition or prompt
            $table->string('action'); // e.g., 'notify', 'alarm', 'email'
            $table->timestamps();

            $table->foreign('richbot_id')->references('id')->on('remote_richbots')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_triggers');
    }
};
