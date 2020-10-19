<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Commands;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Exceptions\InvalidCredentials;
use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Exceptions\PermissionDenied;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

    /**
     * @param Zwave $zwave
     * @param bool  $retry
     *
     * @throws InvalidCredentials
     * @throws NetworkFailure
     * @throws PermissionDenied
     */
    protected function fetch(Zwave $zwave, $retry = true): void
    {
        $startTime = Carbon::now();
        $devices = collect();

        if ($zwave->hasToken() || $this->call('zway:store-token') === 0) {
            $this->line('Fetching devices...');

            try {
                $devices = $zwave->listDevices();
            } catch (InvalidCredentials $unauthorized) {
                if ($retry) {
                    Log::debug('Invalid token for fetching devices. Attempting to retrieve new token.');
                    $this->call('zway:store-token');
                    $this->fetch($zwave, false);
                } else {
                    throw $unauthorized;
                }
            }

            Log::debug('Counting new devices.');
            $newDevices = $devices
                ->filter(static function (Device $device) use ($startTime) {
                    return $startTime->lessThanOrEqualTo($device->{$device->getCreatedAtColumn()});
                })
                ->count();
            $this->info("{$devices->count()} devices reported. {$newDevices} new devices added.");
        }
    }
}
