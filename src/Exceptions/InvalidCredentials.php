<?php

namespace ExposureSoftware\LaravelWave\Exceptions;

class InvalidCredentials extends LaravelWaveException
{
    public function __construct()
    {
        parent::__construct('Failed to log in. Please check ZWave credentials.');
    }
}
