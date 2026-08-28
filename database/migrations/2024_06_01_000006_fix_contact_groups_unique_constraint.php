<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing records with empty groupable fields
        // We'll assign them to the first user (usually admin)
        $adminUser = User::find(1);
        if ($adminUser) {
            DB::table('contact_groups')
                ->where('groupable_type', '')
                ->orWhereNull('groupable_type')
                ->update([
                    'groupable_type' => User::class,
                    'groupable_id' => $adminUser->id
                ]);
        }

        // Drop foreign key constraints first
        $foreignKeys = collect(DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'contact_groups'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        "))->pluck('CONSTRAINT_NAME');

        foreach ($foreignKeys as $foreignKey) {
            Schema::table('contact_groups', function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });
        }

        // Drop the old unique constraint if it exists
        $uniqueConstraints = collect(DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'contact_groups'
            AND CONSTRAINT_TYPE = 'UNIQUE'
        "))->pluck('CONSTRAINT_NAME');

        if ($uniqueConstraints->contains('user_contacts_user_id_contact_id_unique')) {
            Schema::table('contact_groups', function (Blueprint $table) {
                $table->dropUnique('user_contacts_user_id_contact_id_unique');
            });
        }

        // Also check for the simple contact_id unique constraint
        if ($uniqueConstraints->contains('contact_groups_contact_id_unique')) {
            Schema::table('contact_groups', function (Blueprint $table) {
                $table->dropUnique('contact_groups_contact_id_unique');
            });
        }

        // Add the new unique constraint
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->unique(['contact_id', 'groupable_type', 'groupable_id'], 'contact_groups_unique');
        });

        // Add back the foreign key constraint
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('contacts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints first
        $foreignKeys = collect(DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'contact_groups'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        "))->pluck('CONSTRAINT_NAME');

        foreach ($foreignKeys as $foreignKey) {
            Schema::table('contact_groups', function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });
        }

        // Drop the new unique constraint
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->dropUnique('contact_groups_unique');
        });

        // Add back the old unique constraint
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->unique('contact_id', 'contact_groups_contact_id_unique');
        });

        // Add back the foreign key constraint
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('contacts')
                  ->onDelete('cascade');
        });

        // Reset groupable fields to empty
        DB::table('contact_groups')
            ->where('groupable_type', User::class)
            ->where('groupable_id', 1)
            ->update([
                'groupable_type' => '',
                'groupable_id' => 0
            ]);
    }
}; 