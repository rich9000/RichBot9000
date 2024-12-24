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
    Schema::create('assistant_tool', function (Blueprint $table) {
        $table->id();
        $table->foreignId('assistant_id')->constrained('assistants')->onDelete('cascade');
        $table->foreignId('tool_id')->constrained('tools')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_tool');
    }
};
