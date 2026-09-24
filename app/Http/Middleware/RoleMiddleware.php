<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        if (empty($roles)) {
            return $next($request);
        }
        
        if (in_array(auth()->user()->role, $roles)) {
            return $next($request);
        }
        
        return redirect()->route('dashboard')->with('error', 'You are not authorized to access that page.');
    }
}
