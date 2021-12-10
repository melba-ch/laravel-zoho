<?php

namespace MelbaCh\LaravelZoho;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use MelbaCh\LaravelZoho\Clients\ZohoHttp;

class ZohoPendingRequest extends PendingRequest
{
    public static function fromPendingRequest(PendingRequest $pendingRequest): ZohoPendingRequest
    {
        $instance = new static(app(ZohoHttp::class));
        $instance->loadFromPendingRequest($pendingRequest);
        return $instance;
    }

    public function loadFromPendingRequest(PendingRequest $pendingRequest)
    {
        $attributes = get_object_vars($pendingRequest);
        foreach ($attributes as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    public function send(string $method, string $url, array $options = [])
    {
        $response = parent::send($method, $url, $options);
        if ($response instanceof Response) {
            return ZohoResponse::fromResponse($response);
        }
        return $response;
    }

}