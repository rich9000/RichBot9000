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
        Schema::create('ticket_impacts', function (Blueprint $table) {
            $table->id('impact_id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('impact_rating'); // e.g., High, Medium, Low
            $table->integer('affected_users'); // Number of users affected
            $table->integer('resolution_estimate'); // Estimated resolution time
            $table->timestamps();
            $table->foreign('ticket_id')->references('ticket_id')->on('ticket_tickets');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_impacts');
    }
};
