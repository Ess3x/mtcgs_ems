<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountCredentials;
use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BranchHeadController extends Controller
{
    private function authorizeBranchHeadManagement(): void
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            abort(403, 'Only super administrators can manage branch heads.');
        }
    }

    public function index()
    {
        $this->authorizeBranchHeadManagement();

        $branchHeads = AdminProfile::with('user', 'branch')
            ->where(function ($query) {
                $query->where('admin_level', 'branch_admin')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('role', 'admin')
                            ->where('admin_type', 'branch_admin');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $branches = Branch::orderBy('branch_name')->get();

        $totalBranchHeads = AdminProfile::where(function ($query) {
            $query->where('admin_level', 'branch_admin')
                ->orWhereHas('user', function ($userQuery) {
                    $userQuery->where('role', 'admin')
                        ->where('admin_type', 'branch_admin');
                });
        })->count();

        return view('admin.branch-heads.index', compact('branchHeads', 'branches', 'totalBranchHeads'));
    }

    public function create()
    {
        $this->authorizeBranchHeadManagement();
        
        $branches = Branch::orderBy('branch_name')->get();
        
        return view('admin.branch-heads.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorizeBranchHeadManagement();

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'employee_number' => 'required|string|unique:admin_profiles,employee_number',
            'branch_id' => 'required|exists:branches,id',
            'position' => 'nullable|string|max:255',
            'basic_salary' => 'nullable|numeric|min:0',
            'date_hired' => 'nullable|date',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name' => "{$request->first_name} {$request->last_name}",
            'email' => $request->email,
            'password' => Hash::make($tempPassword),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $request->branch_id,
            'profile_type' => AdminProfile::class,
            'is_active' => true,
            'id_verification_status' => 'approved',
            'is_verified' => true,
        ]);

        $adminProfile = AdminProfile::create([
            'user_id' => $user->id,
            'branch_id' => $request->branch_id,
            'employee_number' => $request->employee_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'position' => $request->position ?? 'Branch Administrator',
            'basic_salary' => $request->input('basic_salary', 0),
            'admin_level' => 'branch_admin',
            'can_verify_ids' => false,
            'can_create_employees' => false,
            'can_manage_accounts' => false,
            'date_hired' => $request->date_hired,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
        ]);

        $user->update([
            'profile_id' => $adminProfile->id,
        ]);

        try {
            Mail::to($user->email)->send(new AccountCredentials($user->fresh(), $tempPassword, 'branch_admin'));
        } catch (\Throwable $mailException) {
            \Log::error('Failed to send Branch Admin account credentials email.', [
                'user_id' => $user->id,
                'recipient' => $user->email,
                'error' => $mailException->getMessage(),
            ]);

            return redirect()->route('admin.branch-heads.index')
                ->with('warning', "Branch admin account for {$user->name} was created, but the credentials email could not be sent to {$user->email}.");
        }

        return redirect()->route('admin.branch-heads.index')
            ->with('success', "Branch admin account for {$user->name} created successfully. Credentials sent to {$user->email}");
    }

    public function show(AdminProfile $branchHead)
    {
        $this->authorizeBranchHeadManagement();

        return view('admin.branch-heads.show', compact('branchHead'));
    }

    public function edit(AdminProfile $branchHead)
    {
        $this->authorizeBranchHeadManagement();

        $branches = Branch::orderBy('branch_name')->get();

        return view('admin.branch-heads.edit', compact('branchHead', 'branches'));
    }

    public function update(Request $request, AdminProfile $branchHead)
    {
        $this->authorizeBranchHeadManagement();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'position' => 'nullable|string|max:255',
            'basic_salary' => 'required|numeric|min:0',
            'date_hired' => 'nullable|date',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'fingerprint_data' => 'nullable|string',
        ]);

        $fingerprintTemplate = $request->input('fingerprint_data');
        $fingerprintTemplate = is_string($fingerprintTemplate)
            ? preg_replace('/\s+/', '', trim($fingerprintTemplate))
            : null;
        $fingerprintTemplate = $fingerprintTemplate
            ? (base64_decode($fingerprintTemplate, true) ?: $fingerprintTemplate)
            : null;
        $isFingerprintRegistered = !empty($fingerprintTemplate) && $fingerprintTemplate !== 'null';

        $branchHead->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'branch_id' => $request->branch_id,
            'position' => $request->position,
            'basic_salary' => $request->basic_salary,
            'date_hired' => $request->date_hired,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'fingerprint_template' => $fingerprintTemplate,
            'is_fingerprint_registered' => $isFingerprintRegistered,
            'admin_level' => 'branch_admin',
        ]);

        $branchHead->user->update([
            'name' => "{$request->first_name} {$request->last_name}",
            'branch_id' => $request->branch_id,
        ]);

        return redirect()->route('admin.branch-heads.index')
            ->with('success', 'Branch admin updated successfully.');
    }

    public function destroy(AdminProfile $branchHead)
    {
        $this->authorizeBranchHeadManagement();

        $user = $branchHead->user;
        $branchHead->delete();
        if ($user) {
            $user->delete();
        }

        return redirect()->route('admin.branch-heads.index')
            ->with('success', 'Branch admin account deleted successfully.');
    }
}
