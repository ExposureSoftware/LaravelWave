<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationColumn extends Migration
{
    public function up(): void
    {
        Schema::table('zway_devices', function (Blueprint $table) {
            $table->integer('location')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('zway_devices', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
}
