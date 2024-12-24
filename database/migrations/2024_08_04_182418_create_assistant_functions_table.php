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
        Schema::create('assistant_functions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('description');
            $table->json('parameters')->nullable();
            $table->longText('code')->nullable();
            $table->enum('status', ['active', 'inactive', 'deprecated'])->default('active');
            $table->string('version')->default('1.0');
            $table->unsignedBigInteger('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_functions');
    }
};
