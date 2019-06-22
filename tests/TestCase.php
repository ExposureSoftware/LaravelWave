<?php
/**
 * ExposureSoftware
 */

namespace Tests;

use ExposureSoftware\LaravelWave\Facades\ZwaveFacade;
use ExposureSoftware\LaravelWave\LaravelWaveProvider;
use Illuminate\Support\Facades\DB;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        DB::table('zway_devices')->get();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelWaveProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Zwave' => ZwaveFacade::class,
        ];
    }
}
