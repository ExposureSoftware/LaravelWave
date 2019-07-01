<?php
/**
 * ExposureSoftware
 */

namespace Tests;

use ExposureSoftware\LaravelWave\Facades\ZwaveFacade;
use ExposureSoftware\LaravelWave\LaravelWaveProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/factories');
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
