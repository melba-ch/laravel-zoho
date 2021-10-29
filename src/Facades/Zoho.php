<?php

namespace MelbaCh\LaravelZoho\Facades;

use Illuminate\Support\Facades\Facade;

class Zoho extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MelbaCh\LaravelZoho\Zoho::class;
    }
}