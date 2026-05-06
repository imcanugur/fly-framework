<?php

declare(strict_types=1);

namespace Fly\View;

class View
{
    protected Factory $factory;
    protected string $view;
    protected array $data;

    public function __construct(Factory $factory, string $view, array $data = [])
    {
        $this->factory = $factory;
        $this->view = $view;
        $this->data = $data;
    }

    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->view;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function render(): string
    {
        $this->factory->callComposers($this);
        return $this->factory->render($this->view, $this->data);
    }

    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
