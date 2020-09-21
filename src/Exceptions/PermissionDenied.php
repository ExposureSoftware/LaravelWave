<?php

namespace ExposureSoftware\LaravelWave\Exceptions;

class PermissionDenied extends LaravelWaveException
{
    public function __construct()
    {
        parent::__construct('User does not have permission to perform selected action.');
    }
}
