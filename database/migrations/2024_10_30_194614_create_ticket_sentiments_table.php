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
        Schema::create('ticket_sentiments', function (Blueprint $table) {
            $table->id('sentiment_id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('sentiment_score'); // e.g., Positive, Neutral, Negative
            $table->timestamps();
            $table->foreign('ticket_id')->references('ticket_id')->on('ticket_tickets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sentiments');
    }
};
