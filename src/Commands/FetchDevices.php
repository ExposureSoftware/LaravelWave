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
            if ($zwave->hasToken() || $this->login($zwave)) {
                $this->fetch($zwave);
            } else {
                $exitCode = 1;
            }
        } catch (Throwable $throwable) {
            throw $throwable;
            $this->error($throwable->getMessage());
            $exitCode = 1;
        } finally {
            $this->line('Done.');
        }

        return $exitCode ?? 0;
    }

    protected function login(Zwave $zwave): bool
    {
        $this->line('Logging in to Z-Way server...');
        $loggedIn = $zwave->login();
        $loggedIn ? $this->info('OK') : $this->error('FAILED');

        return $loggedIn;
    }

    protected function fetch(Zwave $zwave): void
    {
        $startTime = Carbon::now();
        $this->line('Fetching devices...');
        $devices = $zwave->listDevices();
        $newDevices = $devices
            ->filter(function (Device $device) use ($startTime) {
                return $startTime->lessThanOrEqualTo($device->{$device->getCreatedAtColumn()});
            })
            ->count();
        $this->info("{$devices->count()} devices reported. {$newDevices} new devices added.");
    }
}
