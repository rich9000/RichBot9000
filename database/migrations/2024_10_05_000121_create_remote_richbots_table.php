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
        Schema::create('remote_richbots', function (Blueprint $table) {
            $table->id();
            $table->string('remote_richbot_id')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('status')->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_richbots');
    }
};
