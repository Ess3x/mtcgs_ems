<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\AccountCredentials;
use App\Mail\FinanceOfficerChangePendingApproval;

class UserManagementController extends Controller
{
    public function index()
    {
        // Only Super Admin can access User Management
        if (Auth::user()->role !== 'admin' || Auth::user()->admin_type !== 'super_admin') {
            abort(403, 'Only Super Admin can manage users');
        }
        
        $employees = [];
        $financeOfficers = [];
        $admins = [];
        $activeCount = 0;
        $inactiveCount = 0;
        
        $allUsers = User::all();
        
        foreach ($allUsers as $user) {
            // Count active/inactive (approved accounts only)
            if ($user->id_verification_status === 'approved') {
                if ($user->is_active == 1) {
                    $activeCount++;
                } else {
                    $inactiveCount++;
                }
            }
            
            if ($user->role === 'employee') {
                $profile = EmployeeProfile::where('user_id', $user->id)->first();
                if ($profile) {
                    $employees[] = [
                        'profile_id' => $profile->id,
                        'name' => $profile->first_name . ' ' . $profile->last_name,
                        'email' => $user->email,
                        'position' => $profile->position,
                        'branch' => optional($profile->branch)->branch_name ?? 'N/A',
                        'is_active' => $user->is_active,
                    ];
                }
            }
            elseif (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
                $profile = FinanceProfile::where('user_id', $user->id)->first();
                if ($profile) {
                    $financeOfficers[] = [
                        'profile_id' => $profile->id,
                        'name' => $profile->first_name . ' ' . $profile->last_name,
                        'email' => $user->email,
                        'position' => $profile->position,
                        'is_active' => $user->is_active,
                    ];
                }
            }
            elseif ($user->role === 'admin') {
                $profile = AdminProfile::where('user_id', $user->id)->orderBy('id')->first();
                if ($profile) {
                    $admins[] = [
                        'profile_id' => $profile->id,
                        'name' => $profile->first_name . ' ' . $profile->last_name,
                        'email' => $user->email,
                        'position' => $profile->position,
                        'is_active' => $user->is_active,
                    ];
                }
            }
        }
        
        $totalUsers = count($employees) + count($financeOfficers) + count($admins);
        
        $pendingFinanceChanges = FinanceProfile::with(['user', 'branch'])
            ->whereNotNull('pending_changes')
            ->latest('changes_requested_at')
            ->get();

        return view('admin.user-management', compact(
            'employees', 'financeOfficers', 'admins',
            'activeCount', 'inactiveCount', 'totalUsers', 'pendingFinanceChanges'
        ));
    }
    
    public function edit($role, $id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'admin' || ($currentUser->admin_type !== 'super_admin' && $role !== 'finance')) {
            abort(403);
        }
        
        if ($role === 'employee') {
            $userData = EmployeeProfile::with('user', 'branch')->findOrFail($id);
        } elseif ($role === 'finance') {
            $userData = FinanceProfile::with('user')->findOrFail($id);
            if ($currentUser->admin_type === 'branch_admin' && $userData->branch_id !== $currentUser->getAdminProfile()?->branch_id) {
                abort(403, 'You can only edit Finance Officers in your branch.');
            }
        } else {
            $userData = AdminProfile::with('user')->findOrFail($id);
        }
        
        $branches = \App\Models\Branch::all();
        
