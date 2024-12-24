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

	 Schema::table('stage_assistants', function (Blueprint $table) {
        	$table->unsignedBigInteger('success_stage_id')->nullable()->after('order');
        	$table->unsignedBigInteger('success_tool_id')->nullable()->after('success_stage_id');

	        // Add foreign key constraints if necessary
        	$table->foreign('success_stage_id')->references('id')->on('stages')->onDelete('set null');
        	$table->foreign('success_tool_id')->references('id')->on('tools')->onDelete('set null');
	    });

    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
	Schema::table('stage_assistants', function (Blueprint $table) {
        	$table->dropForeign(['success_stage_id']);
	        $table->dropForeign(['success_tool_id']);
        	$table->dropColumn(['success_stage_id', 'success_tool_id']);
	});

    }


};
