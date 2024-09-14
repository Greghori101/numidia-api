<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission)
    {

        foreach ($request->user->permissions as $action) {

            if ($action->name === $permission && $action->department === env('DEPARTMENT')) {
                return $next($request);
            }
        }
        abort(403, 'Unauthorized action.');
    }
}
