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
        Schema::create('ticket_summaries', function (Blueprint $table) {
            $table->id('summary_id');

            $table->json('summary_text'); // JSON summary data
            $table->string('assistant_name'); // Assistant that generated the summary
            $table->timestamps();
            $table->unsignedBigInteger('ticket_id');
            $table->foreign('ticket_id')->references('ticket_id')->on('ticket_tickets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_summaries');
    }
};
