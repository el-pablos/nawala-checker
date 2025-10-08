<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // For now, allow all authenticated users
        // In production, implement proper permission checking with Spatie Permission or similar
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // TODO: Implement actual permission checking
        // if (!auth()->user()->hasPermission($permission)) {
        //     abort(403, 'Unauthorized action.');
        // }

        return $next($request);
    }
}

