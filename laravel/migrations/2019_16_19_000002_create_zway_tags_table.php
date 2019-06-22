<?php
/**
 * ExposureSoftware
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZwayTagsTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zway_tags')) {
            Schema::create('zway_tags', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zway_tags');
    }
}
