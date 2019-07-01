<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave;

use ExposureSoftware\LaravelWave\Commands\FetchDevices;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class LaravelWaveProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.str_replace('/', \DIRECTORY_SEPARATOR, '/../laravel/config/laravelwave.php') => config_path('laravelwave.php'),
        ]);
        $this->loadMigrationsFrom(__DIR__.str_replace('/', \DIRECTORY_SEPARATOR, '/../laravel/migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchDevices::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->singleton(Zwave::class, function () {
            return new Zwave(new Client([
                'base_uri' => implode('', [
                    config('laravelwave.host'),
                    ':',
                    config('laravelwave.port'),
                    Zwave::BASE_PATH,
                ]),
            ]));
        });
    }
}
