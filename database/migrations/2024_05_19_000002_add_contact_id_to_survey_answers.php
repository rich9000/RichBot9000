<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_answers', 'contact_id')) {
                $table->unsignedBigInteger('contact_id')->after('survey_campaign_id');
                $table->foreign('contact_id')->references('id')->on('contacts');
            }
        });
    }

    public function down()
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
}; 