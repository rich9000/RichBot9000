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
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('category_tag'); // Category assigned to ticket
            $table->decimal('confidence_score', 3, 2); // Confidence level (0-1)
            $table->timestamps();

            $table->foreign('ticket_id')->references('ticket_id')->on('ticket_tickets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
