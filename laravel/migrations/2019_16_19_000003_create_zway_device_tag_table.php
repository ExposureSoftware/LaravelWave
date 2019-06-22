<?php
/**
 * ExposureSoftware
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZwayDeviceTagTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zway_device_zway_tag')) {
            Schema::create('zway_device_zway_tag', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('zway_device_id');
                $table->integer('zway_tag_id');
                $table->timestamps();

                $table->foreign('zway_device_id')->references('id')->on('zway_devices');
                $table->foreign('zway_tag_id')->references('id')->on('zway_tags');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zway_device_zway_tag');
    }
}
