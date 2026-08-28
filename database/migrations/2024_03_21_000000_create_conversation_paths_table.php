<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_paths', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('nodes');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('conversation_path_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('current_node_index')->nullable();
            $table->json('path_state')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['conversation_path_id']);
            $table->dropColumn(['conversation_path_id', 'current_node_index', 'path_state']);
        });

        Schema::dropIfExists('conversation_paths');
    }
}; 