<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave\Commands\Concerns;

trait TurnsOn
{
    public function on(): string
    {
        return "devices/{$this->device->id}/command/on";
    }
}
