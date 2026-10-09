<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Models\Branch;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!$request->user()?->isFinanceOfficer()) {
                return redirect()->route('dashboard')->with('error', 'You are not authorized to access that page.');
            }

            return $next($request);
        });
    }
    
    private function getBranchId()
    {
        $user = Auth::user();
        if ($user->isFinanceOfficer()) {
            $profile = $user->getFinanceProfile();
            return $profile?->branch_id ?? $user->branch_id ?? 1;
        }
        return $user->branch_id ?? 1;
    }
    
    public function index()
    {
        $branchId = $this->getBranchId();
        $profileTypes = [
            EmployeeProfile::class,
            \App\Models\FinanceProfile::class,
            \App\Models\AdminProfile::class,
            \App\Models\BranchHeadProfile::class,
        ];

        $users = User::with(['profile', 'branch'])
            ->where('is_active', true)
            ->where(function ($query) use ($branchId, $profileTypes) {
                $query->whereHasMorph('profile', $profileTypes, function ($profileQuery) use ($branchId) {
                    $profileQuery->where('branch_id', $branchId);
                })->orWhere(function ($query) use ($branchId, $profileTypes) {
                    $query->whereDoesntHaveMorph('profile', $profileTypes)
                        ->where('branch_id', $branchId);
                });
            })
            ->orderBy('name')
            ->get();

        $branchName = Branch::find($branchId)?->branch_name ?? 'Your Branch';
        
        return view('finance.employees', compact('users', 'branchId', 'branchName'));
    }
    
    public function attendance($id)
    {
        $branchId = $this->getBranchId();
        
        // I-verify na ang employee ay nasa tamang branch at employee role
        $employee = EmployeeProfile::where('id', $id)
            ->where('branch_id', $branchId)
            ->whereHas('user', function($q) {
                $q->where('role', 'employee');
            })
            ->firstOrFail();

        $selectedMonth = request('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = now()->startOfMonth();
            $selectedMonth = $currentMonth->format('Y-m');
        }

        $attendanceLogs = AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereYear('attendance_date', $currentMonth->year)
            ->whereMonth('attendance_date', $currentMonth->month)
            ->orderBy('attendance_date', 'asc')
            ->get();

        $attendanceByDate = $attendanceLogs->keyBy(function ($log) {
            return $log->attendance_date->format('Y-m-d');
        });

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('finance.employee-attendance', compact(
            'employee', 'attendanceLogs', 'currentMonth',
            'attendanceByDate', 'prevMonth', 'nextMonth', 'selectedMonth'
        ));
    }

    public function profile(string $type, int $id)
    {
        $branchId = $this->getBranchId();
        $profileClass = match (strtolower($type)) {
            'employee', 'employeeprofile' => EmployeeProfile::class,
            'finance', 'financeprofile' => \App\Models\FinanceProfile::class,
            'admin', 'adminprofile' => \App\Models\AdminProfile::class,
            'branchhead', 'branchheadprofile' => \App\Models\BranchHeadProfile::class,
            default => abort(404),
        };

        $profile = $profileClass::with('branch', 'user')->findOrFail($id);
        $canViewAnyBranch = Auth::user()->isSuperAdmin();
        abort_unless($canViewAnyBranch || ($profile->branch_id ?? $profile->user?->branch_id) === $branchId, 404);

        return view('finance.employee-profile', compact('profile'));
    }
}
