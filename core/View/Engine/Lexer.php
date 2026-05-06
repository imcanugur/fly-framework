<?php

declare(strict_types=1);

namespace Fly\View\Engine;

class Lexer
{
    protected string $content;
    protected int $cursor = 0;
    protected int $line = 1;
    protected array $tokens = [];

    // Patterns
    protected const PATTERN_ECHO = '/^\{\{\s*(.*?)\s*\}\}/s';
    protected const PATTERN_RAW_ECHO = '/^\{\!\!\s*(.*?)\s*\!\!\}/s';
    protected const PATTERN_COMMENT = '/^\{\{--.*?--\}\}/s';
    protected const PATTERN_DIRECTIVE = '/^@(\w+)(\s*\( ( (?>[^()]+) | (?2) )* \))?/x';
    protected const PATTERN_COMPONENT_SELF = '/^<(?:fly|f):([a-zA-Z0-9_\-]+)\s*(.*?)\s*\/>/s';
    protected const PATTERN_COMPONENT_OPEN = '/^<(?:fly|f):([a-zA-Z0-9_\-]+)\s*(.*?)>/s';
    protected const PATTERN_COMPONENT_CLOSE = '/^<\/(?:fly|f):([a-zA-Z0-9_\-]+)>/';

    public function tokenize(string $content): array
    {
        $this->content = str_replace("\r\n", "\n", $content);
        $this->cursor = 0;
        $this->line = 1;
        $this->tokens = [];

        while ($this->cursor < strlen($this->content)) {
            if ($this->match(self::PATTERN_RAW_ECHO, Token::T_RAW_ECHO)) continue;
            if ($this->match(self::PATTERN_COMMENT, Token::T_COMMENT)) continue;
            if ($this->match(self::PATTERN_ECHO, Token::T_ECHO)) continue;
            if ($this->match(self::PATTERN_DIRECTIVE, Token::T_DIRECTIVE)) continue;
            if ($this->match(self::PATTERN_COMPONENT_SELF, Token::T_COMPONENT_SELF_CLOSING)) continue;
            if ($this->match(self::PATTERN_COMPONENT_OPEN, Token::T_COMPONENT_OPEN)) continue;
            if ($this->match(self::PATTERN_COMPONENT_CLOSE, Token::T_COMPONENT_CLOSE)) continue;

            // Otherwise, it's text
            $this->consumeText();
        }

        return $this->tokens;
    }

    protected function match(string $pattern, string $type): bool
    {
        $subject = substr($this->content, $this->cursor);
        if (preg_match($pattern, $subject, $matches)) {
            $this->tokens[] = new Token($type, $matches[0], $this->line, $matches);
            $this->moveCursor($matches[0]);
            return true;
        }
        return false;
    }

    protected function consumeText(): void
    {
        $subject = substr($this->content, $this->cursor);
        // Find next special character
        $next = strpbrk($subject, '{@<');
        
        if ($next === false) {
            $text = $subject;
        } else {
            $pos = strpos($subject, $next);
            if ($pos === 0) {
                // If the special char didn't match a pattern, it's just text
                $text = substr($subject, 0, 1);
            } else {
                $text = substr($subject, 0, $pos);
            }
        }

        $this->tokens[] = new Token(Token::T_TEXT, $text, $this->line);
        $this->moveCursor($text);
    }

    protected function moveCursor(string $text): void
    {
        $this->line += substr_count($text, "\n");
        $this->cursor += strlen($text);
    }
}
