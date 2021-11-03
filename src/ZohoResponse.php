<?php

namespace MelbaCh\LaravelZoho;

use \Illuminate\Http\Client\Response;

class ZohoResponse extends Response
{
    public static function fromResponse(Response $response): ZohoResponse
    {
        return new static($response->response);
    }

    public function hasErrors(): bool
    {
        return $this->status() >= 400 || count($this->errors());
    }

    public function errors(): array
    {
        if ($this->status() >= 400) {
            return $this->json() ?? [];
        }

        return collect($this->json())
            ->flatten(1)
            ->filter(function ($value)
            {
                if (is_array($value) && array_key_exists('status', $value)) {
                    return $value['status'] === 'error';
                }
                return null;
            })
            ->values()
            ->toArray();
    }

}