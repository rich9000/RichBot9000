<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            // Drop the foreign key if it exists
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_responses'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME');

            if ($foreignKeys->contains('survey_responses_contact_id_foreign')) {
                $table->dropForeign('survey_responses_contact_id_foreign');
            }

            // Drop the column if it exists
            if (Schema::hasColumn('survey_responses', 'contact_id')) {
                $table->dropColumn('contact_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_responses', 'contact_id')) {
                $table->unsignedBigInteger('contact_id')->after('survey_contact_id');
                $table->foreign('contact_id')
                      ->references('id')
                      ->on('contacts')
                      ->onDelete('cascade');
            }
        });
    }
}; 