<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Cache\CacheManager;
use Closure;

class CacheResponse implements MiddlewareInterface
{
    /**
     * The cache manager instance.
     */
    protected CacheManager $cache;

    /**
     * Create a new middleware instance.
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$args): Response
    {
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        $ttl = (int) ($args[0] ?? 60);
        $key = 'response_cache:' . md5($request->fullUrl());

        if ($this->cache->has($key)) {
            $cached = $this->cache->get($key);
            
            $response = new Response($cached['content'], $cached['status'], $cached['headers']);
            $response->headers->set('X-Cache', 'HIT');
            
            return $response;
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            $this->cache->put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], $ttl);
        }

        $response->headers->set('X-Cache', 'MISS');

        return $response;
    }
}
