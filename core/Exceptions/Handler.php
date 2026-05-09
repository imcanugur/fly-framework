<?php

declare(strict_types=1);

namespace Fly\Exceptions;

use Fly\Http\Request;
use Fly\Http\Response;
use Throwable;

/**
 * The Exception Handler.
 * 
 * Responsible for reporting and rendering exceptions.
 */
class Handler
{
    /**
     * Register the exception handler for the application.
     */
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Convert PHP errors to ErrorExceptions.
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        return false;
    }

    /**
     * Handle an uncaught exception.
     */
    public function handleException(\Throwable $e): void
    {
        $request = app()->has('request') ? app('request') : Request::capture();
        
        $this->report($e);
        
        if (PHP_SAPI === 'cli') {
            echo "\033[31mException: " . $e->getMessage() . "\033[0m\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo $e->getTraceAsString() . "\n";
            return;
        }

        $this->render($request, $e)->send();
    }

    /**
     * Handle the application shutdown and look for fatal errors.
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && $this->isFatal($error['type'])) {
            $this->handleException(new \ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            ));
        }
    }

    /**
     * Determine if the error type is fatal.
     */
    protected function isFatal(int $type): bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        // For now, we just rely on standard PHP error logging
        // In the future, we could integrate with Sentry, Loggly, etc.
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render(Request $request, Throwable $e): Response
    {
        $debug = config('app.debug', false);

        if ($debug) {
            return $this->renderDebugResponse($request, $e);
        }

        return $this->renderProductionResponse($e);
    }

    /**
     * Render the detailed debug response.
     */
    protected function renderDebugResponse(Request $request, Throwable $e): Response
    {
        $data = $this->getExceptionData($request, $e);
        
        ob_start();
        extract($data);
        include __DIR__ . '/Views/debug.php';
        $content = ob_get_clean();
        
        return new Response($content, 500);
    }

    /**
     * Render the clean production response.
     */
    protected function renderProductionResponse(Throwable $e): Response
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        ob_start();
        extract(['statusCode' => $statusCode]);
        include __DIR__ . '/Views/production.php';
        $content = ob_get_clean();
        
        return new Response($content, $statusCode);
    }

    /**
     * Get the data required for the exception view.
     */
    protected function getExceptionData(Request $request, Throwable $e): array
    {
        return [
            'request' => $request,
            'exception' => $e,
            'exceptionName' => (new \ReflectionClass($e))->getShortName(),
            'exceptionClass' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->formatTrace($e->getTrace()),
            'codeSnippet' => $this->getCodeSnippet($e->getFile(), $e->getLine()),
            'phpVersion' => PHP_VERSION,
            'os' => PHP_OS,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s'),
            'headers' => getallheaders(),
            'env' => $_ENV,
            'serverVars' => $_SERVER,
            'config' => config()->all(),
            'execution_time' => number_format((microtime(true) - FLY_START) * 1000, 2),
            'memory_usage' => number_format(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'extensions' => get_loaded_extensions(),
            'php_ini' => [
                'display_errors' => ini_get('display_errors'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'opcache.enable' => ini_get('opcache.enable'),
            ],
        ];
    }

    /**
     * Format the stack trace with code snippets for each step.
     */
    protected function formatTrace(array $trace): array
    {
        return array_map(function ($step) {
            if (isset($step['file']) && isset($step['line'])) {
                $step['snippet'] = $this->getCodeSnippet($step['file'], $step['line'], 5);
            } else {
                $step['snippet'] = [];
            }
            return $step;
        }, $trace);
    }

    /**
     * Get a snippet of code around the given line.
     */
    protected function getCodeSnippet(string $file, int $line, int $radius = 10): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $lines = file($file);
        $start = max(0, $line - $radius - 1);
        $end = min(count($lines), $line + $radius);

        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = $lines[$i];
        }

        return $snippet;
    }
}