        return view('admin.user-edit', compact('userData', 'role', 'branches'));
    }
    
    public function update(Request $request, $role, $id)
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== 'admin' || ($currentUser->admin_type !== 'super_admin' && $role !== 'finance')) {
            abort(403);
        }

        if ($role === 'finance') {
            $request->validate([
                'status' => 'nullable|in:New Hire,Regular,1-2 Years in Service,3+ Years of Service',
                'date_hired' => 'nullable|date',
                'fingerprint_data' => 'nullable|string',
            ]);
        }
        
        $user = null;
        if ($role === 'employee') {
            $profile = EmployeeProfile::findOrFail($id);
            $user = $profile->user;
            $this->validateEmail($request, $user);
            $profile->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'position' => $request->position,
                'basic_salary' => $request->basic_salary,
                'branch_id' => $request->branch_id,
            ]);
            if ($profile->user) {
                $profile->user->update([
                    'email' => $request->email,
                    'is_active' => $request->has('is_active'),
                    'name' => $request->first_name . ' ' . $request->last_name,
                ]);
            }
        } elseif ($role === 'finance') {
            $profile = FinanceProfile::findOrFail($id);
            if ($currentUser->admin_type === 'branch_admin' && $profile->branch_id !== $currentUser->getAdminProfile()?->branch_id) {
                abort(403, 'You can only edit Finance Officers in your branch.');
            }
            $user = $profile->user;
            $this->validateEmail($request, $user);

            $fingerprintTemplate = $request->input('fingerprint_data');
            $fingerprintTemplate = is_string($fingerprintTemplate)
                ? preg_replace('/\s+/', '', trim($fingerprintTemplate))
                : null;

            $jsonSafeFingerprint = null;
            if (is_string($fingerprintTemplate) && $fingerprintTemplate !== '') {
                $normalizedFingerprint = preg_replace('/\s+/', '', trim($fingerprintTemplate));
                $decoded = base64_decode($normalizedFingerprint, true);

                if ($decoded !== false && base64_encode($decoded) === $normalizedFingerprint) {
                    $fingerprintTemplate = $decoded;
                    $jsonSafeFingerprint = base64_encode($decoded);
                } else {
                    $cleanFingerprint = @iconv('UTF-8', 'UTF-8//IGNORE', $normalizedFingerprint);
                    $fingerprintTemplate = $cleanFingerprint !== false ? $cleanFingerprint : $normalizedFingerprint;
                    $jsonSafeFingerprint = base64_encode($fingerprintTemplate);
                }
            }

            $isFingerprintRegistered = is_string($fingerprintTemplate) && $fingerprintTemplate !== '' && strtolower(trim((string) $fingerprintTemplate)) !== 'null';

            $financeChanges = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'position' => $request->position,
                'email' => strtolower(trim($request->email)),
                'can_process_payroll' => $request->has('can_process_payroll'),
                'status' => $request->status ?? $profile->status,
                'date_hired' => $request->date_hired ? $request->date_hired : $profile->date_hired,
                'fingerprint_template' => $jsonSafeFingerprint ?? $fingerprintTemplate,
                'is_fingerprint_registered' => $isFingerprintRegistered,
            ];

            if ($currentUser->admin_type === 'branch_admin') {
                $profile->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'position' => $request->position,
                    'can_process_payroll' => $request->has('can_process_payroll'),
                    'status' => $request->status ?? $profile->status,
                    'date_hired' => $request->date_hired ? $request->date_hired : $profile->date_hired,
                    'fingerprint_template' => $jsonSafeFingerprint ?? $fingerprintTemplate,
                    'is_fingerprint_registered' => $isFingerprintRegistered,
                    'pending_changes' => $financeChanges,
                    'changes_requested_by' => $currentUser->id,
                    'changes_requested_at' => now(),
                ]);

                if ($user) {
                    $profile->user->update([
                        'email' => strtolower(trim($request->email)),
                        'is_active' => $request->has('is_active'),
                        'name' => $request->first_name . ' ' . $request->last_name,
                    ]);
                }

                $superAdmins = collect(User::where('role', 'admin')
                    ->where('admin_type', 'super_admin')
                    ->whereNotNull('email')
                    ->pluck('email'))
                    ->filter()
                    ->values()
                    ->all();

                if (!empty($superAdmins)) {
                    try {
                        Mail::to($superAdmins)->send(new FinanceOfficerChangePendingApproval($profile->fresh(), $currentUser, $financeChanges));
                    } catch (\Throwable $mailException) {
                        \Log::error('Failed to notify System Administrator about pending Finance Officer changes.', [
                            'finance_profile_id' => $profile->id,
                            'recipients' => $superAdmins,
                            'error' => $mailException->getMessage(),
                        ]);
                    }
                }

                return redirect()->route('admin.employees', ['finance' => 1])
                    ->with('success', 'Finance Officer updated and submitted to the System Administrator for approval.');
            }

            $profile->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'position' => $request->position,
                'can_process_payroll' => $request->has('can_process_payroll'),
                'status' => $request->status ?? $profile->status,
                'date_hired' => $request->date_hired ? $request->date_hired : $profile->date_hired,
                'fingerprint_template' => $fingerprintTemplate,
                'is_fingerprint_registered' => $isFingerprintRegistered,
            ]);
            if ($user) {
                $profile->user->update([
                    'email' => $request->email,
                    'is_active' => $request->has('is_active'),
                    'name' => $request->first_name . ' ' . $request->last_name,
                ]);
            }
        } else {
            $profile = AdminProfile::findOrFail($id);
            $user = $profile->user;
            $this->validateEmail($request, $user);
            $newEmail = strtolower(trim($request->email));
            $emailChanged = $user && strtolower(trim($user->email)) !== $newEmail;
            $newPassword = null;

            if (!$user) {
                return back()->with('error', 'The administrator account is not linked to a user account.');
            }

            $profile->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'position' => $request->position,
                'admin_level' => $request->admin_level,
            ]);
            if ($user->admin_type === 'branch_admin' && $emailChanged) {
                $newPassword = $this->generatePassword();
            }

            $userUpdates = [
                'email' => $newEmail,
                'is_active' => $request->has('is_active'),
                'name' => $request->first_name . ' ' . $request->last_name,
            ];

            if ($newPassword !== null) {
                $userUpdates['password'] = Hash::make($newPassword);
                $userUpdates['pending_password'] = null;
            }

            $user->forceFill($userUpdates);
            $user->save();

            if ($emailChanged && $user->admin_type === 'branch_admin') {
                try {
                    Mail::to($newEmail)->send(new AccountCredentials($user->fresh(), $newPassword, 'branch_admin'));
                } catch (\Throwable $mailException) {
                    \Log::error('Failed to send updated Branch Head credentials email.', [
                        'user_id' => $user->id,
                        'recipient' => $newEmail,
                        'error' => $mailException->getMessage(),
                    ]);
                    return redirect()->route('admin.user-management')->with('error', 'Email and password were updated, but Gmail rejected the credentials email. Check the mail settings/log.');
                }
            }
        }
        
        $message = $role === 'admin' && isset($emailChanged) && $emailChanged && $user?->admin_type === 'branch_admin'
            ? 'Branch Head updated. New login credentials were sent to the new Gmail address.'
            : 'User updated successfully';

        return redirect()->route('admin.user-management')->with('success', $message);
    }

    public function approveFinanceChanges($id)
    {
        if (Auth::user()->role !== 'admin' || Auth::user()->admin_type !== 'super_admin') {
            abort(403, 'Only the System Administrator can approve Finance Officer changes.');
        }

        $profile = FinanceProfile::with('user')->findOrFail($id);
        $changes = $profile->pending_changes;
        if (!$changes) {
            return back()->with('error', 'No pending Finance Officer changes found.');
        }

        $email = $changes['email'] ?? $profile->user?->email;
        $approvedStatus = $changes['status'] ?? $profile->status;
        $approvedDateHired = $changes['date_hired'] ?? $profile->date_hired;
        $approvedFingerprint = $changes['fingerprint_template'] ?? null;

        if (is_string($approvedFingerprint) && $approvedFingerprint !== '') {
            $decodedFingerprint = base64_decode($approvedFingerprint, true);
            $approvedFingerprint = $decodedFingerprint !== false ? $decodedFingerprint : $approvedFingerprint;
        }

        $profile->update([
            'first_name' => $changes['first_name'],
            'last_name' => $changes['last_name'],
            'position' => $changes['position'],
            'can_process_payroll' => $changes['can_process_payroll'] ?? false,
            'status' => $approvedStatus,
            'date_hired' => $approvedDateHired,
            'fingerprint_template' => $approvedFingerprint ?? $profile->fingerprint_template,
            'is_fingerprint_registered' => $changes['is_fingerprint_registered'] ?? $profile->is_fingerprint_registered,
            'pending_changes' => null,
            'changes_requested_by' => null,
            'changes_requested_at' => null,
        ]);

        if ($profile->employeeProfile) {
            $profile->employeeProfile->update([
                'first_name' => $changes['first_name'],
                'last_name' => $changes['last_name'],
                'position' => $changes['position'],
                'status' => $approvedStatus,
                'date_hired' => $approvedDateHired,
                'branch_id' => $profile->branch_id,
            ]);

            app(\App\Http\Controllers\LeaveController::class)->refreshLeaveBalanceForProfile($profile->employeeProfile);
        }

        $profile->user?->update([
            'name' => $changes['first_name'] . ' ' . $changes['last_name'],
            'email' => $email,
        ]);

        return back()->with('success', 'Finance Officer changes approved and applied.');
    }

    public function rejectFinanceChanges($id)
    {
        if (Auth::user()->role !== 'admin' || Auth::user()->admin_type !== 'super_admin') {
            abort(403, 'Only the System Administrator can reject Finance Officer changes.');
        }

        FinanceProfile::findOrFail($id)->update([
            'pending_changes' => null,
            'changes_requested_by' => null,
            'changes_requested_at' => null,
        ]);

        return back()->with('success', 'Finance Officer changes rejected.');
    }

    private function validateEmail(Request $request, ?User $user): void
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . ($user?->id ?? 'NULL'),
        ]);
    }

    private function generatePassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        $password = '';

        for ($index = 0; $index < 10; $index++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }
}
