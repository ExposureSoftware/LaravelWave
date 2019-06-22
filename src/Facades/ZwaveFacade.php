<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Facades;

use ExposureSoftware\LaravelWave\Zwave\Zwave;
use Illuminate\Support\Facades\Facade;

class ZwaveFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Zwave::class;
    }
}
