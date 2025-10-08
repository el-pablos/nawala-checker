<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $key, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $limiterKey = $key . ':' . $request->user()?->id ?? $request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            
            return response()->json([
                'message' => 'Too many requests. Please try again in ' . $seconds . ' seconds.',
            ], 429);
        }

        RateLimiter::hit($limiterKey, $decayMinutes * 60);

        return $next($request);
    }
}

