<?php
/**
 * ExposureSoftware
 */

namespace Tests\Unit\Zwave;

use ExposureSoftware\LaravelWave\Exceptions\NetworkFailure;
use ExposureSoftware\LaravelWave\Exceptions\NoToken;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Models\Metric;
use ExposureSoftware\LaravelWave\Zwave\Commands\SwitchBinary;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class ZwaveTest extends TestCase
{
    /**
     * @dataProvider storageProvider
     *
     * @param bool $hasToken
     * @param bool $exists
     */
    public function testLoadsTokenFromStorage(bool $hasToken, bool $exists): void
    {
        $token = encrypt('token');
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturn($exists);
        Storage::shouldReceive('get')->with('zwave_token')->andReturn($token);
        Log::shouldReceive('error');
        Log::shouldReceive('debug');

        $zwave = new Zwave($this->getMockClient());

        static::assertSame(
            $hasToken,
            $zwave->hasToken(),
            implode('', [
                'File ',
                ($exists ? 'does ' : 'does not '),
                "exist with value '{$token}'. ",
                'Should return ',
                ($hasToken ? 'true' : 'false'),
                '.',
            ])
        );
    }

    public function testHandlesMissingTokenFile(): void
    {
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andThrow(FileNotFoundException::class);
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug');

        $zwave = new Zwave($this->getMockClient());

        static::assertFalse($zwave->hasToken());
    }

    public function testHandlesBadTokenData(): void
    {
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn('');
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug');

        $zwave = new Zwave($this->getMockClient());

        static::assertFalse($zwave->hasToken());
    }

    public function testWillNotSendWithoutToken(): void
    {
        $this->expectException(NoToken::class);
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();

        $zwave = new Zwave($this->getMockClient());

        $zwave->listDevices();
    }

    public function testLoginSendsWithoutToken(): void
    {
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();
        Storage::shouldReceive('put')->once()->andReturnTrue();

        $zwave = new Zwave($this->getMockClient([
            new Response(
                200,
                [],
                \GuzzleHttp\json_encode([
                    'data' => (object) [
                        'id'                        => 1,
                        'role'                      => 1,
                        'login'                     => 'aLogin',
                        'name'                      => 'Marshall A. Davis',
                        'lang'                      => 'en',
                        'color'                     => '#dddddd',
                        'dashboard'                 => [],
                        'interval'                  => 2000,
                        'rooms'                     => [
                            0,
                        ],
                        'expert_view'               => true,
                        'hide_all_device_events'    => false,
                        'hide_system_events'        => false,
                        'hide_single_device_events' => [],
                        'night_mode'                => true,
                        'email'                     => 'test@exposuresoftware.com',
                        'sid'                       => '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa',
                    ],
                    'code'    => 200,
                    'message' => '200 OK',
                    'error'   => null,
                ])
            ),
        ]));

        $zwave->login();
    }

    public function testHandlesClientException(): void
    {
        $this->expectException(NetworkFailure::class);
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();

        $zwave = new Zwave($this->getMockClient([
            new TransferException('Mocked Failure'),
        ]));

        $zwave->login();
    }

    public function testNoHeadersWithoutToken(): void
    {
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();
        Storage::shouldReceive('put')->once()->andReturnTrue();

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'id'                        => 1,
                            'role'                      => 1,
                            'login'                     => 'aLogin',
                            'name'                      => 'Marshall A. Davis',
                            'lang'                      => 'en',
                            'color'                     => '#dddddd',
                            'dashboard'                 => [],
                            'interval'                  => 2000,
                            'rooms'                     => [
                                0,
                            ],
                            'expert_view'               => true,
                            'hide_all_device_events'    => false,
                            'hide_system_events'        => false,
                            'hide_single_device_events' => [],
                            'night_mode'                => true,
                            'email'                     => 'test@exposuresoftware.com',
                            'sid'                       => '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa',
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->login();

        static::assertArrayNotHasKey('Authorization', $history[0]['request']->getHeaders());
    }

    public function testProvidesAuthorizationHeaderWithToken(): void
    {
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'structureChanged' => false,
                            'updateTime'       => 1561091908,
                            'devices'          => [
                                (object) [
                                    'creationTime'       => 1560912400,
                                    'creatorId'          => 12,
                                    'customIcons'        => (object) [],
                                    'deviceType'         => 'toggleButton',
                                    'h'                  => -1891043069,
                                    'hasHistory'         => false,
                                    'id'                 => 'MailNotifier_12',
                                    'location'           => 0,
                                    'metrics'            => (object) [
                                        'level'   => 'on',
                                        'title'   => 'Send Email Notification',
                                        'icon'    => '/ZAutomation/api/v1/load/modulemedia/MailNotifier/icon.png',
                                        'message' => '',
                                    ],
                                    'order'              => (object) [
                                        'rooms'     => 0,
                                        'elements'  => 0,
                                        'dashboard' => 0,
                                    ],
                                    'permanently_hidden' => false,
                                    'probeType'          => 'notification_email',
                                    'tags'               => [
                                        'testing',
                                        'mocked',
                                    ],
                                    'visibility'         => true,
                                    'updateTime'         => 1560976328,
                                ],
                                (object) [
                                    'creationTime'       => 1560976328,
                                    'creatorId'          => 5,
                                    'customIcons'        => (object) [],
                                    'deviceType'         => 'text',
                                    'h'                  => -1261400328,
                                    'hasHistory'         => false,
                                    'id'                 => 'InfoWidget_5_Int',
                                    'location'           => 0,
                                    'metrics'            => (object) [
                                        'title' => 'Dear Expert User',
                                        'text'  => '<div style="text-align: center;">If you still want to use ExpertUI please go, after you are successfully logged in, to <br><strong> Menu > Devices > Manage with ExpertUI </strong> <br> or call <br><strong> http =>//MYRASP =>8083/expert </strong><br> in your browser. <br> <br>You could hide or remove this widget in menu <br><strong>Apps > Active Tab</strong>. </div>',
                                        'icon'  => 'app/img/logo-z-wave-z-only.png',
                                    ],
                                    'order'              => (object) [
                                        'rooms'     => 0,
                                        'elements'  => 0,
                                        'dashboard' => 0,
                                    ],
                                    'permanently_hidden' => false,
                                    'probeType'          => '',
                                    'tags'               => [],
                                    'visibility'         => true,
                                    'updateTime'         => 1560976328,
                                ],
                            ],
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->listDevices(false);

        static::assertArrayHasKey('ZWAYSession', $history[0]['request']->getHeaders());
    }

    public function testStoresTokenOnLogin(): void
    {
        $token = '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa';
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();
        Storage::shouldReceive('put')
            ->withArgs(function (...$args) use ($token) {
                $isProperFile = 'zwave_token' === $args[0];
                $correctToken = $token === decrypt($args[1]);

                return $isProperFile && $correctToken;
            })
            ->once()
            ->andReturnTrue();

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'id'                        => 1,
                            'role'                      => 1,
                            'login'                     => 'aLogin',
                            'name'                      => 'Marshall A. Davis',
                            'lang'                      => 'en',
                            'color'                     => '#dddddd',
                            'dashboard'                 => [],
                            'interval'                  => 2000,
                            'rooms'                     => [
                                0,
                            ],
                            'expert_view'               => true,
                            'hide_all_device_events'    => false,
                            'hide_system_events'        => false,
                            'hide_single_device_events' => [],
                            'night_mode'                => true,
                            'email'                     => 'test@exposuresoftware.com',
                            'sid'                       => $token,
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ]
        ));

        $zwave->login();
    }

    public function testCanOptOutOfTokenStorage(): void
    {
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();
        Storage::shouldReceive('put')->times(0);

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'id'                        => 1,
                            'role'                      => 1,
                            'login'                     => 'aLogin',
                            'name'                      => 'Marshall A. Davis',
                            'lang'                      => 'en',
                            'color'                     => '#dddddd',
                            'dashboard'                 => [],
                            'interval'                  => 2000,
                            'rooms'                     => [
                                0,
                            ],
                            'expert_view'               => true,
                            'hide_all_device_events'    => false,
                            'hide_system_events'        => false,
                            'hide_single_device_events' => [],
                            'night_mode'                => true,
                            'email'                     => 'test@exposuresoftware.com',
                            'sid'                       => '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa',
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ]
        ));

        $zwave->login(null, null, false);
    }

    public function testUsesConfiguredCredentials(): void
    {
        $history = [];
        $token = '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa';
        $password = 'aPassword';
        $user = 'aUser';
        $this->app['config']->set('laravelwave.user', $user);
        $this->app['config']->set('laravelwave.password', $password);
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();
        Storage::shouldReceive('put')
            ->withArgs(function (...$args) use ($token) {
                $isProperFile = 'zwave_token' === $args[0];
                $correctToken = $token === decrypt($args[1]);

                return $isProperFile && $correctToken;
            })
            ->once()
            ->andReturnTrue();

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'id'                        => 1,
                            'role'                      => 1,
                            'login'                     => 'aLogin',
                            'name'                      => 'Marshall A. Davis',
                            'lang'                      => 'en',
                            'color'                     => '#dddddd',
                            'dashboard'                 => [],
                            'interval'                  => 2000,
                            'rooms'                     => [
                                0,
                            ],
                            'expert_view'               => true,
                            'hide_all_device_events'    => false,
                            'hide_system_events'        => false,
                            'hide_single_device_events' => [],
                            'night_mode'                => true,
                            'email'                     => 'test@exposuresoftware.com',
                            'sid'                       => $token,
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->login();

        $request = \GuzzleHttp\json_decode((string) $history[0]['request']->getBody());
        static::assertSame($user, $request->login);
        static::assertSame($password, $request->password);
    }

    public function testUsesGivenCredentials(): void
    {
        $history = [];
        $token = '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa';
        $password = 'anotherPassword';
        $user = 'anotherUser';
        $this->app['config']->set('laravelwave.user', 'aUser');
        $this->app['config']->set('laravelwave.password', 'aPassword');
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnFalse();
        Storage::shouldReceive('put')
            ->withArgs(function (...$args) use ($token) {
                $isProperFile = 'zwave_token' === $args[0];
                $correctToken = $token === decrypt($args[1]);

                return $isProperFile && $correctToken;
            })
            ->once()
            ->andReturnTrue();

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data' => (object) [
                            'id'                        => 1,
                            'role'                      => 1,
                            'login'                     => 'aLogin',
                            'name'                      => 'Marshall A. Davis',
                            'lang'                      => 'en',
                            'color'                     => '#dddddd',
                            'dashboard'                 => [],
                            'interval'                  => 2000,
                            'rooms'                     => [
                                0,
                            ],
                            'expert_view'               => true,
                            'hide_all_device_events'    => false,
                            'hide_system_events'        => false,
                            'hide_single_device_events' => [],
                            'night_mode'                => true,
                            'email'                     => 'test@exposuresoftware.com',
                            'sid'                       => $token,
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->login($user, $password);

        $request = \GuzzleHttp\json_decode((string) $history[0]['request']->getBody());
        static::assertSame($user, $request->login);
        static::assertSame($password, $request->password);
    }

    public function testBuildsUrlFromConfiguration(): void
    {
        $host = 'http://localhost';
        $port = 8083;
        $this->app['config']->set('laravelwave.host', $host);
        $this->app['config']->set('laravelwave.port', $port);

        $zwave = $this->app->make(Zwave::class);
        $reflection = new ReflectionClass($zwave);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        /** @var Uri $uri */
        $uri = $clientProperty->getValue($zwave)->getConfig('base_uri');

        static::assertSame($host, "{$uri->getScheme()}://{$uri->getHost()}");
        static::assertSame($port, $uri->getPort());
        static::assertSame(Zwave::BASE_PATH, $uri->getPath());
    }

    public function testListsDevices(): void
    {
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $deviceOne = (object) [
            'creationTime'       => 1560912400,
            'creatorId'          => 12,
            'customIcons'        => (object) [],
            'deviceType'         => 'toggleButton',
            'h'                  => -1891043069,
            'hasHistory'         => false,
            'id'                 => 'MailNotifier_12',
            'location'           => 0,
            'metrics'            => (object) [
                'level'   => 'on',
                'title'   => 'Send Email Notification',
                'icon'    => '/ZAutomation/api/v1/load/modulemedia/MailNotifier/icon.png',
                'message' => '',
            ],
            'order'              => (object) [
                'rooms'     => 0,
                'elements'  => 0,
                'dashboard' => 0,
            ],
            'permanently_hidden' => false,
            'probeType'          => 'notification_email',
            'tags'               => [
                'testing',
                'mocked',
            ],
            'visibility'         => true,
            'updateTime'         => 1560976328,
        ];
        $deviceTwo = (object) [
            'creationTime'       => 1560976328,
            'creatorId'          => 5,
            'customIcons'        => (object) [],
            'deviceType'         => 'text',
            'h'                  => -1261400328,
            'hasHistory'         => false,
            'id'                 => 'InfoWidget_5_Int',
            'location'           => 0,
            'metrics'            => (object) [
                'title' => 'Dear Expert User',
                'text'  => '<div style="text-align: center;">If you still want to use ExpertUI please go, after you are successfully logged in, to <br><strong> Menu > Devices > Manage with ExpertUI </strong> <br> or call <br><strong> http =>//MYRASP =>8083/expert </strong><br> in your browser. <br> <br>You could hide or remove this widget in menu <br><strong>Apps > Active Tab</strong>. </div>',
                'icon'  => 'app/img/logo-z-wave-z-only.png',
            ],
            'order'              => (object) [
                'rooms'     => 0,
                'elements'  => 0,
                'dashboard' => 0,
            ],
            'permanently_hidden' => false,
            'probeType'          => '',
            'tags'               => [],
            'visibility'         => true,
            'updateTime'         => 1560976328,
        ];

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data'    => (object) [
                            'structureChanged' => false,
                            'updateTime'       => 1561091908,
                            'devices'          => [
                                $deviceOne,
                                $deviceTwo,
                            ],
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        /** @var Collection $devices */
        $devices = $zwave->listDevices(false);

        $devices->each(function (Device $device) use ($deviceOne, $deviceTwo) {
            collect($device->getAttributes())
                ->reject(function ($value, string $key): bool {
                    return 'permanently_hidden' === $key;
                })
                ->each(function ($value, string $attribute) use ($deviceOne, $deviceTwo) {
                    $attribute = Str::camel($attribute);
                    $this->assertTrue(($deviceOne->{$attribute} ?? null) === $value || ($deviceTwo->{$attribute} ?? null) === $value);
                });
        });
    }

    public function testSavesDevices(): void
    {
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $deviceOne = (object) [
            'creationTime'       => 1560912400,
            'creatorId'          => 12,
            'customIcons'        => (object) [],
            'deviceType'         => 'toggleButton',
            'h'                  => -1891043069,
            'hasHistory'         => false,
            'id'                 => 'MailNotifier_12',
            'location'           => 0,
            'metrics'            => (object) [
                'level'   => 'on',
                'title'   => 'Send Email Notification',
                'icon'    => '/ZAutomation/api/v1/load/modulemedia/MailNotifier/icon.png',
                'message' => '',
            ],
            'nodeId'             => 2,
            'order'              => (object) [
                'rooms'     => 0,
                'elements'  => 0,
                'dashboard' => 0,
            ],
            'permanently_hidden' => false,
            'probeType'          => 'notification_email',
            'tags'               => [
                'testing',
                'mocked',
            ],
            'visibility'         => true,
            'updateTime'         => 1560976328,
        ];
        $deviceTwo = (object) [
            'creationTime'       => 1560976328,
            'creatorId'          => 5,
            'customIcons'        => (object) [],
            'deviceType'         => 'text',
            'h'                  => -1261400328,
            'hasHistory'         => false,
            'id'                 => 'InfoWidget_5_Int',
            'location'           => 0,
            'metrics'            => (object) [
                'title' => 'Dear Expert User',
                'text'  => '<div style="text-align: center;">If you still want to use ExpertUI please go, after you are successfully logged in, to <br><strong> Menu > Devices > Manage with ExpertUI </strong> <br> or call <br><strong> http =>//MYRASP =>8083/expert </strong><br> in your browser. <br> <br>You could hide or remove this widget in menu <br><strong>Apps > Active Tab</strong>. </div>',
                'icon'  => 'app/img/logo-z-wave-z-only.png',
            ],
            'nodeId'             => 3,
            'order'              => (object) [
                'rooms'     => 0,
                'elements'  => 0,
                'dashboard' => 0,
            ],
            'permanently_hidden' => false,
            'probeType'          => '',
            'tags'               => [],
            'visibility'         => true,
            'updateTime'         => 1560976328,
        ];

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data'    => (object) [
                            'structureChanged' => false,
                            'updateTime'       => 1561091908,
                            'devices'          => [
                                $deviceOne,
                                $deviceTwo,
                            ],
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->listDevices();

        static::assertCount(2, Device::all());
    }

    public function testDevicesHaveCorrectAttributes(): void
    {
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $deviceOne = [
            'creationTime'       => 1560912400,
            'creatorId'          => 12,
            'customIcons'        => (object) [],
            'deviceType'         => 'toggleButton',
            'h'                  => -1891043069,
            'hasHistory'         => false,
            'id'                 => 'MailNotifier_12',
            'location'           => 0,
            'metrics'            => (object) [
                'level'   => 'on',
                'title'   => 'Send Email Notification',
                'icon'    => '/ZAutomation/api/v1/load/modulemedia/MailNotifier/icon.png',
                'message' => '',
            ],
            'nodeId'             => 2,
            'order'              => (object) [
                'rooms'     => 0,
                'elements'  => 0,
                'dashboard' => 0,
            ],
            'permanently_hidden' => false,
            'probeType'          => 'notification_email',
            'tags'               => [
                'testing',
                'mocked',
            ],
            'visibility'         => true,
            'updateTime'         => 1560976328,
        ];

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data'    => (object) [
                            'structureChanged' => false,
                            'updateTime'       => 1561091908,
                            'devices'          => [
                                $deviceOne,
                            ],
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->listDevices();
        /** @var Device $device */
        $device = Device::first();

        $this->assertInstanceOf(Device::class, $device);
        foreach ($device->getAttributes() as $attribute => $value) {
            $this->assertEquals(
                $value,
                $device->{$attribute},
                "For attribute {$attribute}."
            );
        }
    }

    public function testDeviceSavingLogsErrors(): void
    {
        Log::shouldReceive('debug');
        Log::shouldReceive('error')->once();

        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $deviceOne = [
            'creationTime'       => null,
            'creatorId'          => 12,
            'customIcons'        => (object) [],
            'deviceType'         => 'toggleButton',
            'h'                  => -1891043069,
            'hasHistory'         => false,
            'id'                 => 'MailNotifier_12',
            'location'           => 0,
            'metrics'            => (object) [
                'level'   => 'on',
                'title'   => 'Send Email Notification',
                'icon'    => '/ZAutomation/api/v1/load/modulemedia/MailNotifier/icon.png',
                'message' => '',
            ],
            'nodeId'             => 2,
            'order'              => (object) [
                'rooms'     => 0,
                'elements'  => 0,
                'dashboard' => 0,
            ],
            'permanently_hidden' => false,
            'probeType'          => 'notification_email',
            'tags'               => [
                'testing',
                'mocked',
            ],
            'visibility'         => true,
            'updateTime'         => 1560976328,
        ];

        $zwave = new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'data'    => (object) [
                            'structureChanged' => false,
                            'updateTime'       => 1561091908,
                            'devices'          => [
                                $deviceOne,
                            ],
                        ],
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                    ])
                ),
            ],
            $history
        ));

        $zwave->listDevices();
    }

    public function testUpdateDevice(): void
    {
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $metrics = (object) [
            'probeTitle'       => 'Luminiscence',
            'scaleTitle'       => 'Lux',
            'level'            => 130,
            'icon'             => 'luminosity',
            'title'            => 'Luminiscence - Living Room',
            'modificationTime' => 1464779507,
            'lastLevel'        => 130,
        ];
        $device = (new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'code'    => 200,
                        'message' => '200 OK',
                        'error'   => null,
                        'data'    => (object) [
                            'creationTime'       => 1464779507,
                            'creatorId'          => 34,
                            'deviceType'         => 'sensorMultilevel',
                            'h'                  => 1303283138,
                            'hasHistory'         => false,
                            'id'                 => 'ZWayVDev_zway_2-0-49-3',
                            'location'           => 0,
                            'metrics'            => $metrics,
                            'permanently_hidden' => false,
                            'probeType'          => 'luminosity',
                            'tags'               => [],
                            'visibility'         => true,
                            'updateTime'         => 1464779507,
                        ],
                    ])
                ),
            ],
            $history
        )))->update(factory(Metric::class)->create()->device);

        static::assertSame("v1/devices/{$device->id}", $history[0]['request']->getUri()->getPath());
        static::assertSame($metrics->probeTitle, $device->metrics->probe_title);
        static::assertSame($metrics->scaleTitle, $device->metrics->scale_title);
        static::assertSame($metrics->level, $device->metrics->level);
        static::assertSame($metrics->icon, $device->metrics->icon);
        static::assertSame($metrics->title, $device->metrics->title);
    }

    public function testReturnsFalseIfNoCommands(): void
    {
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $device = factory(Device::class)->create();

        static::assertFalse((new Zwave($this->getMockClient()))->command($device, 'color', [1, 2, 3]));
    }

    public function testsRunsCommands(): void
    {
        $endpoint = 'api/v1/test';
        $history = [];
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')->with('zwave_token')->andReturnTrue();
        Storage::shouldReceive('get')->with('zwave_token')->andReturn(encrypt('token'));
        $device = factory(Device::class)->create([
            'device_type' => 'switchBinary',
        ]);
        App::bind(SwitchBinary::class, function () use ($endpoint) {
            $mockBasics = Mockery::mock(SwitchBinary::class);
            $mockBasics->shouldReceive('color')->with(1, 2, 3)->once()->andReturn($endpoint);

            return $mockBasics;
        });

        static::assertTrue((new Zwave($this->getMockClient(
            [
                new Response(
                    200,
                    [],
                    \GuzzleHttp\json_encode([
                        'code' => 200,
                    ])
                ),
            ],
            $history
        )))
            ->command($device, 'color', [1, 2, 3]));
        static::assertSame($endpoint, $history[0]['request']->getUri()->getPath());
    }

    public function storageProvider(): array
    {
        return [
            [
                false,
                false,
            ],
            [
                true,
                true,
            ],
        ];
    }
}
