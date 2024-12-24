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
 Schema::create('stage_tools', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stage_id');
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('success_stage_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('stage_id')
                ->references('id')
                ->on('stages')
                ->onDelete('cascade');

            $table->foreign('tool_id')
                ->references('id')
                ->on('tools')
                ->onDelete('cascade');

            $table->foreign('success_stage_id')
                ->references('id')
                ->on('stages')
                ->onDelete('set null');

            // Indexes for quicker lookups
            $table->index('stage_id');
            $table->index('tool_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_tools');
    }
};
