<?php

namespace App\Http\Middleware;

use App\Support\PortalPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanCreatePortalUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->isStaffSuperUser()) {
            return $next($request);
        }

        if ($user->hasPermission(PortalPermissions::STAFF_USER_CREATE)) {
            return $next($request);
        }

        abort(403);
    }
}
