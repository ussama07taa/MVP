<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // Isolate Spatie Permission cache per tenant
            app(\Spatie\Permission\PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.tenant.' . auth()->user()->tenant_id;
        }

        return $next($request);
    }
}
