<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
        
        // IMPORTANTE: I-filter ang employees LANG - ACTIVE only!
        // Kunin ang lahat ng employee_profiles na nasa branch
        // at ang kanilang user ay may role = 'employee' at is_active = true
        $employees = EmployeeProfile::where('branch_id', $branchId)
            ->whereHas('user', function($q) {
                $q->where('role', 'employee')
                  ->where('is_active', true);  // Only active employees
            })
            ->get();
        
        $branchName = optional($employees->first()?->branch)->branch_name ?? 'Your Branch';
        
        return view('finance.employees', compact('employees', 'branchName'));
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
}
