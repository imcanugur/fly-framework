<?php

declare(strict_types=1);

namespace Fly\Events;

interface ListenerInterface
{
    /**
     * Handle the event.
     *
     * @param mixed $event The event object or payload
     * @param string $eventName The name of the event being fired
     * @return void
     */
    public function handle(mixed $event, string $eventName): void;
}
