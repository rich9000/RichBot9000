<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tool_groups', function (Blueprint $table) {
            $table->string('type')->default('global')->after('description');
        });
    }

    public function down()
    {
        Schema::table('tool_groups', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}; 