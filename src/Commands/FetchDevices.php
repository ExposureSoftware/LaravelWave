<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Commands;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Exceptions\NoToken;
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

    protected function login(Zwave $zwave): bool
    {
        $loggedIn = false;
        $this->line('Logging in to Z-Way server...');

        try {
            $loggedIn = $zwave->login();
        } catch (NetworkFailure $e) {
            Log::error("Could not connect to Zwave API: {$e->getMessage()}");
        } catch (NoToken $e) {
            Log::error('Logging in did not successfully create a token.');
        }
        $loggedIn ? $this->info('OK') : $this->error('FAILED');

        return $loggedIn;
    }

    protected function fetch(Zwave $zwave): void
    {
        $startTime = Carbon::now();

        if ($zwave->hasToken() || $this->login($zwave)) {
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
