<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Events;

use ExposureSoftware\LaravelWave\Models\Device;
use Illuminate\Queue\SerializesModels;

class CommandSent
{
    use SerializesModels;

    /** @var Device */
    public $device;
    /** @var string */
    public $command;
    /** @var bool */
    public $successful;
    /** @var array */
    public $parameters = [];

    public function __construct(string $command, Device $device, bool $successful, array $parameters = [])
    {
        $this->command = $command;
        $this->device = $device;
        $this->successful = $successful;
        $this->parameters = $parameters;
    }
}
