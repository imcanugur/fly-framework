<?php

declare(strict_types=1);

namespace Fly\Queue\Events;

use Fly\Queue\JobInterface;

abstract class QueueEvent
{
    public function __construct(public JobInterface $job) {}
}

class JobProcessing extends QueueEvent {}
class JobProcessed extends QueueEvent {}

class JobFailed extends QueueEvent
{
    public function __construct(JobInterface $job, public \Throwable $exception)
    {
        parent::__construct($job);
    }
}
