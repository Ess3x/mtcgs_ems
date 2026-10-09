<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        $branches = Branch::all();
        return view('auth.register', compact('branches'));
    }
    
    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'branch_id' => 'required|exists:branches,id',
            'position' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'employee',
                'branch_id' => $data['branch_id'],
                'id_verification_status' => 'pending',
                'is_active' => true,
            ]);

            $employeeProfile = EmployeeProfile::create([
                'user_id' => $user->id,
                'branch_id' => $data['branch_id'],
                'employee_number' => 'EMP' . time() . rand(100, 999),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => null,
                'suffix' => null,
                'date_of_birth' => null,
                'gender' => null,
                'civil_status' => null,
                'position' => $data['position'],
                'department' => 'General',
                'employment_type' => 'Regular',
                'date_hired' => now()->format('Y-m-d'),
                'date_resigned' => null,
                'basic_salary' => 0,
                'hourly_rate' => null,
                'contact_number' => $data['contact_number'],
                'emergency_contact_name' => null,
                'emergency_contact_number' => null,
                'address' => null,
                'profile_photo' => null,
                'fingerprint_template' => null,
                'is_fingerprint_registered' => false,
            ]);

            $user->profile_id = $employeeProfile->id;
            $user->profile_type = EmployeeProfile::class;
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['register' => 'Unable to create account. Please try again later.']);
        }

        return redirect()->route('login')->with('success', 'Registration request sent successfully. Please wait for admin approval before logging in.');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = $this->loginThrottleKey($request);
        $lockoutKey = $throttleKey . ':lockout';
        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            return $this->loginLockoutResponse($request, RateLimiter::availableIn($lockoutKey));
        }
        
        $user = User::where('email', $request->email)->first();
        
        // Check if account exists
        if (!$user) {
            $this->recordLoginHistory($request, null, 'failed', 'Unknown email');
            if ($this->recordFailedLoginAttempt($throttleKey, $lockoutKey)) {
                return $this->loginLockoutResponse($request, RateLimiter::availableIn($lockoutKey));
            }
            return back()->withErrors(['email' => 'Invalid credentials']);
        }
        
        // Check verification status
        if ($user->id_verification_status !== 'approved') {
            $this->recordLoginHistory($request, $user, 'failed', 'Account not approved');
            return back()->withErrors([
                'email' => 'Your account is pending verification. Please wait for admin approval.'
            ]);
        }
        
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            RateLimiter::clear($throttleKey);
            RateLimiter::clear($lockoutKey);
            $user = Auth::user();
            
            // Determine branch based on role and profile
            $branchId = null;
            $branchCode = null;
            
            if ($user->isEmployee()) {
                $profile = $user->getEmployeeProfile();
                if ($profile && $profile->branch) {
                    $branchId = $profile->branch_id;
                    $branchCode = $profile->branch->branch_code;
                } else {
                    $branchId = $user->getEffectiveBranchId();
                    $branchCode = $branchId ? Branch::find($branchId)?->branch_code : null;
                }
            } elseif ($user->isFinanceOfficer()) {
                $profile = $user->getFinanceProfile();
                if ($profile && $profile->branch) {
                    $branchId = $profile->branch_id;
                    $branchCode = $profile->branch->branch_code;
                } else {
                    $branchId = $user->getEffectiveBranchId();
                    $branchCode = $branchId ? Branch::find($branchId)?->branch_code : null;
                }
            } elseif ($user->isAdmin() || $user->isBranchHead()) {
                if ($user->role === 'branch_head') {
                    $branchHeadProfile = $user->getBranchHeadProfile();
                    if ($branchHeadProfile && $branchHeadProfile->branch) {
                        $branchId = $branchHeadProfile->branch_id;
                        $branchCode = $branchHeadProfile->branch->branch_code;
                    } else {
                        $branchId = $user->getEffectiveBranchId();
                        $branchCode = $branchId ? Branch::find($branchId)?->branch_code : null;
                    }
                } else {
                    $adminProfile = $user->getAdminProfile();
                    $isBranchScopedAdmin = in_array($user->admin_type ?? '', ['branch_admin'], true);
                    $isGlobalAdmin = in_array($user->admin_type ?? '', ['super_admin', 'hr'], true) || empty($user->admin_type);

                    if ($adminProfile && $adminProfile->branch && $isBranchScopedAdmin) {
                        $branchId = $adminProfile->branch_id;
                        $branchCode = $adminProfile->branch->branch_code;
                    } elseif ($isBranchScopedAdmin) {
                        $branchId = $user->getEffectiveBranchId();
                        $branchCode = $branchId ? Branch::find($branchId)?->branch_code : null;
                    } else {
                        $branchId = null;
                        $branchCode = null;
                    }
                }
            }
            
            // Store branch info in session only for branch-scoped users.
            session(['user_branch' => $branchCode, 'user_branch_code' => $branchCode]);
            session(['branch_id' => $branchId]);
            
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
            $this->recordLoginHistory($request, $user, 'success');
            return redirect()->intended('/dashboard');
        }
        
        $this->recordLoginHistory($request, $user, 'failed', 'Invalid credentials');
        if ($this->recordFailedLoginAttempt($throttleKey, $lockoutKey)) {
            return $this->loginLockoutResponse($request, RateLimiter::availableIn($lockoutKey));
        }
        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    private function loginThrottleKey(Request $request): string
    {
        $identity = mb_strtolower(trim((string) $request->input('email'))) . '|' . $request->ip();

        return 'login:'.hash('sha256', $identity);
    }

    private function recordFailedLoginAttempt(string $throttleKey, string $lockoutKey): bool
    {
        RateLimiter::hit($throttleKey, 180);

        if (RateLimiter::attempts($throttleKey) < 5) {
            return false;
        }

        RateLimiter::clear($throttleKey);
        RateLimiter::hit($lockoutKey, 180);

        return true;
    }

    private function loginLockoutResponse(Request $request, int $seconds)
    {
        $seconds = max(1, $seconds);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Too many failed login attempts. Please wait for the countdown before trying again.'])
            ->with('login_lockout_seconds', $seconds);
    }
    
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $this->recordLoginHistory($request, $user, 'success', null, 'logout');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget(['user_branch', 'user_branch_code', 'branch_id']);
        return redirect('/');
    }

    private function recordLoginHistory(Request $request, ?User $user, string $status, ?string $reason = null, string $event = 'login'): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'event' => $event,
            'status' => $status,
            'email' => $request->input('email') ?: $user?->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'failure_reason' => $reason,
        ]);
    }
}
