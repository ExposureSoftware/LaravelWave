<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave\Commands\Concerns;

trait TurnsOn
{
    public function on(): string
    {
        return "devices/{$this->device->device_id}/command/on";
    }
}
