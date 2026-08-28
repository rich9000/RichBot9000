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
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('survey_response_id');
            $table->unsignedBigInteger('survey_question_id');
            $table->text('answer_text')->nullable();
            $table->json('answer_data')->nullable();
            $table->timestamps();

            $table->foreign('survey_response_id')->references('id')->on('survey_responses')->onDelete('cascade');
            $table->foreign('survey_question_id')->references('id')->on('survey_questions')->onDelete('cascade');
            
            // Ensure one answer per question per response
            $table->unique(['survey_response_id', 'survey_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
}; 