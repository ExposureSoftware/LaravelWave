<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Commands;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use Illuminate\Console\Command;
use Throwable;

class FetchDevices extends Command
{
    protected $signature = 'zway:fetch-devices';
    protected $description = 'Retrieve and store devices from Z-Way server.';

    public function handle(Zwave $zwave): int
    {
        try {
            $this->fetch($zwave);
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
            $exitCode = 1;
        } finally {
            $this->line('Done.');
        }

        return $exitCode ?? 0;
    }

    protected function fetch(Zwave $zwave): void
    {
        $startTime = Carbon::now();

        if ($zwave->hasToken() || $this->call('zway:store-token') === 0) {
            $this->line('Fetching devices...');
            $devices = $zwave->listDevices();
            $newDevices = $devices
                ->filter(static function (Device $device) use ($startTime) {
                    return $startTime->lessThanOrEqualTo($device->{$device->getCreatedAtColumn()});
                })
                ->count();
            $this->info("{$devices->count()} devices reported. {$newDevices} new devices added.");
        }
    }
}
