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


        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable(); // Null for system-wide integrations
            $table->string('scope')->default('user'); // 'user' or 'system'
            $table->string('service'); // e.g., 'gmail', 'openweathermap'
            $table->string('service_type'); // e.g., 'api', 'email', 'database'
            $table->string('auth_type'); // e.g., 'basic', 'bearer', 'oauth2', 'apikey'
            $table->string('server_url')->nullable(); // For custom server-based integrations
            $table->string('api_key')->nullable(); // For API key-based auth
            $table->string('username')->nullable(); // For email or basic auth
            $table->string('password')->nullable(); // Encrypted password
            $table->string('access_token')->nullable(); // For token-based auth
            $table->string('refresh_token')->nullable(); // To refresh access_token
            $table->timestamp('token_expires_at')->nullable(); // Token expiration
            $table->timestamps();
        });
        



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
