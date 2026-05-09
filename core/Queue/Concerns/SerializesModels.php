<?php

declare(strict_types=1);

namespace Fly\Queue\Concerns;

use Fly\Database\ORM\Model;
use ReflectionClass;
use ReflectionProperty;

trait SerializesModels
{
    /**
     * Prepare the instance for serialization.
     * 
     * We convert all Model instances to their IDs so we don't 
     * serialize stale database data into the queue.
     */
    public function __sleep(): array
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);

            if ($value instanceof Model) {
                $property->setValue($this, [
                    '__fly_model_class' => get_class($value),
                    '__fly_model_id'    => $value->getKey(),
                ]);
            }
        }

        return array_map(fn($p) => $p->getName(), $properties);
    }

    /**
     * Restore the model after unserialization.
     */
    public function __wakeup(): void
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);

            if (is_array($value) && isset($value['__fly_model_class'])) {
                $class = $value['__fly_model_class'];
                $id = $value['__fly_model_id'];
                
                $property->setValue($this, (new $class())->find($id));
            }
        }
    }
}
