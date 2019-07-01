<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave\Commands;

use ExposureSoftware\LaravelWave\Models\Device;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

abstract class Commands
{
    /** @var Device */
    protected $device;

    public static function buildFor(Device $device): Commands
    {
        $class = join('\\', [
            __NAMESPACE__,
            Str::studly($device->device_type)
        ]);

        $commands = class_exists($class) && is_subclass_of($class, Commands::class) ? App::make($class) : App::make(Basic::class);
        $commands->device = $device;

        return $commands;
    }
}
