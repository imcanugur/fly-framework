<?php

declare(strict_types=1);

namespace Fly\Session\Handlers;

use Fly\Session\SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * The path where the session files are stored.
     */
    protected string $path;

    /**
     * The number of minutes the session should be valid.
     */
    protected int $minutes;

    /**
     * Create a new file session handler instance.
     */
    public function __construct(string $path, int $minutes)
    {
        $this->path = $path;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id): string
    {
        $path = $this->path . '/' . $id;

        if (file_exists($path)) {
            if (filemtime($path) >= time() - ($this->minutes * 60)) {
                return file_get_contents($path);
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        return (bool) file_put_contents($this->path . '/' . $id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        $path = $this->path . '/' . $id;

        if (file_exists($path)) {
            unlink($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $max_lifetime): int|bool
    {
        // Garbage collection logic
        return true;
    }
}
