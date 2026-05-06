<?php

declare(strict_types=1);

namespace Fly\View;

use Fly\View\Contracts\CompilerInterface;

class Compiler implements CompilerInterface
{
    use Concerns\CompilesConditionals;
    use Concerns\CompilesLoops;
    use Concerns\CompilesLayouts;
    use Concerns\CompilesIncludes;

    protected array $customDirectives = [];

    protected Engine\Lexer $lexer;

    public function __construct()
    {
        $this->lexer = new Engine\Lexer();
    }

    public function compile(string $value): string
    {
        $tokens = $this->lexer->tokenize($value);
        $result = '';

        foreach ($tokens as $token) {
            $result .= $this->compileToken($token);
        }

        return $result;
    }

    protected function compileToken(Engine\Token $token): string
    {
        return match ($token->type) {
            Engine\Token::T_TEXT => $token->content,
            Engine\Token::T_ECHO => $this->compileEcho($token),
            Engine\Token::T_RAW_ECHO => $this->compileRawEcho($token),
            Engine\Token::T_COMMENT => '',
            Engine\Token::T_DIRECTIVE => $this->compileDirective($token),
            Engine\Token::T_COMPONENT_SELF_CLOSING => $this->compileComponentSelf($token),
            Engine\Token::T_COMPONENT_OPEN => $this->compileComponentOpen($token),
            Engine\Token::T_COMPONENT_CLOSE => $this->compileComponentClose($token),
            default => '',
        };
    }

    protected function compileEcho(Engine\Token $token): string
    {
        return "<?php echo htmlspecialchars((string) ({$token->attributes[1]}), ENT_QUOTES); ?>";
    }

    protected function compileRawEcho(Engine\Token $token): string
    {
        return "<?php echo {$token->attributes[1]}; ?>";
    }

    protected function compileDirective(Engine\Token $token): string
    {
        $name = $token->attributes[1];
        $arguments = $token->attributes[2] ?? '';

        if (isset($this->customDirectives[$name])) {
            return call_user_func($this->customDirectives[$name], $this->stripParentheses($arguments));
        }

        $method = 'compile' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->{$method}($this->stripParentheses($arguments));
        }

