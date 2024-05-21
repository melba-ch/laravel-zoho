<?php

namespace MelbaCh\LaravelZoho\Macros;

use Illuminate\Http\Response;

/**
 *
 * @mixin Response
 * @return array
 */
class ErrorsFromZoho
{
    public function __invoke()
    {
        return function (): array {
            if ($this->status() >= 400) {
                return $this->json() ?? [];
            }

            return collect($this->json())
                ->flatten(1)
                ->filter(function ($value) {
                    if (is_array($value) && array_key_exists('status', $value)) {
                        return $value['status'] === 'error';
                    }

                    return null;
                })
                ->values()
                ->toArray();
        };
    }
}
