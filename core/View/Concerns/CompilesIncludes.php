<?php

declare(strict_types=1);

namespace Fly\View\Concerns;

trait CompilesIncludes
{
    protected function compileInclude(string $expression) {
        return "<?php echo \$__env->make($expression, \Fly\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }
    
    protected function compileIncludeIf(string $expression) {
        return "<?php if (\$__env->exists($expression)) echo \$__env->make($expression, \Fly\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }
    
    protected function compileIncludeWhen(string $expression) {
        preg_match('/^([^\,]+)\,\s*(.+)$/s', $expression, $matches);
        return "<?php if ({$matches[1]}) echo \$__env->make({$matches[2]}, \Fly\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }
    
    protected function compileIncludeUnless(string $expression) {
        preg_match('/^([^\,]+)\,\s*(.+)$/s', $expression, $matches);
        return "<?php if (!({$matches[1]})) echo \$__env->make({$matches[2]}, \Fly\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    protected function compileEach(string $expression) { return "<?php echo \$__env->renderEach($expression); ?>"; }
}
