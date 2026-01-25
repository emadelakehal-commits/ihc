<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only modify JSON responses
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            // If response doesn't already have success field, add it
            if (!isset($content['success'])) {
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $content['success'] = true;
                } elseif ($response->getStatusCode() >= 400) {
                    $content['success'] = false;
                }
            }

            // Add timestamp if not present
            if (!isset($content['timestamp'])) {
                $content['timestamp'] = now()->toISOString();
            }

            $response->setContent(json_encode($content));
        }

        return $response;
    }
}
