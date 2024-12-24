<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('media_trigger_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_trigger_id');
            $table->unsignedBigInteger('media_id');
            $table->string('action_taken');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('media_trigger_id')->references('id')->on('media_triggers')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_trigger_events');
    }
};
