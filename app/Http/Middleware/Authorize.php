<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authorize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if ((Auth::user()->role == 'student' or Auth::user()->role == 'parent')) {
            return $next($request);
        }
        if ($request->role != $role or Auth::user()->role != $role) {
            abort(402, 'Unauthorized action.');
        }
        return $next($request);
    }
}
