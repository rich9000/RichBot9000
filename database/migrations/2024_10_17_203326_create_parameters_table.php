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



Schema::create('parameters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tool_id');
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->boolean('required')->default(false);
            $table->timestamps();

            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');
            $table->unique(['tool_id', 'name']); // Prevent duplicate parameter names per tool
        });




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
