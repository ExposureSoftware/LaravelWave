<?php

use ExposureSoftware\LaravelWave\Models\Location;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */

$factory->define(Location::class, static function (Faker $faker) {
    return [
        'id'   => $faker->unique()->randomNumber(),
        'name' => $faker->unique()->word,
    ];
});
