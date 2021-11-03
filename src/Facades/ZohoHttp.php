<?php

namespace MelbaCh\LaravelZoho\Facades;

use Illuminate\Support\Facades\Facade;

class ZohoHttp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MelbaCh\LaravelZoho\Clients\ZohoHttp::class;
    }
}