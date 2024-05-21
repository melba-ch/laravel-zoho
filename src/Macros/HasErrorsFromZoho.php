<?php

namespace MelbaCh\LaravelZoho\Macros;

use Illuminate\Http\Response;

/**
 *
 * @mixin Response
 * @return bool
 */
class HasErrorsFromZoho
{
    public function __invoke()
    {
        return function (): bool {
            return $this->status() >= 400 || count($this->errorsFromZoho()) > 0;
        };
    }
}
