<?php

declare(strict_types=1);

namespace Fly\Cache\Events;

abstract class CacheEvent
{
    public function __construct(public string $key, public array $tags = []) {}
}

class CacheHit extends CacheEvent 
{
    public function __construct(string $key, public mixed $value, array $tags = [])
    {
        parent::__construct($key, $tags);
    }
}

class CacheMissed extends CacheEvent {}

class KeyWritten extends CacheEvent
{
    public function __construct(string $key, public mixed $value, public ?int $seconds = null, array $tags = [])
    {
        parent::__construct($key, $tags);
    }
}

class KeyForgotten extends CacheEvent {}
