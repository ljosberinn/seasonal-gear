<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blade indentation repeated per item row makes up about 60% of the gear page and a third of it
 * gzipped. Whitespace between tags renders the same however long it is, so one newline is kept.
 */
class CollapseHtmlWhitespace
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $content = $response->getContent();

        if (is_string($content) && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->setContent(preg_replace('/>\s+</', ">\n<", $content) ?? $content);
        }

        return $response;
    }
}
