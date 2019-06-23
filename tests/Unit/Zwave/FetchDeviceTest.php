<?php
/**
 * ExposureSoftware
 */

namespace Tests\Unit\Zwave;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class FetchDeviceTest extends TestCase
{
    public function testErrorLoggingIn(): void
    {
        $this->app->bind(Zwave::class, function () {
            $mockZwave = Mockery::mock(Zwave::class);
            $mockZwave->shouldReceive('hasToken')->once()->andReturnFalse();
            $mockZwave->shouldReceive('login')->once()->andReturnFalse();

            return $mockZwave;
        });

        $this->artisan('zway:fetch-devices')->assertExitCode(1);
    }

    public function testFetchesDevices(): void
    {
        $this->app->bind(Zwave::class, function () {
            /** @var Collection $devices */
            $devices = factory(Device::class, 5)->create([
                'created_at' => Carbon::now()->subDay(),
            ]);
            $devices = $devices->merge(factory(Device::class, 3)->create([
                'created_at' => Carbon::now()->addMinutes(5),
            ]));
            $mockZwave = Mockery::mock(Zwave::class);
            $mockZwave->shouldReceive('hasToken')->once()->andReturnTrue();
            $mockZwave->shouldReceive('listDevices')
                ->withNoArgs()
                ->andReturn($devices);

            return $mockZwave;
        });

        $this->artisan('zway:fetch-devices')
            ->expectsOutput("8 devices reported. 3 new devices added.")
            ->assertExitCode(0);
    }
}
