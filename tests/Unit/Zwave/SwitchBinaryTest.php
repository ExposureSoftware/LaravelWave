<?php
/**
 * ExposureSoftware
 */

namespace Tests\Unit\Zwave;

use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Zwave\Commands\Commands;
use ExposureSoftware\LaravelWave\Zwave\Commands\SwitchBinary;
use Tests\TestCase;

class SwitchBinaryTest extends TestCase
{
    public function testOn(): void
    {
        $device = factory(Device::class)->create([
            'device_type' => 'switchBinary',
        ]);
        /** @var SwitchBinary $command */
        $command = Commands::buildFor($device);
        static::assertSame("v1/devices/{$device->id}/command/on", $command->on());
    }

    public function testOff(): void
    {
        $device = factory(Device::class)->create([
            'device_type' => 'switchBinary',
        ]);
        /** @var SwitchBinary $command */
        $command = Commands::buildFor($device);
        static::assertSame("v1/devices/{$device->id}/command/off", $command->off());
    }
}
