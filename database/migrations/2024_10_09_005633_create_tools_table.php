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
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Tool name, e.g., 'generate_image'
            $table->string('method'); // HTTP method, e.g., 'get', 'post'
            $table->string('summary')->nullable(); // Brief description
            $table->string('operation_id')->nullable(); // Unique operation ID
            $table->json('parameters')->nullable(); // Tool parameters in JSON
            $table->json('responses')->nullable(); // Tool responses in JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
