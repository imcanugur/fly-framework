<?php

declare(strict_types=1);

namespace Fly\View\Concerns;

trait CompilesConditionals
{
    protected function compileIf(string $expression) { return "<?php if($expression): ?>"; }
    protected function compileElseif(string $expression) { return "<?php elseif($expression): ?>"; }
    protected function compileElse() { return "<?php else: ?>"; }
    protected function compileEndif() { return "<?php endif; ?>"; }

    protected function compileUnless(string $expression) { return "<?php if(!($expression)): ?>"; }
    protected function compileEndunless() { return "<?php endif; ?>"; }

    protected function compileIsset(string $expression) { return "<?php if(isset($expression)): ?>"; }
    protected function compileEndisset() { return "<?php endif; ?>"; }

    protected function compileEmpty(string $expression) {
        if (empty($expression)) return "<?php endforeach; if (\$__forelse_empty): ?>";
        return "<?php if(empty($expression)): ?>";
    }
    protected function compileEndempty() { return "<?php endif; ?>"; }

    protected function compileError(string $expression) {
        return "<?php if (\$errors->has($expression)): \$message = \$errors->first($expression); ?>";
    }
    protected function compileEnderror() { return "<?php endif; ?>"; }

    protected function compileSwitch(string $expression) { return "<?php switch($expression): ?>"; }
    protected function compileCase(string $expression) { return "<?php case $expression: ?>"; }
    protected function compileDefault() { return "<?php default: ?>"; }
    protected function compileEndswitch() { return "<?php endswitch; ?>"; }
}
