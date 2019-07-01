<?php
/**
 * ExposureSoftware
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZwayDevicesTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zway_devices')) {
            Schema::create('zway_devices', function (Blueprint $table) {
                $table->string('id')->unique();
                $table->string('device_type');
                $table->timestamp('update_time');
                $table->timestamp('creation_time');
                $table->integer('creator_id');
                $table->boolean('has_history');
                $table->boolean('permanently_hidden');
                $table->string('probeType');
                $table->boolean('visibility');
                $table->timestamps();

                $table->primary('id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zway_devices');
    }
}
