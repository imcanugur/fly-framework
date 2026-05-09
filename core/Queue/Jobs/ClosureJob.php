<?php

declare(strict_types=1);

namespace Fly\Queue\Jobs;

use Closure;
use ReflectionFunction;

class ClosureJob
{
    /**
     * The serialized closure.
     */
    protected string $closure;

    /**
     * Create a new closure job instance.
     */
    public function __construct(Closure $closure)
    {
        $this->closure = base64_encode(serialize($closure));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $closure = unserialize(base64_decode($this->closure));
        $closure();
    }
}
