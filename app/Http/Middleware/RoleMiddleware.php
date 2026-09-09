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
        
        abort(403, 'Unauthorized access.');
    }
}
