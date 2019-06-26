<?php
/**
 * ExposureSoftware
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZwayMetricsTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zway_metrics')) {
            Schema::create('zway_metrics', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('device_id');
                $table->string('probe_title');
                $table->string('scale_title');
                $table->string('level');
                $table->string('icon');
                $table->string('title');
                $table->timestamps();

                $table->foreign('device_id')->references('id')->on('zway_devices');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zway_metrics');
    }
}
