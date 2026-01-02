<?php

namespace MadeInUA\LaravelDripFlow\Facades;

use Illuminate\Support\Facades\Facade;

class DripFlow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MadeInUA\LaravelDripFlow\Services\DripManager::class;
    }
}
