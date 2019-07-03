<?php
/**
 * ExposureSoftware
 */

namespace Tests;

use ExposureSoftware\LaravelWave\Facades\ZwaveFacade;
use ExposureSoftware\LaravelWave\LaravelWaveProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

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

    protected function getMockClient(array $responses = [], array &$history = []): Client
    {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push(Middleware::history($history));

        return new Client(['handler' => $handlerStack]);
    }
}
