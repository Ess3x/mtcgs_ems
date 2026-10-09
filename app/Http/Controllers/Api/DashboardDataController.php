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
                ->whereIn('status', ['pending', 'pending_system_admin'])
                ->count(),
        ];
        
        $recentAttendance = AttendanceLog::where('employee_profile_id', $profile->id)
            ->whereDate('attendance_date', today())
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
        
        $allowedBranchIds = $this->allowedBranchIds($user);
        $employeesQuery = EmployeeProfile::query();
        $attendanceQuery = AttendanceLog::query();
        if ($allowedBranchIds !== null) {
            $employeesQuery->whereIn('branch_id', $allowedBranchIds);
            $attendanceQuery->whereIn('branch_id', $allowedBranchIds);
        }

        // Basic stats
        $totalEmployees = (clone $employeesQuery)->count();
        $presentToday = (clone $attendanceQuery)->whereDate('attendance_date', today())->whereNotNull('time_in')->count();
        $lateToday = (clone $attendanceQuery)->whereDate('attendance_date', today())->where('late_minutes', '>', 0)->count();
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
            
            $dayQuery = clone $attendanceQuery;
            $dayPresent = $dayQuery->whereDate('attendance_date', $date)->whereNotNull('time_in')->count();
            $dayLate = (clone $attendanceQuery)->whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->count();
            $dayAbsent = $totalEmployees - $dayPresent;
            
            $trendPresent[] = $dayPresent;
            $trendLate[] = $dayLate;
            $trendAbsent[] = max(0, $dayAbsent);
        }
        
        // Recent attendance with employee details
        $recentQuery = AttendanceLog::with('employeeProfile');
        if ($allowedBranchIds !== null) {
            $recentQuery->whereIn('branch_id', $allowedBranchIds);
        }
        $recentAttendance = $recentQuery
            ->whereDate('attendance_date', today())
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
            'pending_leaves' => LeaveRequest::whereIn('status', ['pending', 'pending_system_admin'])
                ->when($allowedBranchIds !== null, fn ($query) => $query->whereHas('employeeProfile', fn ($employeeQuery) => $employeeQuery->whereIn('branch_id', $allowedBranchIds)))
                ->count(),
            'pending_verifications' => User::where('id_verification_status', 'pending')
                ->when($allowedBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $allowedBranchIds))
                ->count(),
            'trend_labels' => $trendLabels,
            'trend_present' => $trendPresent,
            'trend_late' => $trendLate,
            'trend_absent' => $trendAbsent,
            'recent_attendance' => $recentAttendance,
        ]);
    }

    public function getLiveUpdates(Request $request)
    {
        $user = Auth::user();
        $allowedBranchIds = $this->allowedBranchIds($user);
        if ($user->role === 'admin' && $user->admin_type === 'hr') {
            $allowedBranchIds = null;
        }
        $sinceId = max(0, (int) $request->query('since_id', 0));
        $employeeProfileId = null;
        if ($user->role === 'employee') {
            $employeeProfileId = EmployeeProfile::where('user_id', $user->id)->value('id');
        } elseif ($user->role === 'branch_head') {
            $employeeProfileId = $user->getBranchHeadProfile()?->employee_profile_id;
        }

        $attendanceQuery = AttendanceLog::with('employeeProfile')
            ->whereDate('attendance_date', today())
            ->where(function ($query) {
                $query->whereNotNull('am_in')
                    ->orWhereNotNull('am_out')
                    ->orWhereNotNull('pm_in')
                    ->orWhereNotNull('pm_out');
            })
            ->when($employeeProfileId, fn ($query) => $query->where('employee_profile_id', $employeeProfileId))
            ->when($sinceId > 0, fn ($query) => $query->where('id', '>', $sinceId))
            ->when($allowedBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $allowedBranchIds))
            ->latest('id')
            ->limit(10)
            ->get();

        $latestAttendanceId = (int) (AttendanceLog::whereDate('attendance_date', today())
            ->when($employeeProfileId, fn ($query) => $query->where('employee_profile_id', $employeeProfileId))
            ->when($allowedBranchIds !== null, fn ($query) => $query->whereIn('branch_id', $allowedBranchIds))
            ->max('id') ?? 0);

        $ownAttendance = $employeeProfileId
            ? AttendanceLog::where('employee_profile_id', $employeeProfileId)->whereDate('attendance_date', today())->first()
            : null;

        return response()->json([
            'success' => true,
            'role' => $user->role,
            'latest_attendance_id' => $latestAttendanceId,
            'unread_notifications' => $user->unreadNotifications()->count(),
            'own_attendance' => $ownAttendance ? [
                'am_in' => $ownAttendance->am_in?->format('h:i A'),
                'am_out' => $ownAttendance->am_out?->format('h:i A'),
                'pm_in' => $ownAttendance->pm_in?->format('h:i A'),
                'pm_out' => $ownAttendance->pm_out?->format('h:i A'),
                'status' => $ownAttendance->status,
            ] : null,
            'arrivals' => $attendanceQuery->map(function (AttendanceLog $attendance) {
                $profile = $attendance->employeeProfile;

                return [
                    'id' => $attendance->id,
                    'employee' => trim(($profile?->first_name ?? '') . ' ' . ($profile?->last_name ?? '')) ?: 'Unknown employee',
                    'employee_number' => $profile?->employee_number ?? 'N/A',
                    'time' => ($attendance->am_in ?? $attendance->am_out ?? $attendance->pm_in ?? $attendance->pm_out)?->format('h:i A'),
                    'verification_method' => $attendance->verification_method ?? 'manual',
                ];
            })->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function allowedBranchIds(User $user): ?array
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        $branchIds = array_filter([(int) $user->getEffectiveBranchId()]);
        if ($user->isFinanceOfficer()) {
            $profile = $user->getFinanceProfile();
            $branchIds = array_merge($branchIds, array_map('intval', (array) ($profile?->accessible_branches ?? [])));
        }

        return array_values(array_unique(array_filter($branchIds)));
    }
}
