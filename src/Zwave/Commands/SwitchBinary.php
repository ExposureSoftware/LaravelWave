<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave\Commands;

use ExposureSoftware\LaravelWave\Zwave\Commands\Concerns\TurnsOff;
use ExposureSoftware\LaravelWave\Zwave\Commands\Concerns\TurnsOn;

class SwitchBinary extends Commands
{
    use TurnsOn;
    use TurnsOff;
}
