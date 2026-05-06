<?php

declare(strict_types=1);

namespace Fly\View;

class Compiler
{
    protected array $customDirectives = [];

    public function compile(string $value): string
    {
        $value = $this->compileComments($value);
        $value = $this->compileComponents($value);
        $value = $this->compileEchos($value);
        $value = $this->compileStatements($value);
        
        return $value;
    }

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    protected function compileComments(string $value): string
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', '\{\{', '\}\}');
        return preg_replace($pattern, '<?php /* $1 */ ?>', $value);
    }

    protected function compileEchos(string $value): string
    {
        // Unescaped: {!! $var !!}
        $value = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/s', '<?php echo $1; ?>', $value);
        
        // Escaped: {{ $var }}
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
            
            // Avoid capturing <fly:slot> here
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
        if (trim($attributeString) === '') {
            return '[]';
        }
        
        $attributes = [];
        preg_match_all('/([a-zA-Z0-9_\-:]+)(?:="([^"]*)")?/', $attributeString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = $match[1];
            $value = isset($match[2]) ? $match[2] : 'true';
            
            if (str_starts_with($key, ':')) {
                // PHP expression
                $key = substr($key, 1);
                $attributes[] = "'$key' => $value";
            } else {
                // String
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
            $match[0] = isset($match[3]) ? $match[1].$match[2].$match[3] : $match[1];
            return $match[0]; // Escaped directive @@if -> @if
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

        return $match[0]; // Not a directive
    }

    protected function stripParentheses(string $expression): string
    {
        if (str_starts_with($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }
        return $expression;
    }

    // -------------------------------------------------------------------------
    // Directives
    // -------------------------------------------------------------------------

    protected function compileFly(string $expression) { return '<?php '; }
    protected function compileEndfly(string $expression) { return ' ?>'; }
    
    protected function compileProps(string $expression) {
        return "<?php \$attributes = \$attributes->merge($expression); extract(\$attributes->getIterator()->getArrayCopy()); ?>";
    }

    protected function compileOnce(string $expression) {
        return "<?php if (!\$__env->hasRenderedOnce($expression)): \$__env->markAsRenderedOnce($expression); ?>";
    }

    protected function compileEndonce(string $expression) {
        return "<?php endif; ?>";
    }

    protected function compileError(string $expression) {
        return "<?php if (\$errors->has($expression)): \$message = \$errors->first($expression); ?>";
    }

    protected function compileEnderror(string $expression) {
        return "<?php endif; ?>";
    }

    protected function compileChecked(string $expression) {
        return "<?php echo ($expression) ? 'checked=\"checked\"' : ''; ?>";
    }

    protected function compileSelected(string $expression) {
        return "<?php echo ($expression) ? 'selected=\"selected\"' : ''; ?>";
    }

    protected function compileDisabled(string $expression) {
        return "<?php echo ($expression) ? 'disabled=\"disabled\"' : ''; ?>";
    }

    protected function compileClass(string $expression) { 
        return "<?php echo 'class=\"' . \\Fly\\View\\ComponentAttributeBag::compileClass($expression) . '\"'; ?>"; 
    }

    // Layouts
    protected function compileExtends(string $expression) { return "<?php \$__env->extend($expression); ?>"; }
    protected function compileSection(string $expression) { 
        // Support @section('title', 'Welcome')
        if (str_contains($expression, ',')) {
            return "<?php \$__env->startSection(" . preg_replace('/^([^\,]+)\,/', '$1); echo ', $expression, 1) . "; \$__env->endSection(); ?>";
        }
        return "<?php \$__env->startSection($expression); ?>"; 
    }
    protected function compileEndsection(string $expression) { return "<?php \$__env->endSection(); ?>"; }
    protected function compileYield(string $expression) { return "<?php echo \$__env->yieldContent($expression); ?>"; }
    protected function compileInclude(string $expression) { return "<?php echo \$__env->make($expression, \Fly\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>"; }

    // Stacks
    protected function compilePush(string $expression) { return "<?php \$__env->startPush($expression); ?>"; }
    protected function compileEndpush(string $expression) { return "<?php \$__env->endPush(); ?>"; }
    protected function compilePrepend(string $expression) { return "<?php \$__env->startPrepend($expression); ?>"; }
    protected function compileEndprepend(string $expression) { return "<?php \$__env->endPrepend(); ?>"; }
    protected function compileStack(string $expression) { return "<?php echo \$__env->yieldPushContent($expression); ?>"; }

    // Conditionals
    protected function compileIf(string $expression) { return "<?php if($expression): ?>"; }
    protected function compileElseif(string $expression) { return "<?php elseif($expression): ?>"; }
    protected function compileElse(string $expression) { return "<?php else: ?>"; }
    protected function compileEndif(string $expression) { return "<?php endif; ?>"; }

    protected function compileIsset(string $expression) { return "<?php if(isset($expression)): ?>"; }
    protected function compileEndisset(string $expression) { return "<?php endif; ?>"; }

    protected function compileEmpty(string $expression) { return "<?php if(empty($expression)): ?>"; }
    protected function compileEndempty(string $expression) { return "<?php endif; ?>"; }

    // Loops
    protected function compileForeach(string $expression) {
        preg_match('/^\s*(.+?)\s+as\s+(.+)$/is', $expression, $matches);
        if (!$matches) { return "<?php foreach($expression): ?>"; }
        
        return "<?php \$__currentLoopData = {$matches[1]}; \$__env->addLoop(\$__currentLoopData); foreach(\$__currentLoopData as {$matches[2]}): \$__env->incrementLoopIndices(); \$loop = \$__env->getLastLoop(); ?>";
    }
    protected function compileEndforeach(string $expression) {
        return "<?php endforeach; \$__env->popLoop(); \$loop = \$__env->getLastLoop(); ?>";
    }

    protected function compileFor(string $expression) { return "<?php for($expression): ?>"; }
    protected function compileEndfor(string $expression) { return "<?php endfor; ?>"; }
    
    protected function compileWhile(string $expression) { return "<?php while($expression): ?>"; }
    protected function compileEndwhile(string $expression) { return "<?php endwhile; ?>"; }

    // Helpers
    protected function compileCsrf(string $expression) { return "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"'.csrf_token().'\">'; ?>"; }
    protected function compileMethod(string $expression) { return "<?php echo '<input type=\"hidden\" name=\"_method\" value=\"'.$expression.'\">'; ?>"; }
    protected function compileDd(string $expression) { return "<?php dd($expression); ?>"; }
    protected function compileDump(string $expression) { return "<?php dump($expression); ?>"; }
    protected function compileJson(string $expression) { return "<?php echo json_encode($expression); ?>"; }
}
