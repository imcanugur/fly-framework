<?php

declare(strict_types=1);

namespace Fly\View\Concerns;

trait CompilesLayouts
{
    protected function compileExtends(string $expression) { return "<?php \$__env->extend($expression); ?>"; }
    
    protected function compileSection(string $expression) {
        if (str_contains($expression, ',')) {
            $parts = explode(',', $expression, 2);
            return "<?php \$__env->startSection({$parts[0]}); echo {$parts[1]}; \$__env->endSection(); ?>";
        }
        return "<?php \$__env->startSection($expression); ?>";
    }
    
    protected function compileEndsection() { return "<?php \$__env->endSection(); ?>"; }
    protected function compileYield(string $expression) { return "<?php echo \$__env->yieldContent($expression); ?>"; }
    
    protected function compileFragment(string $expression) { return "<?php \$__env->startFragment($expression); ?>"; }
    protected function compileStopfragment() { return "<?php \$__env->stopFragment(); ?>"; }
    
    protected function compilePush(string $expression) { return "<?php \$__env->startPush($expression); ?>"; }
    protected function compileEndpush() { return "<?php \$__env->endPush(); ?>"; }
    protected function compilePrepend(string $expression) { return "<?php \$__env->startPrepend($expression); ?>"; }
    protected function compileEndprepend() { return "<?php \$__env->endPrepend(); ?>"; }
    protected function compileStack(string $expression) { return "<?php echo \$__env->yieldPushContent($expression); ?>"; }
}
