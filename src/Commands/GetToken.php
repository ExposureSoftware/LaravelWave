<?php

namespace ExposureSoftware\LaravelWave\Commands;

use ExposureSoftware\LaravelWave\Exceptions\InvalidCredentials;
use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Exceptions\PermissionDenied;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class GetToken extends Command
{
    protected $signature = 'zway:store-token';
    protected $description = 'Retrieve and store API token from Z-Way server.';

    /**
     * @param Zwave $zwave
     *
     * @return int
     * @throws NetworkFailure
     * @throws InvalidCredentials
     * @throws PermissionDenied
     */
    public function handle(Zwave $zwave): int
    {
        $this->line('Logging in to Z-Way server...');

        try {
            $zwave->login();
        } catch (GuzzleException $exception) {
            throw new NetworkFailure($exception);
        }

        $this->info('OK');

        return 0;
    }
}
