<?php
/**
 * ExposureSoftware
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNodeIdToZwayDevices extends Migration
{
    public function up(): void
    {
        Schema::table('zway_devices', function (Blueprint $table) {
            $table->integer('node_id')->nullable(true)->unique();
        });
    }

    public function down(): void
    {
        Schema::table('zway_devices', function (Blueprint $table) {
            $table->dropColumn('node_id');
        });
    }
}
