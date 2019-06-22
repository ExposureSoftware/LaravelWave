<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Exceptions;


class TokenInvalid extends LaravelWaveException
{
    public function __construct()
    {
        parent::__construct('Token was rejected. Please log in again.');
    }
}
