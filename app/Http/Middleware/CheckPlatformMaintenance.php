<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $request->routeIs('logout', 'view-as.exit') && $user && PlatformSetting::current()->maintenance_mode) {
            abort_unless($user->is_admin, 503, 'The platform is temporarily unavailable for maintenance. Please try again later.');
        }

        return $next($request);
    }
}
