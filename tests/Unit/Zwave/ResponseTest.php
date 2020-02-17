<?php
/**
 * ExposureSoftware
 */

namespace Tests\Unit\Zwave;

use ExposureSoftware\LaravelWave\Zwave\Response as ZwaveResponse;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Tests\TestCase;

class ResponseTest extends TestCase
{
    public function testInstantiates(): void
    {
        static::assertInstanceOf(
            ZwaveResponse::class,
            new ZwaveResponse(new HttpResponse(
                200,
                [],
                \GuzzleHttp\json_encode([
                    'code'    => 200,
                    'data'    => [],
                    'message' => 'Mock Response OK',
                    'error'   => null,
                ])
            ))
        );
    }

    public function testHandlesJsonError(): void
    {
        $response = new ZwaveResponse(new HttpResponse(
            200,
            [],
            null
        ));

        static::assertInstanceOf(ZwaveResponse::class, $response);
        static::assertStringEndsWith(json_last_error_msg(), $response->getError());
    }

    public function testHandlesMissingAttribute(): void
    {
        $response = new ZwaveResponse(new HttpResponse(
            200,
            [],
            \GuzzleHttp\json_encode([
                'data'    => [],
                'message' => 'Mock Response OK',
                'error'   => null,
            ])
        ));

        static::assertInstanceOf(ZwaveResponse::class, $response);
        static::assertNull($response->getCode());
    }

    public function testRetrievesDataProperties(): void
    {
        $data = [
            'id'        => 1,
            'role'      => 1,
            'login'     => 'aLogin',
            'name'      => 'Marshall A. Davis',
            'lang'      => 'en',
            'color'     => '#dddddd',
            'dashboard' => [],
            'interval'  => 2000,
            'rooms'     => [
                0,
            ],
            'expert_view'               => true,
            'hide_all_device_events'    => false,
            'hide_system_events'        => false,
            'hide_single_device_events' => [],
            'night_mode'                => true,
            'email'                     => 'test@exposuresoftware.com',
            'sid'                       => '63d8f826-9727-ac3f-60cf-a4ca9cbf7faa',
        ];
        $response = new ZwaveResponse(new HttpResponse(
            200,
            [],
            \GuzzleHttp\json_encode([
                'code'    => 200,
                'data'    => $data,
                'message' => 'Mock Response OK',
                'error'   => null,
            ])
        ));

        static::assertInstanceOf(ZwaveResponse::class, $response);

        foreach ($data as $attribute => $value) {
            if (!\is_array($value)) {
                static::assertSame($value, $response->{$attribute}, "Response attribute {$attribute} was not as expected.");
            }
        }
    }

    public function testHandlesNullData(): void
    {
        $response = new ZwaveResponse(new HttpResponse(
            200,
            [],
            \GuzzleHttp\json_encode([
                'code'    => 200,
                'data'    => null,
                'message' => 'Mock Response OK',
                'error'   => null,
            ])
        ));

        static::assertInstanceOf(ZwaveResponse::class, $response);
        static::assertNull($response->invalidProperty);
    }
}
