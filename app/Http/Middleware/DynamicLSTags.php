<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DynamicLSTags
{
    /**
     * Handle an incoming request.
     *
     * @param  mixed  ...$tags
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$tags)
    {
        $response = $next($request);

        if (! in_array($request->getMethod(), ['GET', 'HEAD']) || ! $response->getContent()) {
            return $response;
        }

        $parsedTags = [];
        foreach ($tags as $tag) {
            // Replace {param} with the actual route parameter
            $parsedTag = preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($request) {
                $paramName = $matches[1];
                $routeParam = $request->route($paramName);
                if (is_object($routeParam) && method_exists($routeParam, 'getKey')) {
                    return $routeParam->getKey();
                }

                return $routeParam ?? $matches[0];
            }, $tag);

            $parsedTags[] = $parsedTag;
        }

        if (count($parsedTags) > 0) {
            $lscache_string = implode(',', $parsedTags);

            // Log tags for verification/debugging in a Redis set for easy CLI access
            try {
                \Illuminate\Support\Facades\Redis::connection('central_keys')->sadd('visited_lscache_tags', ...$parsedTags);
            } catch (\Exception $e) {
                // Fail silently to avoid breaking the request if Redis is down
            }

            if ($response->headers->has('X-LiteSpeed-Tag')) {
                $existing = $response->headers->get('X-LiteSpeed-Tag');
                $response->headers->set('X-LiteSpeed-Tag', $existing.','.$lscache_string);
            } else {
                $response->headers->set('X-LiteSpeed-Tag', $lscache_string);
            }
        }

        return $response;
    }
}
