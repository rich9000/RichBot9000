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



Schema::create('emails', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('message_id')->unique();
    $table->string('parent_folder_id', 255)->nullable();
    $table->timestamp('received_datetime')->nullable();
        $table->text('body');
        $table->text('summary')->nullable();
        $table->string('subject')->nullable();
    $table->boolean('processed')->default(false); // Add this line
        $table->unsignedBigInteger('to_contact_id')->nullable();
        $table->unsignedBigInteger('from_contact_id')->nullable();
        $table->unsignedBigInteger('project_id')->nullable();
        $table->unsignedBigInteger('task_id')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->timestamps();

        $table->foreign('to_contact_id')->references('id')->on('contacts')->onDelete('set null');
        $table->foreign('from_contact_id')->references('id')->on('contacts')->onDelete('set null');
        $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
