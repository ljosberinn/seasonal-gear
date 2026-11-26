<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages only change when a deployment ships a new dataset or new code, and Vercel keys its CDN
 * cache by deployment, so the CDN can keep a page for its maximum lifetime. Browsers can't see
 * deployments, so they revalidate every time and get a 304 while the page is unchanged.
 */
class CacheOnCdn
{
    private const int CDN_MAX_AGE = 31_536_000;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodCacheable() || ! $response->isOk()) {
            return $response;
        }

        $response->setCache([
            'public' => true,
            'max_age' => 0,
            'must_revalidate' => true,
            'etag' => hash('xxh128', (string) $response->getContent()),
        ]);

        // Vercel reads this header ahead of Cache-Control and doesn't forward it to the browser.
        $response->headers->set('Vercel-CDN-Cache-Control', 'max-age='.self::CDN_MAX_AGE);

        $response->isNotModified($request);

        return $response;
    }
}
