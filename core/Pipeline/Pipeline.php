<?php

declare(strict_types=1);

namespace Fly\Pipeline;

use Closure;
use Fly\Container\Container;

/**
 * The Onion Architecture Pipeline.
 *
 * Passes an object (e.g., Request) through a series of "pipes" (e.g., Middleware)
 * before it hits the final destination (e.g., Controller Action).
 */
class Pipeline
{
    /**
     * The object being passed through the pipeline.
     */
    protected mixed $passable;

    /**
     * The array of pipes.
     *
     * @var list<mixed>
     */
    protected array $pipes = [];

    /**
     * The dependency container.
     */
    protected ?Container $container = null;

    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     */
    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the array of pipes.
     */
    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );

        return $pipeline($this->passable);
    }

    /**
     * Get a Closure that represents a single slice of the onion.
     */
    protected function carry(): Closure
    {
        return function (Closure $stack, mixed $pipe): Closure {
            return function (mixed $passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->container ? $this->container->make($pipe) : new $pipe();
                }

                // If it's a callable array or closure, call it directly
                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                // Otherwise, assume it's an object with a 'handle' method
                return $pipe->handle($passable, $stack);
            };
        };
    }
}
