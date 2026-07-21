<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowIframeEmbedding
{
    /**
     * Allow embedding from the Noraya app by removing X-Frame-Options
     * and setting a Content-Security-Policy frame-ancestors policy.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Frame-Options');

        // Also clear any header PHP may have already sent.
        if (function_exists('header_remove')) {
            header_remove('X-Frame-Options');
        }

        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self' https://app.noraya.in"
        );

        return $response;
    }
}
