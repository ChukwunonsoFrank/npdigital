<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureViewAsUserIsReadOnly
{
    /**
     * Block any write request while an admin is previewing a user account.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('view_as_admin_id') || $request->routeIs('view-as.exit')) {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            abort(403, 'This is a read-only preview. Actions are disabled while viewing as a user.');
        }

        return $next($request);
    }
}
