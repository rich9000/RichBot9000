<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Fix survey_contacts status column
        Schema::table('survey_contacts', function (Blueprint $table) {
            $table->string('status')->change(); // Change from enum to string to avoid truncation
        });

        // Fix survey_answers table
        Schema::table('survey_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_answers', 'question_id')) {
                $table->unsignedBigInteger('question_id')->after('id');
                $table->foreign('question_id')->references('id')->on('survey_questions');
            }
            
            if (!Schema::hasColumn('survey_answers', 'survey_campaign_id')) {
                $table->unsignedBigInteger('survey_campaign_id')->after('question_id');
                $table->foreign('survey_campaign_id')->references('id')->on('survey_campaigns');
            }
        });
    }

    public function down()
    {
        Schema::table('survey_contacts', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'completed'])->change();
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->dropForeign(['survey_campaign_id']);
            $table->dropColumn(['question_id', 'survey_campaign_id']);
        });
    }
}; 