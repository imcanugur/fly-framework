<?php

declare(strict_types=1);

namespace Fly\View\Exceptions;

class ViewException extends \Exception {}

class ViewNotFoundException extends ViewException
{
    public function __construct(string $view, string $path)
    {
        parent::__construct("View [{$view}] not found at path: [{$path}]");
    }
}

class CompilationException extends ViewException
{
    public function __construct(string $message, string $template = null)
    {
        $msg = "Template Compilation Error: {$message}";
        if ($template) $msg .= " in [{$template}]";
        parent::__construct($msg);
    }
}

class ContractViolationException extends ViewException
{
    public function __construct(string $message)
    {
        parent::__construct("View Blueprint Violation: {$message}");
    }
}
