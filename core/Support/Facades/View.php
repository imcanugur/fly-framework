<?php

declare(strict_types=1);

namespace Fly\Support\Facades;

class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}
