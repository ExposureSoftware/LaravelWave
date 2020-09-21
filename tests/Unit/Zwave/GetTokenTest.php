<?php

namespace Tests\Unit\Zwave;

use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GetTokenTest extends TestCase
{
    public function testTokenCreation(): void
    {
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('put')->andReturnTrue();
        Storage::shouldReceive('exists')->andReturnFalse();
        $this->app->bind(Zwave::class, function () {
            return new Zwave($this->getMockClient([
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'sid' => 'aToken',
                        ],
                    ])
                ),
            ]));
        });

        $this->artisan('zway:store-token')
            ->assertExitCode(0);
    }

    public function testLoginError(): void
    {
        $this->expectException(NetworkFailure::class);
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('put')->andReturnTrue();
        Storage::shouldReceive('exists')->andReturnFalse();
        $this->app->bind(Zwave::class, function () {
            return new Zwave($this->getMockClient([
                new Response(500),
            ]));
        });

        $this->artisan('zway:store-token')
            ->assertExitCode(0);
    }
}
