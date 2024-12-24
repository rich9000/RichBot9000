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
        Schema::create('ticket_root_causes', function (Blueprint $table) {
            $table->id('root_cause_id');
            $table->unsignedBigInteger('ticket_id');
            $table->text('cause_description'); // Description of probable cause
            $table->decimal('correlation_score', 3, 2); // Confidence level
            $table->timestamps();
            $table->foreign('ticket_id')->references('ticket_id')->on('ticket_tickets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_root_causes');
    }
};
