<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function checkSuperAdmin()
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            abort(403, 'Only Super Admin can manage admin permissions');
        }
    }

    public function index()
    {
        $this->checkSuperAdmin();

        $admins = AdminProfile::with('user')
            ->whereHas('user', function($q) {
                $q->where('role', 'admin')
                  ->where('admin_type', '!=', 'super_admin');
            })
            ->get();

        return view('admin.permissions.index', compact('admins'));
    }

    public function toggleVerifyPermission($adminId, Request $request)
    {
        $this->checkSuperAdmin();

        $admin = AdminProfile::findOrFail($adminId);
        
        // Only allow toggling for non-super-admin admins
        if ($admin->user->admin_type === 'super_admin') {
            return back()->with('error', 'Cannot modify super admin permissions');
        }

        $admin->can_verify_ids = !$admin->can_verify_ids;
        $admin->save();

        $status = $admin->can_verify_ids ? 'enabled' : 'disabled';
        return back()->with('success', "ID verification permission {$status} for {$admin->user->name}");
    }

    public function grantVerifyPermission($adminId)
    {
        $this->checkSuperAdmin();

        $admin = AdminProfile::findOrFail($adminId);
        
        if ($admin->user->admin_type === 'super_admin') {
            return back()->with('error', 'Cannot modify super admin permissions');
        }

        $admin->can_verify_ids = true;
        $admin->save();

        return back()->with('success', "ID verification permission granted to {$admin->user->name}");
    }

    public function revokeVerifyPermission($adminId)
    {
        $this->checkSuperAdmin();

        $admin = AdminProfile::findOrFail($adminId);
        
        if ($admin->user->admin_type === 'super_admin') {
            return back()->with('error', 'Cannot modify super admin permissions');
        }

        $admin->can_verify_ids = false;
        $admin->save();

        return back()->with('success', "ID verification permission revoked from {$admin->user->name}");
    }
}
