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
        Schema::table('audio_files', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->bigInteger('file_size')->nullable()->comment('Size in bytes');
            $table->integer('bitrate')->nullable()->comment('Bitrate in kbps');
            $table->integer('sample_rate')->nullable()->comment('Sample rate in Hz');
            $table->integer('channels')->nullable()->comment('Number of audio channels (1=mono, 2=stereo)');
            $table->text('transcription')->nullable();
            $table->json('tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audio_files', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'file_size',
                'bitrate',
                'sample_rate',
                'channels',
                'transcription',
                'tags'
            ]);
        });
    }
};
