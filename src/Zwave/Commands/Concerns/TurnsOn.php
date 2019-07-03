<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave\Commands\Concerns;

trait TurnsOn
{
    public function on(): string
    {
        return "v1/devices/{$this->device->id}/command/on";
    }
}
