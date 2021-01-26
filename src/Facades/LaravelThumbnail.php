<?php

namespace HungNM\LaravelThumbnail\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelThumbnail extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'thumbnail';
    }
}