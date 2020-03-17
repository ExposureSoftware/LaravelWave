<?php

namespace Tests\Unit;

use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Models\Location;
use ExposureSoftware\LaravelWave\Models\Metric;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LocationTest extends TestCase
{
    public function testDevices(): void
    {
        factory(Metric::class)->create();

        $location = Location::first();

        $this->assertSame(Device::first()->id, $location->devices->first()->id);
    }

    public function testHasDevicesScope(): void
    {
        factory(Metric::class, 3)->create();
        factory(Location::class, 3)->create();

        /** @var Collection $locations */
        $locations = Location::hasDevices()->get();

        $this->assertSame(3, $locations->count());
        $locations->each(function (Location $location) {
            $this->assertNotEmpty($location->devices);
        });
    }
}
