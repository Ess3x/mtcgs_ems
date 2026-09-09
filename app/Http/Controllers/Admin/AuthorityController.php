<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\FinanceProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthorityController extends Controller
{
    /**
     * Show authority management dashboard (Super Admin only)
     */
    public function index()
    {
        $user = Auth::user();
        
        // Only super admin can manage authority
        if ($user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            abort(403, 'Unauthorized. Only Super Admin can manage authority.');
        }

        // Get all branch admins with their authority status
        $branchAdmins = AdminProfile::where('admin_level', 'branch_admin')
            ->with(['user', 'branch'])
            ->orderBy('branch_id')
            ->get()
            ->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'user_id' => $admin->user_id,
                    'name' => $admin->full_name,
                    'branch' => $admin->branch->branch_name ?? 'N/A',
                    'branch_id' => $admin->branch_id,
                    'can_create_employees' => $admin->can_create_employees ?? false,
                    'can_manage_accounts' => $admin->can_manage_accounts ?? false,
                    'authority_granted_at' => $admin->authority_granted_at,
                    'granted_by' => $admin->authority_granted_by ? User::find($admin->authority_granted_by)?->name : null,
                ];
            });

        // Get all finance officers with their authority status
        $financeOfficers = FinanceProfile::with(['user', 'branch'])
            ->orderBy('branch_id')
            ->get()
            ->map(function ($finance) {
                return [
                    'id' => $finance->id,
                    'user_id' => $finance->user_id,
                    'name' => $finance->full_name,
                    'branch' => $finance->branch->branch_name ?? 'N/A',
                    'branch_id' => $finance->branch_id,
                    'can_create_employees' => $finance->can_create_employees ?? false,
                    'can_manage_accounts' => $finance->can_manage_accounts ?? false,
                    'authority_granted_at' => $finance->authority_granted_at,
                    'granted_by' => $finance->authority_granted_by ? User::find($finance->authority_granted_by)?->name : null,
                ];
            });

        return view('admin.authority.index', compact('branchAdmins', 'financeOfficers'));
    }

    /**
     * Grant authority to branch admin
     */
    public function grantAdminAuthority(Request $request, $adminId)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'can_create_employees' => 'boolean',
            'can_manage_accounts' => 'boolean',
        ]);

        try {
            $admin = AdminProfile::findOrFail($adminId);
            
            $admin->update([
                'can_create_employees' => $request->can_create_employees ?? false,
                'can_manage_accounts' => $request->can_manage_accounts ?? false,
                'authority_granted_by' => $user->id,
                'authority_granted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Authority granted to {$admin->full_name}",
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->full_name,
                    'can_create_employees' => $admin->can_create_employees,
                    'can_manage_accounts' => $admin->can_manage_accounts,
                    'authority_granted_at' => $admin->authority_granted_at->format('M d, Y H:i A'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Revoke authority from branch admin
     */
    public function revokeAdminAuthority($adminId)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $admin = AdminProfile::findOrFail($adminId);
            
            $admin->update([
                'can_create_employees' => false,
                'can_manage_accounts' => false,
                'authority_granted_by' => null,
                'authority_granted_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Authority revoked from {$admin->full_name}",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Grant authority to finance officer
     */
    public function grantFinanceAuthority(Request $request, $financeId)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'can_create_employees' => 'boolean',
            'can_manage_accounts' => 'boolean',
        ]);

        try {
            $finance = FinanceProfile::findOrFail($financeId);
            
            $finance->update([
                'can_create_employees' => $request->can_create_employees ?? false,
                'can_manage_accounts' => $request->can_manage_accounts ?? false,
                'authority_granted_by' => $user->id,
                'authority_granted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Authority granted to {$finance->full_name}",
                'finance' => [
                    'id' => $finance->id,
                    'name' => $finance->full_name,
                    'can_create_employees' => $finance->can_create_employees,
                    'can_manage_accounts' => $finance->can_manage_accounts,
                    'authority_granted_at' => $finance->authority_granted_at->format('M d, Y H:i A'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Revoke authority from finance officer
     */
    public function revokeFinanceAuthority($financeId)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $finance = FinanceProfile::findOrFail($financeId);
            
            $finance->update([
                'can_create_employees' => false,
                'can_manage_accounts' => false,
                'authority_granted_by' => null,
                'authority_granted_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Authority revoked from {$finance->full_name}",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get authority summary for API
     */
    public function getSummary()
    {
        $user = Auth::user();
        
        $adminCount = AdminProfile::where('can_create_employees', true)->count();
        $financeCount = FinanceProfile::where('can_create_employees', true)->count();

        return response()->json([
            'admins_with_authority' => $adminCount,
            'finance_with_authority' => $financeCount,
            'total_authorized' => $adminCount + $financeCount,
        ]);
    }
}
