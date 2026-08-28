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
        // Check which table exists
        $userContactsExists = Schema::hasTable('user_contacts');
        $contactGroupsExists = Schema::hasTable('contact_groups');

        if ($userContactsExists) {
            // If user_contacts exists, we need to rename it
            Schema::table('user_contacts', function (Blueprint $table) {
                // Check if the foreign key exists before dropping it
                $foreignKeys = collect(DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'user_contacts'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                "))->pluck('CONSTRAINT_NAME');

                if ($foreignKeys->contains('user_contacts_user_id_foreign')) {
                    $table->dropForeign('user_contacts_user_id_foreign');
                }
                
                $table->dropColumn('user_id');
            });

            Schema::rename('user_contacts', 'contact_groups');
        } elseif (!$contactGroupsExists) {
            // If neither table exists, create contact_groups
            Schema::create('contact_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contact_id');
                $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
                $table->morphs('groupable');
                $table->string('name');
                $table->string('type')->default('contact');
                $table->boolean('allowed_to_contact')->default(true);
                $table->timestamps();
            });
        }

        // If contact_groups exists (either from rename or creation), add/modify columns
        if (Schema::hasTable('contact_groups')) {
            Schema::table('contact_groups', function (Blueprint $table) {
                if (!Schema::hasColumn('contact_groups', 'groupable_type')) {
                    $table->morphs('groupable');
                }
                if (Schema::hasColumn('contact_groups', 'context')) {
                    $table->renameColumn('context', 'type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contact_groups')) {
            Schema::table('contact_groups', function (Blueprint $table) {
                if (Schema::hasColumn('contact_groups', 'groupable_type')) {
                    $table->dropMorphs('groupable');
                }
                if (Schema::hasColumn('contact_groups', 'type')) {
                    $table->renameColumn('type', 'context');
                }
            });

            Schema::rename('contact_groups', 'user_contacts');

            Schema::table('user_contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->after('contact_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
}; 