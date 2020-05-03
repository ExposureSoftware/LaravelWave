<?php

namespace Tests\Unit;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Models\Device;
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
}
