<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 http://localhost:5175 http://127.0.0.1:5175; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 http://localhost:5175 http://127.0.0.1:5175; font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https://ui-avatars.com https://www.transparenttextures.com http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 http://localhost:5175 http://127.0.0.1:5175; connect-src 'self' ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174 http://localhost:5174 http://127.0.0.1:5174 ws://localhost:5175 ws://127.0.0.1:5175 http://localhost:5175 http://127.0.0.1:5175;");

        return $response;
    }
}
