<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\AdminProfile;
use App\Models\FinanceProfile;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardDataController extends Controller
{
    public function getEmployeeDashboard(Request $request)
    {
        $user = Auth::user();
        $profile = EmployeeProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return response()->json(['success' => false, 'error' => 'Profile not found']);
        }
        
        $todayAttendance = AttendanceLog::where('employee_profile_id', $profile->id)
            ->whereDate('attendance_date', today())
            ->first();
        
        $todayData = null;
        if ($todayAttendance) {
            $todayData = [
                'time_in' => $todayAttendance->time_in,
                'time_out' => $todayAttendance->time_out,
                'time_in_formatted' => $todayAttendance->time_in ? date('h:i A', strtotime($todayAttendance->time_in)) : null,
                'time_out_formatted' => $todayAttendance->time_out ? date('h:i A', strtotime($todayAttendance->time_out)) : null,
                'late_minutes' => $todayAttendance->late_minutes,
                'status' => $todayAttendance->status,
            ];
        }
        
        $stats = [
            'days_present' => AttendanceLog::where('employee_profile_id', $profile->id)
                ->whereMonth('attendance_date', now()->month)
                ->whereNotNull('time_in')
                ->count(),
            'days_late' => AttendanceLog::where('employee_profile_id', $profile->id)
                ->whereMonth('attendance_date', now()->month)
                ->where('late_minutes', '>', 0)
                ->count(),
            'pending_leaves' => LeaveRequest::where('employee_profile_id', $profile->id)
                ->where('status', 'pending')
                ->count(),
        ];
        
        $recentAttendance = AttendanceLog::where('employee_profile_id', $profile->id)
            ->orderBy('attendance_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($att) {
                return [
                    'attendance_date' => date('M d, Y', strtotime($att->attendance_date)),
                    'time_in' => $att->time_in ? date('h:i A', strtotime($att->time_in)) : '--',
                    'time_out' => $att->time_out ? date('h:i A', strtotime($att->time_out)) : '--',
                    'status' => $att->status,
                ];
            });
        
        return response()->json([
            'success' => true,
            'today_attendance' => $todayData,
            'stats' => $stats,
            'recent_attendance' => $recentAttendance,
            'has_fingerprint' => $profile->is_fingerprint_registered ?? false,
            'current_date' => now()->format('l, F j, Y'),
        ]);
    }
    
    public function getAdminDashboard(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        }
        
        // Basic stats
        $totalEmployees = EmployeeProfile::count();
        $presentToday = AttendanceLog::whereDate('attendance_date', today())->whereNotNull('time_in')->count();
        $lateToday = AttendanceLog::whereDate('attendance_date', today())->where('late_minutes', '>', 0)->count();
        $absentToday = $totalEmployees - $presentToday;
        $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 1) : 0;
        
        // Weekly trend data
        $trendLabels = [];
        $trendPresent = [];
        $trendLate = [];
        $trendAbsent = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trendLabels[] = $date->format('D, M d');
            
            $dayPresent = AttendanceLog::whereDate('attendance_date', $date)->whereNotNull('time_in')->count();
            $dayLate = AttendanceLog::whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->count();
            $dayAbsent = $totalEmployees - $dayPresent;
            
            $trendPresent[] = $dayPresent;
            $trendLate[] = $dayLate;
            $trendAbsent[] = max(0, $dayAbsent);
        }
        
        // Recent attendance with employee details
        $recentAttendance = AttendanceLog::with('employeeProfile')
            ->latest()
            ->limit(15)
            ->get()
            ->map(function($att) {
                return [
                    'date' => date('M d, Y', strtotime($att->attendance_date)),
                    'employee' => ($att->employeeProfile->first_name ?? '') . ' ' . ($att->employeeProfile->last_name ?? ''),
                    'employee_number' => $att->employeeProfile->employee_number ?? '',
                    'time_in' => $att->time_in ? date('h:i A', strtotime($att->time_in)) : '--',
                    'time_out' => $att->time_out ? date('h:i A', strtotime($att->time_out)) : '--',
                    'status' => $att->status,
                    'late_minutes' => $att->late_minutes,
                ];
            });
        
        return response()->json([
            'success' => true,
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'late_today' => $lateToday,
            'absent_today' => $absentToday,
            'attendance_rate' => $attendanceRate,
            'pending_leaves' => LeaveRequest::where('status', 'pending')->count(),
            'pending_verifications' => User::where('id_verification_status', 'pending')->count(),
            'trend_labels' => $trendLabels,
            'trend_present' => $trendPresent,
            'trend_late' => $trendLate,
            'trend_absent' => $trendAbsent,
            'recent_attendance' => $recentAttendance,
        ]);
    }
}
