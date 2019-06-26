<?php
/**
 * ExposureSoftware
 */

use ExposureSoftware\LaravelWave\Models\Device;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Device::class, function (Faker $faker) {
    return [
        'id'                 => $faker->unique()->word,
        'device_type'        => $faker->word,
        'update_time'        => $faker->unixTime,
        'creation_time'      => $faker->unixTime,
        'has_history'        => $faker->boolean,
        'creator_id'         => $faker->randomNumber(),
        'permanently_hidden' => $faker->boolean,
        'probeType'          => $faker->word,
        'visibility'         => $faker->boolean,
        'node_id'            => $faker->unique()->randomNumber(),
    ];
});
