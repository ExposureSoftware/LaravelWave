<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave;

use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class LaravelWaveProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            '../laravel/config/laravelwave.php' => config_path('laravelwave.php'),
        ]);
        $this->loadMigrationsFrom('laravel/migrations');
    }

    public function register(): void
    {
        $this->app->singleton(Zwave::class, function () {
            return new Zwave(new Client([
                'base_uri' => join('', [
                    config('laravelwave.host'),
                    ':',
                    config('laravelwave.port'),
                    Zwave::BASE_PATH,
                ]),
            ]));
        });
    }
}
