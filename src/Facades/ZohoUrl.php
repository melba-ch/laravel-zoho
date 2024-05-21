<?php

namespace MelbaCh\LaravelZoho\Facades;

use Illuminate\Support\Facades\Facade;

class ZohoUrl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MelbaCh\LaravelZoho\Clients\ZohoUrlFactory::class;
    }
}
