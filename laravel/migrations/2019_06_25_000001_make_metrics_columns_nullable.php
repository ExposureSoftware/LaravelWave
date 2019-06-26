<?php
/**
 * ExposureSoftware
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeMetricsColumnsNullable extends Migration
{
    public function up(): void
    {
        Schema::table('zway_metrics', function (Blueprint $table) {
            $table->string('probe_title')->nullable(true)->change();
            $table->string('scale_title')->nullable(true)->change();
            $table->string('level')->nullable(true)->change();
            $table->string('icon')->nullable(true)->change();
            $table->boolean('is_failed')->nullable(true);
        });
    }

    public function down(): void
    {
        Schema::table('zway_metrics', function (Blueprint $table) {
            $table->string('probe_title')->nullable(false)->change();
            $table->string('scale_title')->nullable(false)->change();
            $table->string('level')->nullable(false)->change();
            $table->string('icon')->nullable(false)->change();
            $table->dropColumn('is_failed');
        });
    }
}
