<?php
/**
 * ExposureSoftware
 */

namespace Tests\Unit\Zwave;

use Carbon\Carbon;
use ExposureSoftware\LaravelWave\Models\Device;
use ExposureSoftware\LaravelWave\Zwave\Zwave;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class FetchDeviceTest extends TestCase
{
    public function testErrorLoggingIn(): void
    {
        $this->app->bind(Zwave::class, function () {
            $mockZwave = Mockery::mock(Zwave::class);
            $mockZwave->shouldReceive('hasToken')->once()->andReturnFalse();
            $mockZwave->shouldReceive('login')->once()->andReturnFalse();

            return $mockZwave;
        });

        $this->artisan('zway:fetch-devices')->assertExitCode(1);
    }

    public function testFetchesDevices(): void
    {
        $this->app->bind(Zwave::class, static function () {
            /** @var Collection $devices */
            $devices = factory(Device::class, 5)->create([
                'created_at' => Carbon::now()->subDay(),
            ]);
            $devices = $devices->merge(factory(Device::class, 3)->create([
                'created_at' => Carbon::now()->addMinutes(5),
            ]));
            $mockZwave = Mockery::mock(Zwave::class);
            $mockZwave->shouldReceive('hasToken')->once()->andReturnTrue();
            $mockZwave->shouldReceive('listDevices')
                ->withNoArgs()
                ->andReturn($devices);

            return $mockZwave;
        });

        $this->artisan('zway:fetch-devices')
            ->expectsOutput('8 devices reported. 3 new devices added.')
            ->assertExitCode(0);
    }

    public function testUnauthorizedResponse(): void
    {
        $this->app->singleton(ClientInterface::class, static function () {
            $deviceOne = (object) [
                'creationTime' => Carbon::now()->addHour()->timestamp,
                'creatorId'    => 12,
                'customIcons'  => (object) [],
                'deviceType'   => 'toggleButton',
                'h'            => -1891043069,
                'hasHistory'   => false,
                'id'           => 'MailNotifier_12',
                'location'     => 0,
                'metrics'      => (object) [
                    'level'   => 'on',
                    'title'   => 'Send Email Notification',
                    'icon'    => '/ZAutomation/api/v1/load/modulemedia/MailNotifier/icon.png',
                    'message' => '',
                ],
                'order' => (object) [
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
                'visibility' => true,
                'updateTime' => 1560976328,
            ];
            $deviceTwo = (object) [
                'creationTime' => Carbon::now()->subHour()->timestamp,
                'creatorId'    => 5,
                'customIcons'  => (object) [],
                'deviceType'   => 'text',
                'h'            => -1261400328,
                'hasHistory'   => false,
                'id'           => 'InfoWidget_5_Int',
                'location'     => 0,
                'metrics'      => (object) [
                    'title' => 'Dear Expert User',
                    'text'  => '<div style="text-align: center;">If you still want to use ExpertUI please go, after you are successfully logged in, to <br><strong> Menu > Devices > Manage with ExpertUI </strong> <br> or call <br><strong> http =>//MYRASP =>8083/expert </strong><br> in your browser. <br> <br>You could hide or remove this widget in menu <br><strong>Apps > Active Tab</strong>. </div>',
                    'icon'  => 'app/img/logo-z-wave-z-only.png',
                ],
                'order' => (object) [
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

            return new Client([
                'handler' => HandlerStack::create(new MockHandler([
                    new Response(401),
                    new Response(
                        200,
                        [
                            'content-type' => 'application/json',
                        ],
                        json_encode([
                            'data' => [
                                'sid' => '123abc',
                            ],
                        ])
                    ),
                    new Response(
                        200,
                        [
                            'content-type' => 'application/json',
                        ],
                        json_encode([
                            'data' => [
                                'devices' => [
                                    $deviceOne,
                                    $deviceTwo,
                                ],
                            ],
                        ])
                    ),
                ])),
            ]);
        });

        $this->artisan('zway:fetch-devices')
            ->expectsOutput('2 devices reported. 0 new devices added.')
            ->assertExitCode(0);
    }
}
