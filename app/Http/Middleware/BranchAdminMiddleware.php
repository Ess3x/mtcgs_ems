<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BranchAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        // Super Admin has full access
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Branch Admin can only access their branch
        if ($user->isBranchAdmin()) {
            $branchId = $user->getAdminBranch()->id ?? null;
            
            // Add branch filter to request
            $request->merge(['branch_filter' => $branchId]);
            
            return $next($request);
        }
        
        // Non-admin users cannot proceed
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized');
        }
        
        return $next($request);
    }
}
