<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tool_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Create pivot table for tool_group relationships
        Schema::create('tool_tool_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->onDelete('cascade');
            $table->foreignId('tool_group_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tool_tool_group');
        Schema::dropIfExists('tool_groups');
    }
}; 