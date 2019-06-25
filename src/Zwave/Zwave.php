<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave;

use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Exceptions\NoToken;
use ExposureSoftware\LaravelWave\Models\Device;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use stdClass;

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
        $devices = $this->convertToModels(collect($response->devices));

        if ($andSave) {
            $this->save($devices);
        }

        return $devices;
    }

    /**
     * @param string|null $user
     * @param string|null $withPassword
     * @param bool        $andStoreToken
     *
     * @return bool
     * @throws NetworkFailure
     * @throws NoToken
     */
    public function login(string $user = null, string $withPassword = null, bool $andStoreToken = true): bool
    {
        $user = $user ?? config('laravelwave.user');
        $withPassword = $withPassword ?? config('laravelwave.password');

        $response = $this->send(
            new Request(
                'POST',
                'v1/login',
                [],
                \GuzzleHttp\json_encode([
                    'login'    => $user,
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

    protected function convertToModels(Collection $devices): Collection
    {
        return $devices->transform(function (stdClass $attributes): Device {
            /** @var Device $device */
            $device = Device::first(['id' => $attributes->id]) ?? $this->deviceFrom(collect((array) $attributes));

            return $device;
        });
    }

    protected function deviceFrom(Collection $attributes): Device
    {
        $device = new Device([
            /**
             * Why this attribute is not camel case is a mystery.
             */
            'permanently_hidden' => $attributes->get('permanently_hidden'),
        ]);
        return $device->fill(
            collect(Schema::getColumnListing($device->getTable()))
                ->flip()
                ->map(function (int $value, string $attribute) use ($attributes) {
                    return $attributes->get(Str::camel($attribute));
                })
                ->reject(function ($value): bool {
                    return is_null($value);
                })
                ->toArray()
        );
    }

    protected function save(Collection $devices): bool
    {
        return $devices->reduce(
            function (bool $saved, Device $device): bool {
                return $device->save() && $saved;
            },
            true
        );
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
