<?php

namespace Marvin\Ask\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Marvin\Ask\Ask
 */
class Ask extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Marvin\Ask\Ask::class;
    }
}
