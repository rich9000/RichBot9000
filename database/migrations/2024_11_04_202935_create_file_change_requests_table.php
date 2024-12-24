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



    Schema::create('file_change_requests', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('conversation_id');
        $table->string('file_path'); // Original file path
        $table->text('new_content'); // New file content
        $table->text('original_content')->nullable(); // Original file content for diff comparison
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->timestamps();

        // Foreign keys for conversation_id and user_id
//        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
  //      $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
    });









    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_change_requests');
    }
};
