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


    Schema::create('coding_sessions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Adds user_id with a foreign key
        $table->string('session_name')->nullable();
        $table->json('prompt')->nullable();
        $table->json('files')->nullable();
        $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
        $table->timestamps();
    });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coding_sessions');
    }
};
