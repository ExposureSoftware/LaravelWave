<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Exceptions;

use GuzzleHttp\Exception\GuzzleException;

class NetworkFailure extends LaravelWaveException
{
    public function __construct(GuzzleException $guzzleException)
    {
        parent::__construct('Failed to connect to Z-Way Server.', $guzzleException->getCode(), $guzzleException);
    }
}
