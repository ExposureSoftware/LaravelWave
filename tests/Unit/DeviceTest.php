<?php

namespace Tests\Unit;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Models\Location;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    public function testsDates(): void
    {
        $date = Carbon::create(1986, 2, 13);
        $device = factory(Device::class)->make();

        $device->update_time = $date->timestamp;
        $device->saveOrFail();

        $this->assertEquals($device->refresh()->update_time, $date);
    }

    public function testSiblings(): void
    {
        factory(Device::class, 3)->create([
            'location' => factory(Location::class)->create()->id,
            'node_id'  => 15,
        ]);
        factory(Device::class)->create([
            'node_id' => 2,
        ]);

        /** @var Device $device */
        $device = Device::first();
        $siblings = $device->siblings();

        $this->assertCount(2, $siblings);
        $this->assertEmpty($siblings->where('node_id', '!=', 15));
    }

    public function testSiblingsWithType(): void
    {
        factory(Device::class, 3)->create([
            'location' => factory(Location::class)->create()->id,
            'node_id'  => 15,
        ]);
        factory(Device::class)->create([
            'location'    => factory(Location::class)->create()->id,
            'node_id'     => 15,
            'device_type' => 'battery',
        ]);
        factory(Device::class)->create([
            'node_id' => 2,
        ]);

        /** @var Device $device */
        $device = Device::first();
        $siblings = $device->siblings('battery');

        $this->assertCount(1, $siblings);
        $this->assertEmpty($siblings->where('node_id', '!=', 15));
        $this->assertEmpty($siblings->where('device_type', '!=', 'battery'));
    }
}
