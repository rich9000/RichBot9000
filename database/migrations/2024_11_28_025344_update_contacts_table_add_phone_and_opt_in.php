<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContactsTableAddPhoneAndOptIn extends Migration
{
    public function up()
    {
        
            Schema::table('contacts', function (Blueprint $table) {
                $table->string('phone')->nullable();
                $table->timestamp('opt_in_at')->nullable();
                $table->string('type')->default('contact');
            });
        
    
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['phone', 'opt_in_at', 'type']);
        });
    }
} 