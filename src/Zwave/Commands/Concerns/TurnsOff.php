<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave\Commands\Concerns;

trait TurnsOff
{
    public function off(): string
    {
        return "devices/{$this->device->device_id}/command/off";
    }
}