        return $token->content;
    }

    protected function compileComponentSelf(Engine\Token $token): string
    {
        $component = $token->attributes[1];
        $attributes = $this->parseAttributes($token->attributes[2]);
        return "<?php \$__env->startComponent('components.{$component}', {$attributes}); ?>\n<?php echo \$__env->renderComponent(); ?>";
    }

    protected function compileComponentOpen(Engine\Token $token): string
    {
        $component = $token->attributes[1];
        if ($component === 'slot') {
            preg_match('/name="([^"]+)"/', $token->attributes[2], $matches);
            return "<?php \$__env->slot('{$matches[1]}'); ?>";
        }
        $attributes = $this->parseAttributes($token->attributes[2]);
        return "<?php \$__env->startComponent('components.{$component}', {$attributes}); ?>";
    }

    protected function compileComponentClose(Engine\Token $token): string
    {
        if ($token->attributes[1] === 'slot') return "<?php \$__env->endSlot(); ?>";
        return "<?php echo \$__env->renderComponent(); ?>";
    }

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    protected function compileComments(string $value): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $value);
    }

    protected function compileEchos(string $value): string
    {
        $value = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/s', '<?php echo $1; ?>', $value);
        $value = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?php echo htmlspecialchars((string) ($1), ENT_QUOTES); ?>', $value);
        return $value;
    }

    protected function compileComponents(string $value): string
    {
        // Self closing tags <fly:alert type="error" />
        $value = preg_replace_callback('/<(?:fly|f):([a-zA-Z0-9_\-]+)\s*(.*?)\s*\/>/s', function ($matches) {
            $component = $matches[1];
            $attributes = $this->parseAttributes($matches[2]);
            return "<?php \$__env->startComponent('components.{$component}', {$attributes}); ?>\n<?php echo \$__env->renderComponent(); ?>";
        }, $value);

        // Opening tags <fly:alert type="error">
        $value = preg_replace_callback('/<(?:fly|f):([a-zA-Z0-9_\-]+)\s*(.*?)>/s', function ($matches) {
            $component = $matches[1];
            if ($component === 'slot') return $matches[0];
            $attributes = $this->parseAttributes($matches[2]);
            return "<?php \$__env->startComponent('components.{$component}', {$attributes}); ?>";
        }, $value);

        // Closing tags </fly:alert>
        $value = preg_replace_callback('/<\/(?:fly|f):([a-zA-Z0-9_\-]+)>/', function ($matches) {
            if ($matches[1] === 'slot') return $matches[0];
            return "<?php echo \$__env->renderComponent(); ?>";
        }, $value);
        
        // Slots
        $value = preg_replace('/<(?:fly|f):slot\s+name="([^"]+)"\s*>/', "<?php \$__env->slot('$1'); ?>", $value);
        $value = preg_replace('/<\/(?:fly|f):slot>/', "<?php \$__env->endSlot(); ?>", $value);

        return $value;
    }

    protected function parseAttributes(string $attributeString): string
    {
        if (trim($attributeString) === '') return '[]';
        $attributes = [];
        preg_match_all('/([a-zA-Z0-9_\-:]+)(?:="([^"]*)")?/', $attributeString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2] ?? 'true';
            if (str_starts_with($key, ':')) {
                $key = substr($key, 1);
                $attributes[] = "'$key' => $value";
            } else {
                $attributes[] = "'$key' => '$value'";
            }
        }
        return '[' . implode(', ', $attributes) . ']';
    }

    protected function compileStatements(string $value): string
    {
        return preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
            function ($match) {
                return $this->compileStatement($match);
            },
            $value
        );
    }

    protected function compileStatement(array $match): string
    {
        if (str_contains($match[1], '@')) {
            return $match[1] . ($match[2] ?? '') . ($match[3] ?? '');
        }

        $name = $match[1];
        $arguments = $match[3] ?? '';

        if (isset($this->customDirectives[$name])) {
            return call_user_func($this->customDirectives[$name], $this->stripParentheses($arguments));
        }

        $method = 'compile' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->{$method}($this->stripParentheses($arguments));
        }

        return $match[0];
    }

    protected function stripParentheses(string $expression): string
    {
        if (str_starts_with($expression, '(')) {
            return substr($expression, 1, -1);
        }
        return $expression;
    }

    // --- Core Directives ---

    protected function compilePhp(string $expression) { return $expression ? "<?php $expression; ?>" : '<?php '; }
    protected function compileEndphp() { return ' ?>'; }
    protected function compileFly(string $expression) { return '<?php '; }
    protected function compileEndfly() { return ' ?>'; }
    
    protected function compileProps(string $expression) {
        return "<?php \$attributes = \$attributes->merge($expression); extract(\$attributes->getIterator()->getArrayCopy()); ?>";
    }

    protected function compileOnce(string $expression) {
        return "<?php if (!\$__env->hasRenderedOnce($expression)): \$__env->markAsRenderedOnce($expression); ?>";
    }
    protected function compileEndonce() { return "<?php endif; ?>"; }

    protected function compileBlueprint(string $expression) {
        return "<?php \\Fly\\View\\Blueprint::validate(get_defined_vars(), $expression); ?>";
    }

    protected function compileTelemetry(string $expression) {
        return "<?php \\Fly\\View\\Engine\\Telemetry::start($expression); ?>";
    }

    protected function compileEndtelemetry(string $expression) {
        $arg = $expression ?: 'null';
        return "<?php \$__telemetry = \\Fly\\View\\Engine\\Telemetry::end($arg); echo '<!-- Telemetry: ' . json_encode(\$__telemetry) . ' -->'; ?>";
    }

    protected function compileCsrf() { return "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"'.csrf_token().'\">'; ?>"; }
    protected function compileMethod(string $expression) { return "<?php echo '<input type=\"hidden\" name=\"_method\" value=\"'.$expression.'\">'; ?>"; }
    protected function compileChecked(string $expression) { return "<?php echo ($expression) ? 'checked=\"checked\"' : ''; ?>"; }
    protected function compileSelected(string $expression) { return "<?php echo ($expression) ? 'selected=\"selected\"' : ''; ?>"; }
    protected function compileDisabled(string $expression) { return "<?php echo ($expression) ? 'disabled=\"disabled\"' : ''; ?>"; }
    protected function compileClass(string $expression) { 
        return "<?php echo 'class=\"' . \\Fly\\View\\ComponentAttributeBag::compileClass($expression) . '\"'; ?>"; 
    }

    protected function compileDd(string $expression) { return "<?php dd($expression); ?>"; }
    protected function compileDump(string $expression) { return "<?php dump($expression); ?>"; }
    protected function compileJson(string $expression) { return "<?php echo json_encode($expression); ?>"; }
    protected function compileInject(string $expression) {
        $segments = explode(',', $expression, 2);
        $variable = trim($segments[0], " '\"");
        $service = trim($segments[1]);
        return "<?php \${$variable} = \Fly\Container\Container::getInstance()->make($service); ?>";
    }

    protected function compileAuth() { return "<?php if (function_exists('auth') && auth()->check()): ?>"; }
    protected function compileEndauth() { return "<?php endif; ?>"; }
    protected function compileGuest() { return "<?php if (!function_exists('auth') || auth()->guest()): ?>"; }
    protected function compileEndguest() { return "<?php endif; ?>"; }
    protected function compileCan(string $expression) { return "<?php if (function_exists('can') && can($expression)): ?>"; }
    protected function compileEndcan() { return "<?php endif; ?>"; }

    protected function compileJs() { return "<?php \$__env->startPush('scripts'); ?>"; }
    protected function compileEndjs() { return "<?php \$__env->endPush(); ?>"; }
    protected function compileCss() { return "<?php \$__env->startPush('styles'); ?>"; }
    protected function compileEndcss() { return "<?php \$__env->endPush(); ?>"; }

    protected function compileHtmx(string $expression) {
        return "hx-target=\"$expression\" hx-swap=\"outerHTML\"";
    }

    protected function compileProduction() { return "<?php if (config('app.env') === 'production'): ?>"; }
    protected function compileEndproduction() { return "<?php endif; ?>"; }
    protected function compileEnv(string $expression) {
        return "<?php if (in_array(config('app.env'), (array) $expression)): ?>";
    }
    protected function compileEndenv() { return "<?php endif; ?>"; }
}
