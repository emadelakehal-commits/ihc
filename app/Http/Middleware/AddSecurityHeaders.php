<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers to every response
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");

        // Add CORS headers for cross-origin requests
        $response->headers->set('Access-Control-Allow-Origin', config('cors.allowed_origins'));
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', config('cors.allowed_methods')));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', config('cors.allowed_headers')));
        $response->headers->set('Access-Control-Allow-Credentials', config('cors.allow_credentials') ? 'true' : 'false');

        return $response;
    }
}
