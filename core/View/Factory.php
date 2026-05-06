<?php

declare(strict_types=1);

namespace Fly\View;

class Factory
{
    protected string $viewPath;
    protected string $cachePath;
    protected Compiler $compiler;
    
    // Layouts
    protected array $sections = [];
    protected array $sectionStack = [];
    protected ?string $extend = null;
    
    // Components
    protected array $componentStack = [];
    protected array $slotStack = [];

    // Stacks
    protected array $pushes = [];
    protected array $pushStack = [];
    protected array $prependStack = [];

    // Loops
    protected array $loopsStack = [];

    // Once tracking
    protected array $renderedOnce = [];

    // Fragments
    protected array $fragments = [];
    protected array $fragmentStack = [];

    // Composers
    protected array $composers = [];

    // Data
    protected array $shared = [];

    public function __construct(string $viewPath, string $cachePath, Compiler $compiler = null)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->compiler = $compiler ?? new Compiler();
    }

    public function make(string $view, array $data = []): View
    {
        return new View($this, $view, $data);
    }
    
    public function share(string $key, mixed $value = null): void
    {
        $this->shared[$key] = $value;
    }
    
    public function getShared(): array
    {
        return $this->shared;
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->getViewPath($view);
        
        if (!file_exists($path)) {
            $plainPath = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
            if (file_exists($plainPath)) {
                return $this->evaluatePath($plainPath, array_merge($this->shared, $data));
            }
            throw new \RuntimeException("View [$view] not found at path: $path");
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

    public function callComposers(View $view): void
    {
        $name = $view->getName();
        foreach (['*', $name] as $key) {
            if (isset($this->composers[$key])) {
                foreach ($this->composers[$key] as $composer) {
                    $composer($view);
                }
            }
        }
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
        if (!file_exists($compiledPath)) {
            return true;
        }
        
        return filemtime($path) >= filemtime($compiledPath);
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
        include $__path;
        $content = ob_get_clean();
        
        if ($this->extend !== null) {
            $extendView = $this->extend;
            $this->extend = null;
            $content = $this->make($extendView, $__data)->render();
        }
        
        $this->extend = $__extendBackup;
        
        return $content;
    }
    
    // -------------------------------------------------------------------------
    // Layouts
    // -------------------------------------------------------------------------

    public function extend(string $view): void
    {
        $this->extend = $view;
    }
    
    public function startSection(string $section): void
    {
        if (ob_start()) {
            $this->sectionStack[] = $section;
        }
    }
    
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

    // -------------------------------------------------------------------------
    // Components
    // -------------------------------------------------------------------------

    public function startComponent(string $view, array $data = []): void
    {
        if (ob_start()) {
            $this->componentStack[] = [
                'view' => $view,
                'data' => $data,
                'slots' => []
            ];
        }
    }

    public function slot(string $name): void
    {
        if (ob_start()) {
            $this->slotStack[] = $name;
        }
    }

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
        
        // Named slots
        foreach ($component['slots'] as $name => $content) {
            $data[$name] = $content;
        }

        // Attributes should exclude slots
        $attributeData = \Fly\Support\Arr::except($data, array_merge(['slot'], array_keys($component['slots'])));
        $data['attributes'] = new ComponentAttributeBag($attributeData);
        
        return $this->make($component['view'], $data)->render();
    }

    // -------------------------------------------------------------------------
    // Stacks
    // -------------------------------------------------------------------------

    public function startPush(string $section): void
    {
        if (ob_start()) {
            $this->pushStack[] = $section;
        }
    }

    public function endPush(): void
    {
        $content = ob_get_clean();
        $section = array_pop($this->pushStack);
        if (!isset($this->pushes[$section])) {
            $this->pushes[$section] = [];
        }
        $this->pushes[$section][] = $content;
    }

    public function startPrepend(string $section): void
    {
        if (ob_start()) {
            $this->prependStack[] = $section;
        }
    }

    public function endPrepend(): void
    {
        $content = ob_get_clean();
        $section = array_pop($this->prependStack);
        if (!isset($this->pushes[$section])) {
            $this->pushes[$section] = [];
        }
        array_unshift($this->pushes[$section], $content);
    }

    public function yieldPushContent(string $section, string $default = ''): string
    {
        if (!isset($this->pushes[$section])) return $default;
        return implode('', $this->pushes[$section]);
    }

    // -------------------------------------------------------------------------
    // Loops
    // -------------------------------------------------------------------------

    public function addLoop(mixed $data): void
    {
        $length = is_array($data) || $data instanceof \Countable ? count($data) : null;
        $this->loopsStack[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $length,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length == 1 : null,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $this->loopsStack ? end($this->loopsStack) : null,
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
        } else {
            $loop['last'] = null;
        }
    }

    public function popLoop(): void
    {
        array_pop($this->loopsStack);
    }

    public function getLastLoop(): ?\stdClass
    {
        if (!$this->loopsStack) return null;
        
        $loop = end($this->loopsStack);
        return (object) $loop;
    }

    // -------------------------------------------------------------------------
    // Composers & Data
    // -------------------------------------------------------------------------

    public function composer(string|array $views, callable $callback): void
    {
        foreach ((array) $views as $view) {
            $this->composers[$view][] = $callback;
        }
    }

    // -------------------------------------------------------------------------
    // Once
    // -------------------------------------------------------------------------

    public function hasRenderedOnce(string $id): bool
    {
        return isset($this->renderedOnce[$id]);
    }

    public function markAsRenderedOnce(string $id): void
    {
        $this->renderedOnce[$id] = true;
    }

    // -------------------------------------------------------------------------
    // Fragments
    // -------------------------------------------------------------------------

    public function startFragment(string $name): void
    {
        if (ob_start()) {
            $this->fragmentStack[] = $name;
        }
    }

    public function stopFragment(): void
    {
        $content = ob_get_clean();
        $name = array_pop($this->fragmentStack);
        $this->fragments[$name] = $content;
        echo $content;
    }

    public function getFragment(string $name): ?string
    {
        return $this->fragments[$name] ?? null;
    }

    // -------------------------------------------------------------------------
    // Custom Directives
    // -------------------------------------------------------------------------

    public function directive(string $name, callable $handler): void
    {
        $this->compiler->directive($name, $handler);
    }

    public function exists(string $view): bool
    {
        $path = $this->getViewPath($view);
        if (file_exists($path)) return true;
        
        $plainPath = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
        return file_exists($plainPath);
    }

    public function renderEach(string $view, iterable $data, string $iterator, string $emptyView = null): string
    {
        $result = '';
        $data = is_array($data) ? $data : iterator_to_array($data);
        
        if (count($data) > 0) {
            foreach ($data as $item) {
                $result .= $this->make($view, [$iterator => $item])->render();
            }
        } elseif ($emptyView) {
            $result .= $this->make($emptyView)->render();
        }
        
        return $result;
    }
}
