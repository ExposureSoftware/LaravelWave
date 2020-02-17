<?php
/**
 * ExposureSoftware
 */
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Models\Metric;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/* @var Factory $factory */

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

$factory->define(Metric::class, function (Faker $faker) {
    return [
        'device_id' => function () {
            return factory(Device::class)->create()->id;
        },
        'probe_title' => 'Temperature',
        'scale_title' => 'C',
        'level'       => 40,
        'icon'        => 'temperature',
        'title'       => 'Temperature - Mars',
    ];
});
