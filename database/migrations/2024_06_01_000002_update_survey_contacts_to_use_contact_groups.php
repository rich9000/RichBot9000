<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyCampaign;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the new column if it doesn't exist
        if (!Schema::hasColumn('survey_contacts', 'contact_group_id')) {
            Schema::table('survey_contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_group_id')->nullable()->after('survey_campaign_id');
            });
        }

        // Only migrate data if we have both columns
        if (Schema::hasColumn('survey_contacts', 'contact_id') && Schema::hasColumn('survey_contacts', 'contact_group_id')) {
            // Migrate the data using Laravel's query builder
            DB::table('survey_contacts')
                ->join('contacts', 'survey_contacts.contact_id', '=', 'contacts.id')
                ->join('contact_groups', 'contacts.id', '=', 'contact_groups.contact_id')
                ->join('survey_campaigns', 'survey_contacts.survey_campaign_id', '=', 'survey_campaigns.id')
                ->where('contact_groups.groupable_type', SurveyCampaign::class)
                ->whereRaw('contact_groups.groupable_id = survey_campaigns.id')
                ->update([
                    'survey_contacts.contact_group_id' => DB::raw('contact_groups.id')
                ]);
        }

        // Now we can safely drop the old column and constraints if they exist
        if (Schema::hasColumn('survey_contacts', 'contact_id')) {
            // First, find all foreign keys that reference this table
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_contacts'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME');

            // Drop all foreign keys that reference this table
            foreach ($foreignKeys as $foreignKey) {
                Schema::table('survey_contacts', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
            }

            // Now find all foreign keys in other tables that reference this table
            $referencingForeignKeys = collect(DB::select("
                SELECT TABLE_NAME, CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'survey_contacts'
                AND REFERENCED_COLUMN_NAME = 'contact_id'
            "));

            // Drop all foreign keys that reference this table from other tables
            foreach ($referencingForeignKeys as $foreignKey) {
                Schema::table($foreignKey->TABLE_NAME, function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey->CONSTRAINT_NAME);
                });
            }

            // Now we can safely drop the unique constraint and column
            Schema::table('survey_contacts', function (Blueprint $table) {
                // Drop the old unique constraint if it exists
                $uniqueConstraints = collect(DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'survey_contacts'
                    AND CONSTRAINT_TYPE = 'UNIQUE'
                "))->pluck('CONSTRAINT_NAME');

                if ($uniqueConstraints->contains('survey_contacts_survey_campaign_id_contact_id_unique')) {
                    $table->dropUnique('survey_contacts_survey_campaign_id_contact_id_unique');
                }

                $table->dropColumn('contact_id');
            });
        }

        // Add the new constraints if they don't exist
        Schema::table('survey_contacts', function (Blueprint $table) {
            // Check if the foreign key exists
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_contacts'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME');

            if (!$foreignKeys->contains('survey_contacts_contact_group_id_foreign')) {
                $table->foreign('contact_group_id')
                      ->references('id')
                      ->on('contact_groups')
                      ->onDelete('cascade');
            }

            // Check if the unique constraint exists
            $uniqueConstraints = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_contacts'
                AND CONSTRAINT_TYPE = 'UNIQUE'
            "))->pluck('CONSTRAINT_NAME');

            if (!$uniqueConstraints->contains('survey_contacts_survey_campaign_id_contact_group_id_unique')) {
                $table->unique(['survey_campaign_id', 'contact_group_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First add back the old column if it doesn't exist
        if (!Schema::hasColumn('survey_contacts', 'contact_id')) {
            Schema::table('survey_contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_id')->nullable()->after('survey_campaign_id');
            });
        }

        // Only migrate data if we have both columns
        if (Schema::hasColumn('survey_contacts', 'contact_group_id') && Schema::hasColumn('survey_contacts', 'contact_id')) {
            // Migrate the data back using Laravel's query builder
            DB::table('survey_contacts')
                ->join('contact_groups', 'survey_contacts.contact_group_id', '=', 'contact_groups.id')
                ->update([
                    'survey_contacts.contact_id' => DB::raw('contact_groups.contact_id')
                ]);
        }

        // Now we can safely drop the new column and constraints if they exist
        if (Schema::hasColumn('survey_contacts', 'contact_group_id')) {
            // First, find all foreign keys that reference this table
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_contacts'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME');

            // Drop all foreign keys that reference this table
            foreach ($foreignKeys as $foreignKey) {
                Schema::table('survey_contacts', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
            }

            // Now find all foreign keys in other tables that reference this table
            $referencingForeignKeys = collect(DB::select("
                SELECT TABLE_NAME, CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'survey_contacts'
                AND REFERENCED_COLUMN_NAME = 'contact_group_id'
            "));

            // Drop all foreign keys that reference this table from other tables
            foreach ($referencingForeignKeys as $foreignKey) {
                Schema::table($foreignKey->TABLE_NAME, function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey->CONSTRAINT_NAME);
                });
            }

            // Now we can safely drop the unique constraint and column
            Schema::table('survey_contacts', function (Blueprint $table) {
                // Drop the new unique constraint if it exists
                $uniqueConstraints = collect(DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'survey_contacts'
                    AND CONSTRAINT_TYPE = 'UNIQUE'
                "))->pluck('CONSTRAINT_NAME');

                if ($uniqueConstraints->contains('survey_contacts_survey_campaign_id_contact_group_id_unique')) {
                    $table->dropUnique(['survey_campaign_id', 'contact_group_id']);
                }

                $table->dropColumn('contact_group_id');
            });
        }

        // Add back the old constraints if they don't exist
        Schema::table('survey_contacts', function (Blueprint $table) {
            // Check if the foreign key exists
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_contacts'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME');

            if (!$foreignKeys->contains('survey_contacts_contact_id_foreign')) {
                $table->foreign('contact_id')
                      ->references('id')
                      ->on('contacts')
                      ->onDelete('cascade');
            }

            // Check if the unique constraint exists
            $uniqueConstraints = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'survey_contacts'
                AND CONSTRAINT_TYPE = 'UNIQUE'
            "))->pluck('CONSTRAINT_NAME');

            if (!$uniqueConstraints->contains('survey_contacts_survey_campaign_id_contact_id_unique')) {
                $table->unique(['survey_campaign_id', 'contact_id']);
            }
        });
    }
}; 