<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropNodeIdIndex extends Migration
{
    public function up(): void
    {
        Schema::table('zway_devices', static function (Blueprint $table) {
            $table->dropUnique('zway_devices_node_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('zway_devices', static function (Blueprint $table) {
            $table->unique('node_id');
        });
    }
}
