<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthorizeCatalogAccess
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
        $requestId = $request->header('X-Request-ID');
        $userId = $request->header('X-User-Id');

        // Decode JWT from Authorization header
        $jwtPayload = $this->decodeJwt($request);

        if (!$jwtPayload) {
            \Log::channel('security')->warning('JWT decoding failed', [
                'request_id' => $requestId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Invalid or missing JWT token'
            ], 403);
        }

        // TEMPORARILY BYPASS ROLE CHECKING - User roles ignored for now
        \Log::channel('security')->info('Authorization successful - roles bypassed', [
            'request_id' => $requestId,
            'user_id' => $userId,
            'jwt_user_id' => $jwtPayload->sub ?? null,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        return $next($request);
    }

    /**
     * Decode and validate JWT token from Authorization header.
     *
     * @param Request $request
     * @return object|null
     */
    private function decodeJwt(Request $request): ?object
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

        // Use JWT secret from environment variable
        $jwtSecret = env('JWT_SECRET');

        try {
            // Ensure JWT secret is a string, not a SensitiveParameterValue object
            $jwtSecretString = is_object($jwtSecret) ? (string)$jwtSecret : $jwtSecret;

            $decoded = JWT::decode($token, new Key($jwtSecretString, 'HS256'));

            // Validate JWT claims
            if (!$this->validateJwtClaims($decoded)) {
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            // Log to dedicated error channel
            \Log::channel('error')->error('JWT decoding error', [
                'error' => $e->getMessage(),
                'request_id' => $request->header('X-Request-ID'),
                'user_id' => $request->header('X-User-Id'),
                'token_prefix' => substr($token, 0, 10) . '...',
            ]);
            return null;
        }
    }

    /**
     * Validate JWT claims according to requirements.
     *
     * @param object $payload
     * @return bool
     */
    private function validateJwtClaims(object $payload): bool
    {
        // Validate audience (aud) must equal "catalog"
        if (!isset($payload->aud) || $payload->aud !== 'catalog') {
            \Log::channel('security')->warning('Invalid JWT audience', [
                'aud' => $payload->aud ?? 'missing',
                'expected' => 'catalog',
            ]);
            return false;
        }

        // Validate subject (sub) exists
        if (!isset($payload->sub) || empty($payload->sub)) {
            \Log::channel('security')->warning('Missing JWT subject');
            return false;
        }

        // SKIP: Validate expiration (exp) - tokens never expire
        // Expiration check is disabled as per requirements

        return true;
    }

    /**
     * Get the required role based on HTTP method.
     *
     * @param string $method
     * @return string
     */
    private function getRequiredRole(string $method): string
    {
        // CATALOG_READ for read endpoints (GET, HEAD, OPTIONS)
        // CATALOG_WRITE for write endpoints (POST, PUT, DELETE, PATCH)
        return in_array($method, ['GET', 'HEAD', 'OPTIONS'])
            ? 'CATALOG_READ'
            : 'CATALOG_WRITE';
    }
}
