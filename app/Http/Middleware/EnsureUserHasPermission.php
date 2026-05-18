<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permissionSpec): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $parts = str_contains($permissionSpec, '|')
            ? array_values(array_filter(array_map(trim(...), explode('|', $permissionSpec))))
            : [trim($permissionSpec)];

        $allowed = count($parts) === 1
            ? $user->hasPermission($parts[0])
            : $user->hasAnyPermission(...$parts);

        if (! $allowed) {
            abort(403);
        }

        return $next($request);
    }
}
