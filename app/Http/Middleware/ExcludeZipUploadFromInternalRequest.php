<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExcludeZipUploadFromInternalRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Log the request for debugging
        \Log::info('ExcludeZipUploadFromInternalRequest middleware called', [
            'path' => $request->path(),
            'method' => $request->method(),
            'is_zip_upload' => $request->is('api/products/upload-zip') && $request->method() === 'POST'
        ]);

        // Skip internal request validation for zip upload endpoint
        if ($request->is('api/products/upload-zip') && $request->method() === 'POST') {
            \Log::info('Skipping internal request validation for zip upload');
            return $next($request);
        }

        // For other routes, continue with normal processing
        return $next($request);
    }
}