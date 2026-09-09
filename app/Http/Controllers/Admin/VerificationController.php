<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\AccountCredentials;
use App\Mail\EmployeeStatusChanged;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    private function checkVerificationPermission()
    {
        $user = Auth::user();
        
        // Super Admin always has permission
        if ($user->role === 'admin' && $user->admin_type === 'super_admin') {
            return true;
        }
        
        // Regular admin needs can_verify_ids permission granted by super admin
        if ($user->role === 'admin') {
            $adminProfile = $user->getAdminProfile();
            if ($adminProfile && $adminProfile->can_verify_ids) {
                return true;
            }
        }
        
        abort(403, 'You do not have permission to verify IDs');
    }
    
    public function index()
    {
        $this->checkVerificationPermission();
        
        $currentUser = Auth::user();
        $query = User::query();
        
        // Filter by branch for non-super-admins
        if ($currentUser->role === 'admin' && $currentUser->admin_type !== 'super_admin') {
            $adminProfile = $currentUser->getAdminProfile();
            if ($adminProfile && $adminProfile->branch_id) {
                $query->where('branch_id', $adminProfile->branch_id);
            }
        }
        
        $pendingUsers = $query->where('id_verification_status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
        
        $rejectedQuery = User::query();
        if ($currentUser->role === 'admin' && $currentUser->admin_type !== 'super_admin') {
            $adminProfile = $currentUser->getAdminProfile();
            if ($adminProfile && $adminProfile->branch_id) {
                $rejectedQuery->where('branch_id', $adminProfile->branch_id);
            }
        }
        
        $rejectedUsers = $rejectedQuery->where('id_verification_status', 'rejected')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        $deactivatedQuery = User::query();
        if ($currentUser->role === 'admin' && $currentUser->admin_type !== 'super_admin') {
            $adminProfile = $currentUser->getAdminProfile();
            if ($adminProfile && $adminProfile->branch_id) {
                $deactivatedQuery->where('branch_id', $adminProfile->branch_id);
            }
        }
        
        $deactivatedUsers = $deactivatedQuery->where('is_active', false)
            ->where('id_verification_status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $pendingStatusChanges = EmployeeProfile::with(['user', 'branch'])
            ->whereNotNull('pending_status')
            ->where('pending_status', '!=', 'status')
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->when($currentUser->admin_type !== 'super_admin', function ($q) use ($currentUser) {
                $branchId = $currentUser->getAdminProfile()?->branch_id;
                $q->where('branch_id', $branchId);
            })
            ->latest('status_change_requested_at')
            ->get();
        
        // Load profiles
        foreach ($pendingUsers as $user) {
            $this->loadProfile($user);
        }
        foreach ($rejectedUsers as $user) {
            $this->loadProfile($user);
        }
        foreach ($deactivatedUsers as $user) {
            $this->loadProfile($user);
        }
        
        return view('admin.verifications', compact('pendingUsers', 'rejectedUsers', 'deactivatedUsers', 'pendingStatusChanges'));
    }
    
    private function loadProfile($user)
    {
        if ($user->role === 'employee') {
            $user->profile = EmployeeProfile::where('user_id', $user->id)->first();
        } elseif ($user->role === 'finance_officer') {
            $user->profile = FinanceProfile::where('user_id', $user->id)->first();
        } else {
            $user->profile = AdminProfile::where('user_id', $user->id)->first();
        }
    }
    
    private function authorizeBranchAccess($userId)
    {
        $currentUser = Auth::user();
        $user = User::findOrFail($userId);
        
        // Super admin can approve anyone
        if ($currentUser->role === 'admin' && $currentUser->admin_type === 'super_admin') {
            return $user;
        }
        
        // Non-super admin can only approve users from their branch
        if ($currentUser->role === 'admin') {
            $adminProfile = $currentUser->getAdminProfile();
            if (!$adminProfile || !$adminProfile->branch_id || $user->branch_id !== $adminProfile->branch_id) {
                abort(403, 'You can only verify users from your branch');
            }
        }
        
        return $user;
    }
    
    public function approve($userId)
    {
        $this->checkVerificationPermission();
        
        $user = $this->authorizeBranchAccess($userId);

        $loginPassword = $user->pending_password ?: $this->generatePassword();

        $user->id_verification_status = 'approved';
        $user->is_active = true;
        $user->is_verified = true;
        $user->email_verified_at = now();
        $user->pending_password = null;
        $user->save();

        $emailSent = false;

        try {
            Mail::to($user->email)->send(new AccountCredentials($user, $loginPassword, $user->role));
            $emailSent = true;
        } catch (\Exception $mailException) {
            \Log::error('Failed to send approved employee credentials email: ' . $mailException->getMessage());
        }

        if ($emailSent) {
            return redirect()->back()->with('success', 'Registration approved for ' . $user->name . '. Login credentials have been sent to the employee email.');
        }

        return redirect()->back()->with('error', 'Registration approved for ' . $user->name . ', but the email could not be sent. Please verify the Gmail SMTP App Password and mail settings.');
    }

    private function checkSuperAdmin()
    {
        if (Auth::user()->role !== 'admin' || Auth::user()->admin_type !== 'super_admin') {
            abort(403, 'Only the System Administrator can approve status changes.');
        }
    }

    public function approveStatusChange($employeeId)
    {
        $this->checkSuperAdmin();

        $employee = EmployeeProfile::with('user')->findOrFail($employeeId);
        if (!$employee->pending_status) {
            return back()->with('error', 'There is no pending status change for this employee.');
        }

        $previousStatus = $employee->status ?? 'New Hire';
        $newStatus = $employee->pending_status;
        $employee->update([
            'status' => $newStatus,
            'pending_status' => null,
            'status_change_approved_by' => Auth::id(),
            'status_change_approved_at' => now(),
            'status_change_rejection_reason' => null,
        ]);

        app(\App\Http\Controllers\LeaveController::class)->refreshLeaveBalanceForProfile($employee);

        $emailSent = false;
        try {
            if ($employee->user?->email) {
                Mail::to($employee->user->email)->send(new EmployeeStatusChanged($employee, $previousStatus, $newStatus));
                $emailSent = true;
            }
        } catch (\Throwable $mailException) {
            \Log::warning('Failed to send employee status change email: ' . $mailException->getMessage());
        }

        $message = "Status change approved for {$employee->user->name}.";
        if ($emailSent) {
            $message .= ' The employee was notified by email.';
        } else {
            $message .= ' However, the status email could not be sent.';
        }

        return back()->with($emailSent ? 'success' : 'error', $message);
    }

    public function rejectStatusChange(Request $request, $employeeId)
    {
        $this->checkSuperAdmin();

        $request->validate(['reason' => 'required|string|min:5']);
        $employee = EmployeeProfile::findOrFail($employeeId);
        $employee->update([
            'pending_status' => null,
            'status_change_rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'The employee status change request was rejected.');
    }

    private function generatePassword()
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }
    
    public function reject($userId, Request $request)
    {
        $this->checkVerificationPermission();
        
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);
        
        $user = $this->authorizeBranchAccess($userId);
        $user->id_verification_status = 'rejected';
        $user->rejection_reason = $request->reason;
        $user->is_active = false;
        $user->save();
        
        return redirect()->back()->with('success', 'Registration rejected for ' . $user->name);
    }

    public function document($userId)
    {
        $this->checkVerificationPermission();

        $user = $this->authorizeBranchAccess($userId);

        if (!$user->id_document_path || !Storage::disk('public')->exists($user->id_document_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($user->id_document_path));
    }
    
    public function reactivate($userId)
    {
        $this->checkVerificationPermission();
        
        $user = $this->authorizeBranchAccess($userId);
        $user->is_active = true;
        $user->save();
        
        return redirect()->back()->with('success', 'Account reactivated for ' . $user->name);
    }
    
    public function permanentDelete($userId)
    {
        $this->checkVerificationPermission();
        
        $user = $this->authorizeBranchAccess($userId);
        
        if ($user->role === 'employee') {
            EmployeeProfile::where('user_id', $user->id)->delete();
        } elseif ($user->role === 'finance_officer') {
            FinanceProfile::where('user_id', $user->id)->delete();
        } else {
            AdminProfile::where('user_id', $user->id)->delete();
        }
        
        $user->delete();
        
        return redirect()->back()->with('success', 'User permanently deleted');
    }
}
