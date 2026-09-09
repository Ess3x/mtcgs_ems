<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Mail\AccountCredentials;
use App\Mail\EmployeeStatusChangePendingApproval;
use Illuminate\Support\Facades\Mail;

class EmployeeController extends Controller
{
    private function getBranchFilter()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin' && $user->admin_type === 'super_admin') {
            return null; // Super Admin sees all
        }
        
        if (($user->role === 'admin' && $user->admin_type === 'branch_admin') || $user->role === 'branch_head') {
            return $user->getEffectiveBranchId();
        }
        
        return null;
    }
    
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'branch_head') {
            abort(403);
        }
        $branchFilter = $this->getBranchFilter();
        $showFinance = request()->boolean('finance');

        // Super admin: keep role tabs separated so Employee, Finance Officer, and Branch Admin stay in their own lists.
        if (Auth::user()->isSuperAdmin()) {
            $profiles = collect();

            $query = $showFinance
                ? FinanceProfile::with('user', 'branch')
                    ->whereHas('user', function($q) {
                        $q->where('is_active', true);
                    })
                : EmployeeProfile::with('user', 'branch')
                    ->whereHas('user', function($q) {
                        $q->where('role', 'employee')
                          ->where('is_active', true);
                    });

            if ($branchFilter) {
                $query->where('branch_id', $branchFilter);
            }
            if (request()->branch_id) {
                $query->where('branch_id', request()->branch_id);
            }

            $records = $query->orderBy('created_at', 'desc')->get();

            foreach ($records as $record) {
                $role = $showFinance ? ($record->user?->role ?? 'finance_officer') : 'employee';
                $profiles->push((object) [
                    'id' => $record->id,
                    'employee_number' => $record->employee_number,
                    'first_name' => $record->first_name,
                    'last_name' => $record->last_name,
                    'position' => $record->position,
                    'basic_salary' => $record->basic_salary,
                    'branch' => $record->branch,
                    'user' => $record->user,
                    'role' => $role,
                    'status' => $record->status ?? 'New Hire',
                    'profile_type' => $showFinance ? 'finance' : 'employee',
                    'is_fingerprint_registered' => $record->is_fingerprint_registered,
                    'fingerprint_template' => $record->fingerprint_template,
                    'created_at' => $record->created_at,
                ]);
            }

            $page = request()->get('page', 1);
            $perPage = 15;
            $employees = new \Illuminate\Pagination\Paginator(
                $profiles->forPage($page, $perPage)->values(),
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );

        } else {
            // BRANCH ADMIN: EMPLOYEES LANG from their branch only!
            if (!$branchFilter) {
                abort(403, 'Branch admin must have a branch assigned.');
            }
            
            if ($showFinance) {
                $financeProfiles = FinanceProfile::with('user', 'branch')
                    ->where('branch_id', $branchFilter)
                    ->whereHas('user', function ($q) {
                        $q->where('is_active', true);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);

                $employees = $financeProfiles->map(function ($finance) {
                    return (object) [
                        'id' => $finance->id,
                        'employee_number' => $finance->employee_number,
                        'first_name' => $finance->first_name,
                        'last_name' => $finance->last_name,
                        'position' => $finance->position,
                        'basic_salary' => $finance->basic_salary,
                        'branch' => $finance->branch,
                        'user' => $finance->user,
                        'role' => $finance->user?->role ?? 'finance_officer',
                        'status' => $finance->status ?? 'New Hire',
                        'is_fingerprint_registered' => $finance->is_fingerprint_registered,
                    ];
                });

                // Convert mapped collection to paginated result without calling query() on a Collection.
                $employees = new \Illuminate\Pagination\Paginator(
                    $employees->values()->all(),
                    $financeProfiles->perPage(),
                    $financeProfiles->currentPage(),
                    [
                        'path' => $financeProfiles->path(),
                        'query' => request()->query(),
                    ]
                );

                $branches = Branch::all();
                $selectedBranch = $branchFilter;
                
                // Stats: Filter by branch for branch admins
                $totalEmployees = EmployeeProfile::whereHas('user', function ($q) {
                    $q->where('role', 'employee')->where('is_active', true);
                });
                if ($branchFilter) {
                    $totalEmployees = $totalEmployees->where('branch_id', $branchFilter);
                }
                $totalEmployees = $totalEmployees->count();
                
                $totalFinance = FinanceProfile::whereHas('user', function ($q) {
                    $q->where('is_active', true);
                });
                if ($branchFilter) {
                    $totalFinance = $totalFinance->where('branch_id', $branchFilter);
                }
                $totalFinance = $totalFinance->count();
                
                $totalAdmins = AdminProfile::whereHas('user', function ($q) {
                    $q->where('is_active', true);
                });
                if ($branchFilter) {
                    $totalAdmins = $totalAdmins->where('branch_id', $branchFilter);
                }
                $totalAdmins = $totalAdmins->count();

                return view('admin.employees', compact('employees', 'branches', 'selectedBranch', 'totalEmployees', 'totalFinance', 'totalAdmins', 'showFinance'));
            }

            $query = EmployeeProfile::with('user', 'branch')
                ->whereHas('user', function($q) {
                    $q->where('role', 'employee')
                      ->where('is_active', true);  // Only active employees
                })
                ->where('branch_id', $branchFilter);  // ENFORCE strict branch isolation

            // Branch admins cannot override - they can only see their own branch
            // Ignore request()->branch_id for branch admins
            if (!$branchFilter && request()->branch_id) {
                // This shouldn't happen for branch admins, but just in case
                $query->where('branch_id', request()->branch_id);
            }

            $employees = $query->orderBy('created_at', 'desc')->paginate(15);
        }
        $branches = Branch::all();
        $selectedBranch = request()->branch_id ?? $branchFilter;
        
        // Stats: Filter by branch for non-super-admins
        $statsQuery = function() use ($branchFilter) {
            if ($branchFilter) {
                return $branchFilter;
            }
            return null;
        };
        
        $totalEmployees = EmployeeProfile::whereHas('user', function($q) {
            $q->where('role', 'employee')->where('is_active', true);
        });
        if ($branchFilter) {
            $totalEmployees = $totalEmployees->where('branch_id', $branchFilter);
        }
        $totalEmployees = $totalEmployees->count();
        
        $totalFinance = FinanceProfile::whereHas('user', function($q) {
            $q->where('is_active', true);
        });
        if ($branchFilter) {
            $totalFinance = $totalFinance->where('branch_id', $branchFilter);
        }
        $totalFinance = $totalFinance->count();
        
        $totalAdmins = AdminProfile::whereHas('user', function($q) {
            $q->where('is_active', true);
        });
        if ($branchFilter) {
            $totalAdmins = $totalAdmins->where('branch_id', $branchFilter);
        }
        $totalAdmins = $totalAdmins->count();
        
        return view('admin.employees', compact('employees', 'branches', 'selectedBranch', 'totalEmployees', 'totalFinance', 'totalAdmins', 'showFinance'));
    }

    public function archives()
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'branch_head') {
            abort(403);
        }
        $branchFilter = $this->getBranchFilter();

        // If super admin, show all archived staff
        if (Auth::user()->isSuperAdmin()) {
            $profiles = collect();

            $empQuery = EmployeeProfile::with('user', 'branch')
                ->whereHas('user', function($q) {
                    $q->where('role', 'employee')
                      ->where('is_active', false);  // INACTIVE/ARCHIVED only
                });
            $finQuery = FinanceProfile::with('user', 'branch')
                ->whereHas('user', function($q) {
                    $q->where('is_active', false);  // INACTIVE/ARCHIVED only
                });
            $admQuery = AdminProfile::with('user', 'branch')
                ->whereHas('user', function($q) {
                    $q->where('is_active', false);  // INACTIVE/ARCHIVED only
                });

            if ($branchFilter) {
                $empQuery->where('branch_id', $branchFilter);
                $finQuery->where('branch_id', $branchFilter);
                $admQuery->where('branch_id', $branchFilter);
            }
            if (request()->branch_id) {
                $empQuery->where('branch_id', request()->branch_id);
                $finQuery->where('branch_id', request()->branch_id);
                $admQuery->where('branch_id', request()->branch_id);
            }

            $emps = $empQuery->get();
            $fins = $finQuery->get();
            $adms = $admQuery->get();

            foreach ($emps as $e) {
                $obj = (object) [
                    'id' => $e->id,
                    'employee_number' => $e->employee_number,
                    'first_name' => $e->first_name,
                    'last_name' => $e->last_name,
                    'position' => $e->position,
                    'basic_salary' => $e->basic_salary,
                    'branch' => $e->branch,
                    'user' => $e->user,
                    'role' => 'employee',
                    'is_fingerprint_registered' => $e->is_fingerprint_registered,
                    'fingerprint_template' => $e->fingerprint_template,
                    'created_at' => $e->created_at,
                ];
                $profiles->push($obj);
            }

            foreach ($fins as $f) {
                $obj = (object) [
                    'id' => $f->id,
                    'employee_number' => $f->employee_number,
                    'first_name' => $f->first_name,
                    'last_name' => $f->last_name,
                    'position' => $f->position,
                    'basic_salary' => $f->basic_salary,
                    'branch' => $f->branch,
                    'user' => $f->user,
                    'role' => 'finance_officer',
                    'is_fingerprint_registered' => $f->is_fingerprint_registered,
                    'fingerprint_template' => $f->fingerprint_template,
                    'created_at' => $f->created_at,
                ];
                $profiles->push($obj);
            }

            foreach ($adms as $a) {
                $obj = (object) [
                    'id' => $a->id,
                    'employee_number' => $a->employee_number,
                    'first_name' => $a->first_name,
                    'last_name' => $a->last_name,
                    'position' => $a->position,
                    'basic_salary' => 0,
                    'branch' => $a->branch,
                    'user' => $a->user,
                    'role' => 'admin',
                    'is_fingerprint_registered' => $a->is_fingerprint_registered,
                    'fingerprint_template' => $a->fingerprint_template,
                    'created_at' => $a->created_at,
                ];
                $profiles->push($obj);
            }

            // Sort by created_at desc
            $profiles = $profiles->sortByDesc('created_at')->values();
            $employees = $profiles;

        } else {
            // BRANCH ADMIN: ARCHIVED EMPLOYEES from their branch only!
            if (!$branchFilter) {
                abort(403, 'Branch admin must have a branch assigned.');
            }
            
            $query = EmployeeProfile::with('user', 'branch')
                ->whereHas('user', function($q) {
                    $q->where('role', 'employee')
                      ->where('is_active', false);  // ARCHIVED/INACTIVE only
                })
                ->where('branch_id', $branchFilter);  // ENFORCE strict branch isolation

            $employees = $query->orderBy('created_at', 'desc')->paginate(15);
        }

        $branches = Branch::all();
        $selectedBranch = request()->branch_id ?? $branchFilter;
        
        // Stats: Count archived employees - filter by branch for non-super-admins
        $totalEmployees = EmployeeProfile::whereHas('user', function($q) {
            $q->where('role', 'employee')->where('is_active', false);
        });
        if ($branchFilter) {
            $totalEmployees = $totalEmployees->where('branch_id', $branchFilter);
        }
        $totalEmployees = $totalEmployees->count();
        
        $totalFinance = FinanceProfile::whereHas('user', function($q) {
            $q->where('is_active', false);
        });
        if ($branchFilter) {
            $totalFinance = $totalFinance->where('branch_id', $branchFilter);
        }
        $totalFinance = $totalFinance->count();
        
        $totalAdmins = AdminProfile::whereHas('user', function($q) {
            $q->where('is_active', false);
        });
        if ($branchFilter) {
            $totalAdmins = $totalAdmins->where('branch_id', $branchFilter);
        }
        $totalAdmins = $totalAdmins->count();
        
        return view('admin.employees-archives', compact('employees', 'branches', 'selectedBranch', 'totalEmployees', 'totalFinance', 'totalAdmins'));
    }
    
    public function create()
    {
        $user = Auth::user();
        
        // Check authorization
        if ($user->role === 'admin' || $user->role === 'branch_head') {
            if ($user->role === 'admin' && ($user->admin_type === 'super_admin' || !$user->admin_type || $user->admin_type === 'admin')) {
                // Super admin can always create; legacy or untyped admin accounts are also allowed
                $branches = Branch::all();
            } else if (($user->role === 'admin' && $user->admin_type === 'branch_admin') || $user->role === 'branch_head') {
                // Branch heads may submit employees from their assigned branch for approval.
                if (!$user->profile || !$user->profile->branch_id) {
                    abort(403, 'Branch admin must have a branch assigned.');
                }
                $branchId = $user->profile->branch_id;
                $branches = Branch::where('id', $branchId)->get();
            } else {
                abort(403, 'Unauthorized');
            }
        } else if ($user->role === 'finance' && $user->profile instanceof FinanceProfile) {
            // Finance officer needs authority
            if (!($user->profile->can_create_employees ?? false)) {
                return back()->with('error', 'You do not have authority to create employees. Please request authority from the Super Admin.');
            }
            $branchId = $user->profile->branch_id;
            $branches = Branch::where('id', $branchId)->get();
        } else {
            abort(403, 'Unauthorized');
        }
        
        return view('admin.employee-create', compact('branches'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Check authorization
        $hasAuthority = false;
        
        if ($user->role === 'admin' || $user->role === 'branch_head') {
            if ($user->role === 'admin' && ($user->admin_type === 'super_admin' || !$user->admin_type || $user->admin_type === 'admin')) {
                $hasAuthority = true;
            } else if (($user->role === 'admin' && $user->admin_type === 'branch_admin' && $user->profile?->branch_id) || ($user->role === 'branch_head' && $user->profile?->branch_id)) {
                // Branch heads can submit accounts; System Administrator approval is still required.
                $hasAuthority = true;
            }
        } else if ($user->role === 'finance' && ($user->profile->can_create_employees ?? false)) {
            $hasAuthority = true;
        }
        
        if (!$hasAuthority) {
            return back()->with('error', 'You do not have authority to create employees.');
        }
        
        $isFinanceHead = $request->role === 'finance_head';
        $isBranchScopedAdmin = ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head');

        if ($isBranchScopedAdmin && $isFinanceHead) {
            return back()->with('error', 'Only the System Administrator can assign the Finance Head role.')->withInput();
        }

        $allowedRoles = $isBranchScopedAdmin ? 'required|in:employee,finance_officer' : 'required|in:employee,finance_officer,finance_head';

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'employee_number' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/'],
            'branch_id' => $isFinanceHead ? 'nullable|exists:branches,id' : 'required|exists:branches,id',
            'position' => 'required',
            'date_hired' => 'required|date',
            'status' => 'required|in:New Hire,Regular,1-2 Years in Service,3+ Years of Service',
            'role' => $allowedRoles,
            'basic_salary' => 'nullable|numeric|min:0',
            'fingerprint_data' => 'nullable|string',
        ]);
        
        if (EmployeeProfile::where('employee_number', $request->employee_number)->exists() || FinanceProfile::where('employee_number', $request->employee_number)->exists()) {
            return back()->withErrors(['employee_number' => 'This employee number is already in use.'])->withInput();
        }
        
        DB::beginTransaction();
        
        try {
            if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
                $allowedBranch = Auth::user()->profile->branch_id;
                if (!$isFinanceHead && $request->branch_id != $allowedBranch) {
                    return back()->with('error', 'You can only add employees to your branch.');
                }
            }
            
            $isActive = $request->has('is_active');
            $fingerprintTemplate = $request->input('fingerprint_data');
            $fingerprintTemplate = is_string($fingerprintTemplate)
                ? preg_replace('/\s+/', '', trim($fingerprintTemplate))
                : null;
            $fingerprintTemplate = $fingerprintTemplate
                ? (base64_decode($fingerprintTemplate, true) ?: $fingerprintTemplate)
                : null;
            $isFingerprintRegistered = !empty($fingerprintTemplate) && $fingerprintTemplate !== 'null';
            $branchId = $request->branch_id;
            $basicSalary = $isFinanceHead ? 0 : ($request->basic_salary ?? 0);
            
            // Generate a temporary password that will only be emailed after system admin approval.
            $pendingPassword = $this->generateRandomPassword();
            
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($pendingPassword),
                'role' => $request->role,
                'branch_id' => $branchId,
                'id_verification_status' => 'pending',
                'is_active' => false,
                'is_verified' => false,
                'email_verified_at' => null,
                'pending_password' => $pendingPassword,
            ]);
            
            if (in_array($request->role, ['finance_officer', 'finance_head'], true)) {
                $profile = FinanceProfile::create([
                    'user_id' => $user->id,
                    'branch_id' => $branchId,
                    'employee_number' => $request->employee_number,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'position' => $request->position,
                    'date_hired' => $request->date_hired,
                    'status' => $request->status,
                    'basic_salary' => $basicSalary,
                    'can_process_payroll' => false,
                    'can_approve_payroll' => false,
                    'fingerprint_template' => $fingerprintTemplate,
                    'is_fingerprint_registered' => $isFingerprintRegistered,
                ]);
                
                $user->profile_id = $profile->id;
                $user->profile_type = FinanceProfile::class;
            } else {
                $profile = EmployeeProfile::create([
                    'user_id' => $user->id,
                    'branch_id' => $request->branch_id,
                    'employee_number' => $request->employee_number,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'position' => $request->position,
                    'basic_salary' => $request->basic_salary ?? 0,
                    'date_hired' => $request->date_hired,
                    'status' => $request->status,
                    'fingerprint_template' => $fingerprintTemplate,
                    'is_fingerprint_registered' => $isFingerprintRegistered,
                ]);
                
                $user->profile_id = $profile->id;
                $user->profile_type = EmployeeProfile::class;
            }
            
            $user->save();
            
            DB::commit();
            
            return redirect()->route('admin.employees')->with('success', 'Employee submitted successfully for System Administrator approval. Login credentials will be sent after approval.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create employee: ' . $e->getMessage());
        }
    }
    
    public function edit($id)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'branch_head') {
            abort(403);
        }
        
        // Try to find as EmployeeProfile first
        $employee = EmployeeProfile::with('user', 'branch')
            ->whereHas('user', function($q) {
                $q->where('role', 'employee');
            })
            ->find($id);
        
        // If not found, try as AdminProfile
        if (!$employee) {
            $admin = AdminProfile::with('user', 'branch')
                ->whereHas('user', function($q) {
                    $q->where('role', 'admin');
                })
                ->find($id);
            
            if ($admin) {
                // Convert AdminProfile to object structure compatible with employee-edit view
                $employee = (object) [
                    'id' => $admin->id,
                    'user_id' => $admin->user_id,
                    'branch_id' => $admin->branch_id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'position' => $admin->position,
                    'basic_salary' => 0,
                    'date_hired' => $admin->date_hired,
                    'status' => 'active',
                    'user' => $admin->user,
                    'branch' => $admin->branch,
                    'is_admin_profile' => true,
                    'profile_id' => $admin->id
                ];
            } else {
                abort(404, 'Account not found');
            }
        }
        
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $allowedBranch = Auth::user()->profile->branch_id;
            if ($employee->branch_id != $allowedBranch) {
                abort(403, 'You can only edit accounts in your branch.');
            }
        }
        
        $branches = Branch::all();
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $branchId = Auth::user()->profile->branch_id;
            $branches = Branch::where('id', $branchId)->get();
        }
        
        return view('admin.employee-edit', compact('employee', 'branches'));
    }
    
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'branch_head') {
            abort(403);
        }

        $isFinanceHead = $request->role === 'finance_head';
        $isBranchScopedAdmin = ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head');

        if ($isBranchScopedAdmin && $isFinanceHead) {
            return back()->with('error', 'Only the System Administrator can assign the Finance Head role.')->withInput();
        }

        // Try to find as EmployeeProfile first
        $employee = EmployeeProfile::find($id);
        $isAdminProfile = false;
        
        // If not found, try as AdminProfile
        if (!$employee) {
            $admin = AdminProfile::find($id);
            if ($admin) {
                $employee = $admin;
                $isAdminProfile = true;
            } else {
                abort(404, 'Account not found');
            }
        }
        
        // STRICT: Branch admins can ONLY update accounts from their branch
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $allowedBranch = Auth::user()->profile->branch_id;
            if ($employee->branch_id != $allowedBranch) {
                abort(403, 'You can only update accounts in your branch.');
            }
            
            // Branch admin also cannot change the branch, except when the role is a branchless Finance Head
            if (!$isFinanceHead && $request->branch_id != $allowedBranch) {
                abort(403, 'You cannot change an account to a different branch.');
            }
        }

        $allowedRoles = $isBranchScopedAdmin ? 'required|in:employee,finance_officer' : 'required|in:employee,finance_officer,finance_head';

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'position' => 'required',
            'branch_id' => $isFinanceHead ? 'nullable|exists:branches,id' : 'required|exists:branches,id',
            'date_hired' => 'required|date',
            'status' => 'required|in:New Hire,Regular,1-2 Years in Service,3+ Years of Service',
            'role' => $allowedRoles,
            'basic_salary' => 'nullable|numeric|min:0',
            'fingerprint_data' => 'nullable|string',
        ]);

        $fingerprintTemplate = $request->input('fingerprint_data');
        $fingerprintTemplate = is_string($fingerprintTemplate)
            ? preg_replace('/\s+/', '', trim($fingerprintTemplate))
            : null;
        $fingerprintTemplate = $fingerprintTemplate
            ? (base64_decode($fingerprintTemplate, true) ?: $fingerprintTemplate)
            : null;
        $hasNewFingerprint = is_string($fingerprintTemplate)
            && $fingerprintTemplate !== ''
            && strtolower(trim($fingerprintTemplate)) !== 'null';
        $fingerprintTemplate = $hasNewFingerprint
            ? $this->appendFingerprintTemplate($employee->fingerprint_template, $fingerprintTemplate)
            : $employee->fingerprint_template;
        $isFingerprintRegistered = $hasNewFingerprint
            ? true
            : (bool) $employee->is_fingerprint_registered;
        $isBranchAdmin = ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head');
        $statusChanged = $request->status !== $employee->status;
            $previousStatus = $employee->status ?? 'New Hire';
        DB::beginTransaction();
        
        try {
            $branchId = $isFinanceHead ? null : $request->branch_id;
            $basicSalary = $isFinanceHead ? 0 : ($request->basic_salary ?? $employee->basic_salary ?? 0);

            $employeeUpdates = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'position' => $request->position,
                'basic_salary' => $basicSalary,
                'branch_id' => $branchId,
                'date_hired' => $request->date_hired,
                'fingerprint_template' => $fingerprintTemplate,
                'is_fingerprint_registered' => $isFingerprintRegistered,
            ];

            if ($isBranchAdmin && $statusChanged) {
                $employeeUpdates += [
                    'pending_status' => $request->status,
                    'status_change_requested_by' => Auth::id(),
                    'status_change_requested_at' => now(),
                    'status_change_approved_by' => null,
                    'status_change_approved_at' => null,
                    'status_change_rejection_reason' => null,
                ];
            } else {
                $employeeUpdates['status'] = $request->status;
                $employeeUpdates['pending_status'] = null;
                $employeeUpdates['status_change_rejection_reason'] = null;
            }

            $employee->update($employeeUpdates);

            if ($isBranchAdmin && $statusChanged) {
                $superAdmins = collect(User::where('role', 'admin')
                    ->where('admin_type', 'super_admin')
                    ->whereNotNull('email')
                    ->pluck('email'))
                    ->filter()
                    ->values()
                    ->all();

                if (!empty($superAdmins)) {
                    try {
                        Mail::to($superAdmins)->send(new EmployeeStatusChangePendingApproval($employee->fresh(), $previousStatus, $request->status, Auth::user()));
                    } catch (\Throwable $mailException) {
                        \Log::error('Failed to notify System Administrator about pending employee status change.', [
                            'employee_profile_id' => $employee->id,
                            'recipients' => $superAdmins,
                            'error' => $mailException->getMessage(),
                        ]);
                    }
                }
            }
            
            if ($employee->user) {
                $userUpdates = [
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'is_active' => $request->has('is_active'),
                    'branch_id' => $branchId,
                ];
                
                if (in_array($request->role, ['finance_officer', 'finance_head'], true) && !in_array($employee->user->role, ['finance_officer', 'finance_head'], true)) {
                    $financeProfile = FinanceProfile::create([
                        'user_id' => $employee->user->id,
                        'branch_id' => $request->branch_id,
                        'employee_profile_id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'position' => $request->position,
                        'date_hired' => $request->date_hired,
                        'status' => $request->status,
                        'basic_salary' => $request->basic_salary ?? $employee->basic_salary,
                        'can_process_payroll' => false,
                        'can_approve_payroll' => false,
                    ]);
                    
                    $userUpdates['role'] = $request->role;
                    $userUpdates['profile_type'] = FinanceProfile::class;
                    $userUpdates['profile_id'] = $financeProfile->id;
                } elseif (in_array($request->role, ['finance_officer', 'finance_head'], true) && in_array($employee->user->role, ['finance_officer', 'finance_head'], true)) {
                    $userUpdates['role'] = $request->role;
                }
                
                $employee->user->update($userUpdates);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update employee: ' . $e->getMessage());
        }
        
        $message = $isBranchAdmin && $statusChanged
            ? 'Employee updated. The status change was submitted to the System Administrator for approval.'
            : 'Employee updated';

        return redirect()->route('admin.employees')->with('success', $message);
    }
    
    public function editAdmin($id)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'branch_head') {
            abort(403);
        }
        
        $admin = AdminProfile::with('user', 'branch')
            ->findOrFail($id);
        
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $allowedBranch = Auth::user()->profile->branch_id;
            if ($admin->branch_id != $allowedBranch) {
                abort(403, 'You can only edit accounts in your branch.');
            }
        }
        
        $branches = Branch::all();
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $branchId = Auth::user()->profile->branch_id;
            $branches = Branch::where('id', $branchId)->get();
        }
        
        // Convert to compatible object
        $employee = (object) [
            'id' => $admin->id,
            'user_id' => $admin->user_id,
            'branch_id' => $admin->branch_id,
            'employee_number' => 'ADMIN-' . str_pad($admin->id, 3, '0', STR_PAD_LEFT),
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'position' => $admin->position ?? 'Administrator',
            'basic_salary' => 0,
            'date_hired' => $admin->date_hired,
            'status' => 'active',
            'user' => $admin->user,
            'branch' => $admin->branch,
            'is_admin_profile' => true,
            'profile_id' => $admin->id
        ];
        
        return view('admin.employee-edit', compact('employee', 'branches'));
    }
    
    public function updateAdmin(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'branch_head') {
            abort(403);
        }

        $admin = AdminProfile::findOrFail($id);
        
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $allowedBranch = Auth::user()->profile->branch_id;
            if ($admin->branch_id != $allowedBranch) {
                abort(403, 'You can only update accounts in your branch.');
            }
        }

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'position' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'date_hired' => 'required|date',
            'fingerprint_data' => 'nullable|string',
        ]);

        $fingerprintTemplate = $request->input('fingerprint_data');
        $fingerprintTemplate = is_string($fingerprintTemplate)
            ? preg_replace('/\s+/', '', trim($fingerprintTemplate))
            : null;
        $fingerprintTemplate = $fingerprintTemplate
            ? (base64_decode($fingerprintTemplate, true) ?: $fingerprintTemplate)
            : null;
        $hasNewFingerprint = is_string($fingerprintTemplate)
            && $fingerprintTemplate !== ''
            && strtolower(trim($fingerprintTemplate)) !== 'null';
        $fingerprintTemplate = $hasNewFingerprint
            ? $this->appendFingerprintTemplate($admin->fingerprint_template, $fingerprintTemplate)
            : $admin->fingerprint_template;
        $isFingerprintRegistered = $hasNewFingerprint
            ? true
            : (bool) $admin->is_fingerprint_registered;

        $admin->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'position' => $request->position,
            'branch_id' => $request->branch_id,
            'date_hired' => $request->date_hired,
            'fingerprint_template' => $fingerprintTemplate,
            'is_fingerprint_registered' => $isFingerprintRegistered,
        ]);

        if ($request->has('is_active') && $admin->user) {
            $admin->user->update([
                'is_active' => (bool) $request->is_active,
            ]);
        }

        return redirect()->route('admin.employees')->with('success', 'Admin account updated successfully');
    }
    
    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'branch_head') {
            abort(403);
        }
        
        $employee = EmployeeProfile::findOrFail($id);
        
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $allowedBranch = Auth::user()->profile->branch_id;
            if ($employee->branch_id != $allowedBranch) {
                abort(403, 'You can only delete employees in your branch.');
            }
        }

        if ($employee->user) {
            $employee->user->update([
                'is_active' => false,
                'id_verification_status' => 'rejected',
                'rejection_reason' => 'Account deactivated by admin.',
            ]);
        }

        $employee->update([
            'date_resigned' => now(),
            'is_fingerprint_registered' => false,
        ]);
        
        return back()->with('success', 'Employee account deactivated and kept in the database.');
    }
    
    public function restore($id)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'branch_head') {
            abort(403);
        }
        
        $employee = EmployeeProfile::findOrFail($id);
        
        // STRICT: Branch admins can ONLY restore employees from their branch
        if ((Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin') || Auth::user()->role === 'branch_head') {
            $userBranch = Auth::user()->profile->branch_id ?? Auth::user()->branch_id;
            if (!$userBranch || $employee->branch_id != $userBranch) {
                abort(403, 'You can only restore employees in your branch.');
            }
        }

        if ($employee->user) {
            $employee->user->update([
                'is_active' => true,
                'id_verification_status' => 'approved',
            ]);
        }

        return back()->with('success', 'Employee account restored successfully.');
    }
    
    private function generateRandomPassword($length = 12)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    private function appendFingerprintTemplate($storedTemplate, $incomingTemplate)
    {
        $templates = $this->fingerprintTemplateList($storedTemplate);
        $incomingBytes = $this->fingerprintBytes($incomingTemplate);

        if ($incomingBytes === '') {
            return $storedTemplate;
        }

        foreach ($templates as $template) {
            if ($this->fingerprintBytes($template) === $incomingBytes) {
                return $storedTemplate;
            }
        }

        if (!$templates) {
            return $incomingBytes;
        }

        $templates[] = base64_encode($incomingBytes);
        return json_encode(['version' => 1, 'templates' => $templates], JSON_UNESCAPED_SLASHES);
    }

    private function fingerprintTemplateList($storedTemplate): array
    {
        $stored = (string) $storedTemplate;
        $decoded = json_decode($stored, true);

        if (is_array($decoded) && isset($decoded['templates']) && is_array($decoded['templates'])) {
            return array_values(array_filter($decoded['templates'], 'is_string'));
        }

        if ($stored === '') {
            return [];
        }

        return [$this->isBinaryTemplate($stored) ? base64_encode($stored) : $stored];
    }

    private function fingerprintBytes($value): string
    {
        $value = (string) $value;
        if ($this->isBinaryTemplate($value)) {
            return $value;
        }

        $normalized = preg_replace('/\s+/', '', trim($value)) ?? '';
        if ($normalized === '' || strlen($normalized) % 4 !== 0
            || !preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $normalized)) {
            return $value;
        }

        $decoded = base64_decode($normalized, true);
        return $decoded !== false && base64_encode($decoded) === $normalized ? $decoded : $value;
    }

    private function isBinaryTemplate($value): bool
    {
        $value = (string) $value;
        return $value !== '' && preg_match('//u', $value) !== 1;
    }
}
