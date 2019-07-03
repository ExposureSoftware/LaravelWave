<?php
/**
 * ExposureSoftware
 */

namespace Tests\Unit\Zwave;

use ExposureSoftware\LaravelWave\Events\CommandSent;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Zwave\Commands\SwitchBinary;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EventsTest extends TestCase
{
    public function testCommandEmitsEvent(): void
    {
        /** @var Device $device */
        $device = factory(Device::class)->create([
            'device_type' => 'switchBinary',
        ]);
        Event::fake();
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        App::bind(SwitchBinary::class, function () {
            $mockBasics = Mockery::mock(SwitchBinary::class);
            $mockBasics->shouldReceive('color')->with(1, 2, 3)->once()->andReturn('api/v1/test');

            return $mockBasics;
        });

        (new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'code' => 200,
                    ])
                ),
            ]
        )))
            ->command($device, 'color', [1, 2, 3]);

        Event::assertDispatched(CommandSent::class, function (CommandSent $event) use ($device) {
            return $device->id === $event->device->id
                && $event->successful === true
                && $event->command === 'color';
        });
    }

    public function testCommandDoesNotEmitsEventWhenFailed(): void
    {
        /** @var Device $device */
        $device = factory(Device::class)->create([
            'device_type' => 'switchBinary',
        ]);
        Event::fake();
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        App::bind(SwitchBinary::class, function () {
            $mockBasics = Mockery::mock(SwitchBinary::class);
            $mockBasics->shouldReceive('color')->with(1, 2, 3)->once()->andReturn('api/v1/test');

            return $mockBasics;
        });

        (new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'code' => 500,
                    ])
                ),
            ]
        )))
            ->command($device, 'color', [1, 2, 3]);

        Event::assertNotDispatched(CommandSent::class, function (CommandSent $event) use ($device) {
            return $device->id === $event->device->id
                && $event->successful === true
                && $event->command === 'color';
        });
    }
}
