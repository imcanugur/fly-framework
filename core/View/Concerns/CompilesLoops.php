<?php

declare(strict_types=1);

namespace Fly\View\Concerns;

trait CompilesLoops
{
    protected function compileForeach(string $expression) {
        preg_match('/^\s*(.+?)\s+as\s+(.+)$/is', $expression, $matches);
        if (!$matches) return "<?php foreach($expression): ?>";
        return "<?php \$__currentLoopData = {$matches[1]}; \$__env->addLoop(\$__currentLoopData); foreach(\$__currentLoopData as {$matches[2]}): \$__env->incrementLoopIndices(); \$loop = \$__env->getLastLoop(); ?>";
    }
    protected function compileEndforeach() { return "<?php endforeach; \$__env->popLoop(); \$loop = \$__env->getLastLoop(); ?>"; }

    protected function compileForelse(string $expression) {
        return "<?php \$__forelse_empty = true; foreach($expression): \$__forelse_empty = false; ?>";
    }
    protected function compileEndforelse() { return "<?php endif; ?>"; }

    protected function compileFor(string $expression) { return "<?php for($expression): ?>"; }
    protected function compileEndfor() { return "<?php endfor; ?>"; }
    protected function compileWhile(string $expression) { return "<?php while($expression): ?>"; }
    protected function compileEndwhile() { return "<?php endwhile; ?>"; }
    
    protected function compileBreak(string $expression) { return $expression ? "<?php break $expression; ?>" : "<?php break; ?>"; }
    protected function compileContinue(string $expression) { return $expression ? "<?php continue $expression; ?>" : "<?php continue; ?>"; }
}
