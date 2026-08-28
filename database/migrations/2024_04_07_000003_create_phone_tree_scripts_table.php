<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phone_tree_scripts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_tree_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('path');
            $table->json('parameters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('phone_tree_id')->references('id')->on('phone_trees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        // First drop any foreign key constraints referencing this table
        Schema::table('phone_tree_menus', function (Blueprint $table) {
            if (Schema::hasColumn('phone_tree_menus', 'script_id')) {
                $table->dropForeign(['script_id']);
            }
        });

        // Then drop the table
        Schema::dropIfExists('phone_tree_scripts');
    }
}; 