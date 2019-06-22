<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Zwave;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class Response
{
    /** @var array */
    protected $data;
    /** @var string */
    protected $message;
    /** @var int */
    protected $code;
    /** @var string */
    protected $error;
    
    public function __construct(ResponseInterface $response)
    {
        try {
            $decoded = \GuzzleHttp\json_decode((string) $response->getBody());

            $this->code = $decoded->code ?? null;
            $this->data = $decoded->data ?? null;
            $this->message = $decoded->message ?? null;
            $this->error = $decoded->error ?? null;
        } catch (InvalidArgumentException $json_error) {
            $this->error = "Failed to parse response due to {$json_error->getMessage()}";
        }
    }

    public function __get($name)
    {
        return $this->data->$name ?? null;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getCode()
    {
        return $this->code;
    }
}
