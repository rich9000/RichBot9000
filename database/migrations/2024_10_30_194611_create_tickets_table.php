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
        Schema::create('ticket_tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->text('raw_data');
            $table->string('file_name')->unique()->nullable();
            $table->string('order_number')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('complete_date')->nullable();
            $table->string('status')->default('In Progress'); // "In Progress", "Completed", etc.
            $table->string('account_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('service_address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('product_type')->nullable(); // "Fiber Internet", "Internet", etc.
            $table->string('service_type')->nullable(); // "CLEC", "Residential", etc.
            $table->timestamp('connect_date')->nullable();
            $table->timestamp('disconnect_date')->nullable();
            $table->string('equipment')->nullable(); // e.g., "Adtran 8614 SMOS"
            $table->string('technician_name')->nullable();
            $table->text('install_notes')->nullable();
            $table->string('drop_type')->nullable();
            $table->text('issues_reported')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('billing_amount', 8, 2)->nullable();
            $table->string('promotions')->nullable();
            $table->decimal('monthly_charge', 8, 2)->nullable();
            $table->decimal('fractional_charge', 8, 2)->nullable();
            $table->decimal('prorated_charge', 8, 2)->nullable();
            $table->text('warnings')->nullable();
            $table->text('comments')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('ssid')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
