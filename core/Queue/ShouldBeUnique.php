<?php

declare(strict_types=1);

namespace Fly\Queue;

interface ShouldBeUnique
{
    /**
     * Get the unique ID for the job.
     * 
     * @return string|null
     */
    public function uniqueId(): ?string;

    /**
     * Get the number of seconds the job should be considered unique.
     * 
     * @return int
     */
    public function uniqueFor(): int;
}
