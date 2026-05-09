<?php

declare(strict_types=1);

namespace Fly\Queue;

use Fly\Support\Str;

class PendingBatch
{
    protected array $jobs;
    protected array $thenCallbacks = [];
    protected array $catchCallbacks = [];
    protected array $finallyCallbacks = [];

    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    public function then(callable $callback): self
    {
        $this->thenCallbacks[] = $callback;
        return $this;
    }

    public function finally(callable $callback): self
    {
        $this->finallyCallbacks[] = $callback;
        return $this;
    }

    public function dispatch(): void
    {
        $batchId = (string) Str::uuid();
        $totalJobs = count($this->jobs);

        app('db')->table('job_batches')->insert([
            'id' => $batchId,
            'total_jobs' => $totalJobs,
            'pending_jobs' => $totalJobs,
            'failed_jobs' => 0,
            'created_at' => time(),
        ]);

        foreach ($this->jobs as $job) {
            if (method_exists($job, 'withBatchId')) {
                $job->withBatchId($batchId);
            }
            app('queue')->push($job);
        }
    }
}
