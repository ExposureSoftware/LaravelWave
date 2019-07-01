<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave;

use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Exceptions\NoToken;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Models\Metric;
use ExposureSoftware\LaravelWave\Zwave\Commands\Commands;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use stdClass;
use Throwable;

class Zwave
{
    public const BASE_PATH = '/ZAutomation/api/';

    /** @var ClientInterface */
    protected $client;
    /** @var string */
    protected $token;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->loadTokenFromStorage();
    }

    public function hasToken(): bool
    {
        return is_string($this->token);
    }

    public function listDevices(bool $andSave = true): Collection
    {
        $response = $this->send(new Request(
            'GET',
            'v1/devices'
        ));
        $models = $this->convertToModels(collect($response->devices));

        if ($andSave) {
            $this->save($models);
        }

        return $models->get('devices');
    }

    /**
     * @param string|null $as
     * @param string|null $withPassword
     * @param bool        $andStoreToken
     *
     * @return bool
     * @throws NetworkFailure
     * @throws NoToken
     */
    public function login(string $as = null, string $withPassword = null, bool $andStoreToken = true): bool
    {
        $as = $as ?? config('laravelwave.user');
        $withPassword = $withPassword ?? config('laravelwave.password');

        $response = $this->send(
            new Request(
                'POST',
                'v1/login',
                [],
                \GuzzleHttp\json_encode([
                    'login'    => $as,
                    'password' => $withPassword,
                ])
            ),
            true
        );
        $this->token = $response->sid;

        if ($andStoreToken) {
            $this->storeToken($this->token);
        }

        return $this->hasToken();
    }

    public function update(Device $device, $andSave = true): Device
    {
        $response = $this->send(new Request(
            'GET',
            "v1/devices/{$device->id}"
        ));
        $device->metrics->fill($this->matchColumns($device->metrics, collect((array) $response->metrics)));

        if ($andSave) {
            $device->metrics->save();
        }

        return $device;
    }

    public function command(Device $device, string $to, array $with): bool
    {
        return $this->run($to, Commands::buildFor($device), $with);
    }

    protected function run(string $command, Commands $ofType, array $given): bool
    {
        return (method_exists($ofType, $command) && is_callable([$ofType, $command]))
            ? $ofType->$command(...$given)
            : false;
    }

    protected function commandsFor(Device $device): Commands
    {
        return Commands::buildFor($device);
    }

    protected function convertToModels(Collection $devices): Collection
    {
        $deviceModels = collect();
        $metricModels = collect();

        $devices->each(function (stdClass $attributes) use ($deviceModels, $metricModels): void {
            $device = Device::first(['id' => $attributes->id]) ?? $this->device(collect((array) $attributes));
            $deviceModels->push($device);
            $metricModels->push($device->metrics ?? $this->metrics($device, collect((array) $attributes->metrics)));
        });

        return collect([
            'devices' => $deviceModels,
            'metrics' => $metricModels,
        ]);
    }

    protected function metrics(Device $for, Collection $from): Metric
    {
        $metric = new Metric([
            'device_id' => $for->id,
        ]);

        return $metric->fill($this->matchColumns($metric, $from));
    }

    protected function device(Collection $from): Device
    {
        $device = new Device([
            /**
             * Why this attribute is not camel case is a mystery.
             */
            'permanently_hidden' => $from->get('permanently_hidden'),
        ]);

        return $device->fill($this->matchColumns($device, $from));
    }

    protected function matchColumns(Model $on, Collection $toAttributes): array
    {
        return collect(Schema::getColumnListing($on->getTable()))
            ->flip()
            ->map(function (int $value, string $attribute) use ($toAttributes) {
                return $toAttributes->get(Str::camel($attribute));
            })
            ->reject(function ($value): bool {
                return is_null($value);
            })
            ->toArray();
    }

    protected function save(Collection $models): bool
    {
        try {
            $saved = DB::transaction(function () use ($models) {
                return $models->reduce(function (bool $saved, Collection $modelSet) {
                    return $saved && $modelSet->reduce(function (bool $saved, Model $model) {
                            return $saved && $model->save();
                        }, true);
                }, true);
            });
        } catch (Throwable $e) {
            dd($e);
            $saved = false;
        }

        return $saved;
    }

    protected function storeToken(string $value): void
    {
        Storage::disk('local')->put('zwave_token', encrypt($value));
    }

    protected function addHeadersTo(RequestInterface $request): RequestInterface
    {
        if ($this->hasToken()) {
            $request = $request->withAddedHeader('ZWAYSession', $this->token);
            Log::debug('Added Authorization token to request.');
        }

        return $request;
    }

    protected function loadTokenFromStorage(): bool
    {
        if (Storage::disk('local')->exists('zwave_token')) {
            try {
                $this->token = decrypt(Storage::disk('local')->get('zwave_token'));
            } catch (FileNotFoundException $e) {
                Log::error("Token storage does not exist following check.");
            } catch (DecryptException $e) {
                Log::error("Could not decrypt token because {$e->getMessage()}");
            }
        }
        Log::debug('Loaded token from file storage.');

        return $this->hasToken();
    }

    /**
     * @param RequestInterface $request
     * @param bool             $withoutToken
     *
     * @return Response
     * @throws NetworkFailure
     * @throws NoToken
     */
    protected function send(RequestInterface $request, bool $withoutToken = false): Response
    {
        if (!$withoutToken && !$this->hasToken()) {
            throw new NoToken();
        }

        try {
            return new Response($this->client->send($this->addHeadersTo($request)));
        } catch (GuzzleException $e) {
            throw new NetworkFailure($e);
        }
    }
}
