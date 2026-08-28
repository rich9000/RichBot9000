<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audio_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('file_path');
            $table->string('file_type')->default('audio/mpeg');
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->string('source_type')->default('upload'); // upload or recording
            $table->string('type')->default('general'); // phone-tree, user, general, etc.
            $table->string('context')->nullable(); // Additional context for the audio file
            $table->json('metadata')->nullable(); // For storing additional info like bitrate, sample rate, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audio_files');
    }
}; 