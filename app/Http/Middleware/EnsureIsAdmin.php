<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Prefer Spatie role; fall back to legacy `role` column for backward compatibility.
            $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
            $isLegacyAdmin = $user->role === 'admin';

            if ($isAdmin || $isLegacyAdmin) {
                return $next($request);
            }

            // Cashiers get access to specific operations: POS/sales history, invoices, clients, and workshop queue/dashboard
            $isCashier = (method_exists($user, 'hasRole') && $user->hasRole('cashier')) || $user->role === 'cashier';
            if ($isCashier) {
                $allowedPatterns = [
                    'admin/dashboard*',
                    'admin/workshop-queue*',
                    'admin/orders*',
                    'admin/invoices*',
                    'admin/clients*',
                    'api/admin/dashboard*',
                    'api/admin/workshop-queue*',
                    'api/admin/orders*',
                    'api/admin/invoices*',
                    'api/admin/clients*',
                ];

                foreach ($allowedPatterns as $pattern) {
                    if ($request->is($pattern)) {
                        return $next($request);
                    }
                }
            }

            // Workers only get access to the Mobile Workshop page
            $isWorker = (method_exists($user, 'hasRole') && $user->hasRole('worker')) || $user->role === 'worker';
            if ($isWorker) {
                $allowedPatterns = [
                    'admin/atelier*',
                    'api/workshop*',
                ];

                foreach ($allowedPatterns as $pattern) {
                    if ($request->is($pattern)) {
                        return $next($request);
                    }
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Accès refusé. Réservé à l\'administration.'], 403);
        }

        abort(403, 'Accès réservé à l\'administration.');
    }
}
