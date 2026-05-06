<?php

declare(strict_types=1);

namespace Fly\View;

use Fly\View\Contracts\CompilerInterface;
use Fly\View\Contracts\EngineInterface;
use Fly\View\Exceptions\ViewNotFoundException;
use Fly\Support\Arr;

class Factory implements EngineInterface
{
    protected string $viewPath;
    protected string $cachePath;
    protected CompilerInterface $compiler;
    
    // State management
    protected array $sections = [];
    protected array $sectionStack = [];
    protected ?string $extend = null;
    protected array $componentStack = [];
    protected array $slotStack = [];
    protected array $pushes = [];
    protected array $pushStack = [];
    protected array $prependStack = [];
    protected array $loopsStack = [];
    protected array $renderedOnce = [];
    protected array $fragments = [];
    protected array $fragmentStack = [];
    protected array $composers = [];
    protected array $shared = [];

    public function __construct(string $viewPath, string $cachePath, CompilerInterface $compiler)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->compiler = $compiler;
    }

    public function make(string $view, array $data = []): View
    {
        return new View($this, $view, $data);
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->getViewPath($view);
        
        if (!file_exists($path)) {
            $plainPath = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
            if (file_exists($plainPath)) {
                return $this->evaluatePath($plainPath, array_merge($this->shared, $data));
            }
            throw new ViewNotFoundException($view, $path);
        }
        
        $compiledPath = $this->getCompiledPath($path);
        
        if ($this->isExpired($path, $compiledPath)) {
            $this->compile($path, $compiledPath);
        }
        
        return $this->evaluatePath($compiledPath, array_merge($this->shared, $data));
    }

    public function precompile(string $view): void
    {
        $path = $this->getViewPath($view);
        if (!file_exists($path)) return;
        
        $compiledPath = $this->getCompiledPath($path);
        if ($this->isExpired($path, $compiledPath)) {
            $this->compile($path, $compiledPath);
        }
    }

    protected function compile(string $path, string $compiledPath): void
    {
        $content = file_get_contents($path);
        $compiled = $this->compiler->compile($content);
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
        
        file_put_contents($compiledPath, $compiled);
    }

    protected function evaluatePath(string $__path, array $__data): string
    {
        $__env = $this;
        $__extendBackup = $this->extend;
        $this->extend = null;
        
        extract($__data);
        
        ob_start();
        try {
            include $__path;
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
        $content = ob_get_clean();
        
        if ($this->extend !== null) {
            $extendView = $this->extend;
            $this->extend = null;
            $content = $this->make($extendView, $__data)->render();
        }
        
        $this->extend = $__extendBackup;
        return $content;
    }

    protected function getViewPath(string $view): string
    {
        return $this->viewPath . '/' . str_replace('.', '/', $view) . '.fly.php';
    }
    
    protected function getCompiledPath(string $path): string
    {
        return $this->cachePath . '/' . md5($path) . '.php';
    }
    
    protected function isExpired(string $path, string $compiledPath): bool
    {
        if (!file_exists($compiledPath)) return true;
        return filemtime($path) >= filemtime($compiledPath);
    }

    // --- Layouts & Sections ---

    public function extend(string $view): void { $this->extend = $view; }
    
    public function startSection(string $section): void { if (ob_start()) $this->sectionStack[] = $section; }
    
    public function endSection(): void
    {
        $last = array_pop($this->sectionStack);
        if (!isset($this->sections[$last])) {
            $this->sections[$last] = ob_get_clean();
        } else {
            $this->sections[$last] .= ob_get_clean();
        }
    }
    
    public function yieldContent(string $section, string $default = ''): string
    {
        $content = $this->sections[$section] ?? $default;
        unset($this->sections[$section]);
        return $content;
    }

    // --- Components ---

    public function startComponent(string $view, array $data = []): void
    {
        if (ob_start()) {
            $this->componentStack[] = ['view' => $view, 'data' => $data, 'slots' => []];
        }
    }

    public function slot(string $name): void { if (ob_start()) $this->slotStack[] = $name; }

    public function endSlot(): void
    {
        $content = ob_get_clean();
        $lastSlot = array_pop($this->slotStack);
        $currentComponent = &$this->componentStack[count($this->componentStack) - 1];
        $currentComponent['slots'][$lastSlot] = $content;
    }

    public function renderComponent(): string
    {
        $slot = ob_get_clean();
        $component = array_pop($this->componentStack);
        $data = $component['data'];
        $data['slot'] = $slot;
        foreach ($component['slots'] as $name => $content) $data[$name] = $content;
        
        $attributeData = Arr::except($data, array_merge(['slot'], array_keys($component['slots'])));
        $data['attributes'] = new ComponentAttributeBag($attributeData);
        
        return $this->make($component['view'], $data)->render();
    }

    // --- Stacks ---

    public function startPush(string $section): void { if (ob_start()) $this->pushStack[] = $section; }
    public function endPush(): void
    {
        $content = ob_get_clean();
        $section = array_pop($this->pushStack);
        $this->pushes[$section][] = $content;
    }

    public function startPrepend(string $section): void { if (ob_start()) $this->prependStack[] = $section; }
    public function endPrepend(): void
    {
        $content = ob_get_clean();
        $section = array_pop($this->prependStack);
        array_unshift($this->pushes[$section] ?? [], $content);
    }

    public function yieldPushContent(string $section, string $default = ''): string
    {
        return isset($this->pushes[$section]) ? implode('', $this->pushes[$section]) : $default;
    }

    // --- Loops ---

    public function addLoop(mixed $data): void
    {
        $length = is_array($data) || $data instanceof \Countable ? count($data) : null;
        $this->loopsStack[] = [
            'iteration' => 0, 'index' => 0, 'remaining' => $length, 'count' => $length,
            'first' => true, 'last' => isset($length) ? $length == 1 : null,
            'depth' => count($this->loopsStack) + 1, 'parent' => end($this->loopsStack) ?: null
        ];
    }

    public function incrementLoopIndices(): void
    {
        $loop = &$this->loopsStack[count($this->loopsStack) - 1];
        $loop['iteration']++;
        $loop['index'] = $loop['iteration'] - 1;
        $loop['first'] = $loop['iteration'] == 1;
        if (isset($loop['count'])) {
            $loop['remaining'] = $loop['count'] - $loop['iteration'];
            $loop['last'] = $loop['iteration'] == $loop['count'];
        }
    }

    public function popLoop(): void { array_pop($this->loopsStack); }
    public function getLastLoop(): ?\stdClass { return $this->loopsStack ? (object) end($this->loopsStack) : null; }

    // --- Composers & State ---

    public function composer(string|array $views, callable $callback): void
    {
        foreach ((array) $views as $view) $this->composers[$view][] = $callback;
    }

    public function callComposers(View $view): void
    {
        $name = $view->getName();
        foreach (['*', $name] as $key) {
            foreach ($this->composers[$key] ?? [] as $composer) $composer($view);
        }
    }

    public function share(string $key, mixed $value = null): void { $this->shared[$key] = $value; }
    public function getShared(): array { return $this->shared; }

    public function hasRenderedOnce(string $id): bool { return isset($this->renderedOnce[$id]); }
    public function markAsRenderedOnce(string $id): void { $this->renderedOnce[$id] = true; }

    public function startFragment(string $name): void { if (ob_start()) $this->fragmentStack[] = $name; }
    public function stopFragment(): void
    {
        $content = ob_get_clean();
        $name = array_pop($this->fragmentStack);
        $this->fragments[$name] = $content;
        echo $content;
    }

    public function getFragment(string $name): ?string { return $this->fragments[$name] ?? null; }
    
    public function directive(string $name, callable $handler): void { $this->compiler->directive($name, $handler); }
    
    public function exists(string $view): bool
    {
        return file_exists($this->getViewPath($view)) || file_exists($this->viewPath . '/' . str_replace('.', '/', $view) . '.php');
    }

    public function renderEach(string $view, iterable $data, string $iterator, string $emptyView = null): string
    {
        $result = '';
        foreach ($data as $item) $result .= $this->make($view, [$iterator => $item])->render();
        if ($result === '' && $emptyView) $result .= $this->make($emptyView)->render();
        return $result;
    }
}
