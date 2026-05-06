<?php

declare(strict_types=1);

namespace Fly\View\Engine;

class Token
{
    public const T_TEXT = 'text';
    public const T_ECHO = 'echo';
    public const T_RAW_ECHO = 'raw_echo';
    public const T_COMMENT = 'comment';
    public const T_DIRECTIVE = 'directive';
    public const T_COMPONENT_OPEN = 'component_open';
    public const T_COMPONENT_CLOSE = 'component_close';
    public const T_COMPONENT_SELF_CLOSING = 'component_self_closing';

    public function __construct(
        public string $type,
        public string $content,
        public int $line,
        public array $attributes = []
    ) {}
}
