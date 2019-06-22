<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Exceptions;

class NoToken extends LaravelWaveException
{
    public function __construct()
    {
        parent::__construct('No token available. Must log in to create token.');
    }
}
