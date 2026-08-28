<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tool_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Add unique constraint to prevent duplicate assignments
            $table->unique(['tool_group_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tool_group_user');
    }
}; 