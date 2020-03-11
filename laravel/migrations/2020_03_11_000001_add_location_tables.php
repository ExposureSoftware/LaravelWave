<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationTables extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zway_locations')) {
            Schema::create('zway_locations', static function (Blueprint $table) {
                $table->integer('id');
                $table->string('name');
                $table->timestamps();

                $table->primary('id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zway_locations');
    }
}
