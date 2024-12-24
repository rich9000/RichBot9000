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
Schema::create('stage_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stage_id');
            $table->string('file_path', 255);
            $table->string('file_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('stage_id')
                ->references('id')
                ->on('stages')
                ->onDelete('cascade');

            // Index for quicker lookups
            $table->index('stage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_files');
    }
};
