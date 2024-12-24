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

        Schema::create('ticket_trends', function (Blueprint $table) {
            $table->id('trend_id');
            $table->string('category_tag'); // Category associated with the trend
            $table->integer('frequency'); // Frequency of occurrence
            $table->timestamp('last_detected'); // Last detection time
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_trends');
    }
};
