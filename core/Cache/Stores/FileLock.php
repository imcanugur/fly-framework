<?php

declare(strict_types=1);

namespace Fly\Cache\Stores;

class FileLock implements LockInterface
{
    protected string $path;
    protected string $owner;
    protected int $seconds;

    public function __construct(string $path, int $seconds, string $owner = '')
    {
        $this->path = $path;
        $this->seconds = $seconds;
        $this->owner = $owner ?: str_replace('.', '', uniqid('', true));
    }

    public function get(?callable $callback = null): bool
    {
        if ($this->acquire()) {
            if ($callback) {
                try {
                    return (bool) $callback();
                } finally {
                    $this->release();
                }
            }
            return true;
        }

        return false;
    }

    protected function acquire(): bool
    {
        if (!file_exists($this->path)) {
            return (bool) file_put_contents($this->path, $this->owner . ':' . (time() + $this->seconds));
        }

        $content = file_get_contents($this->path);
        [$owner, $expire] = explode(':', $content);

        if (time() >= (int) $expire) {
            $this->forceRelease();
            return $this->acquire();
        }

        return $owner === $this->owner;
    }

    public function block(int $seconds, ?callable $callback = null): bool
    {
        $starting = time();

        while (!$this->get($callback)) {
            if (time() - $starting >= $seconds) {
                return false;
            }

            usleep(250000);
        }

        return true;
    }

    public function release(): bool
    {
        if ($this->isOwnedByCurrentProcess()) {
            return (bool) @unlink($this->path);
        }

        return false;
    }

    protected function isOwnedByCurrentProcess(): bool
    {
        if (!file_exists($this->path)) {
            return true;
        }

        return explode(':', file_get_contents($this->path))[0] === $this->owner;
    }

    public function owner(): string
    {
        return $this->owner;
    }

    public function forceRelease(): void
    {
        @unlink($this->path);
    }
}
