<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminProfile;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role;
        
        // SUPER ADMIN
        if ($user->isSuperAdmin()) {
            $totalEmployees = EmployeeProfile::whereHas('user', function($q) {
                $q->where('is_active', true);
            })->count();
            $presentToday = AttendanceLog::whereDate('attendance_date', today())->whereNotNull('am_in')->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->count();
            $lateToday = AttendanceLog::whereDate('attendance_date', today())->where('late_minutes', '>', 0)->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->count();
            $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
            $pendingCount = User::where('id_verification_status', 'pending')->count();
            $absentToday = max(0, $totalEmployees - $presentToday);
            $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
            
            // Build 7-day trend data
            $trendDates = collect(range(6, 0, -1))->map(fn($daysAgo) => today()->subDays($daysAgo));
            $trendLabels = $trendDates->map(fn($date) => $date->format('M d'))->toArray();
            $trendPresent = [];
            $trendLate = [];
            $trendAbsent = [];
            
            foreach ($trendDates as $date) {
                $presentCount = AttendanceLog::whereDate('attendance_date', $date)->whereNotNull('am_in')->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })->count();
                $lateCount = AttendanceLog::whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })->count();
                $totalActiveEmployees = EmployeeProfile::whereHas('user', function($q) {
                    $q->where('is_active', true);
                })->count();
                $absentCount = max(0, $totalActiveEmployees - $presentCount);
                $trendPresent[] = $presentCount;
                $trendLate[] = $lateCount;
                $trendAbsent[] = $absentCount;
            }
            
            $branchName = 'All Branches';
            
            // Get recent attendance records (limit to 5 most recent) - ACTIVE employees only
            $recentAttendance = AttendanceLog::with('employeeProfile')
                ->whereHas('employeeProfile')
                ->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })
                ->latest('attendance_date')
                ->limit(5)
                ->get()
                ->map(function($log) {
                    $profile = $log->employeeProfile;
                    return [
                        'date' => $log->attendance_date ? $log->attendance_date->format('M d, Y') : 'N/A',
                        'employee' => $profile ? ($profile->first_name . ' ' . $profile->last_name) : 'Unknown',
                        'employee_number' => $profile ? $profile->employee_number : 'N/A',
                        'am_in' => $log->am_in ? $log->am_in->format('h:i A') : '--',
                        'am_out' => $log->am_out ? $log->am_out->format('h:i A') : '--',
                        'pm_in' => $log->pm_in ? $log->pm_in->format('h:i A') : '--',
                        'pm_out' => $log->pm_out ? $log->pm_out->format('h:i A') : '--',
                        'status' => $log->status ?? 'N/A',
                        'late_minutes' => (int)($log->late_minutes ?? 0),
                        'overtime_hours' => (float)($log->overtime_hours ?? 0),
                        'verification_method' => $log->verification_method ?? 'manual',
                    ];
                })->toArray();
            
            return view('admin.dashboard', compact(
                'totalEmployees', 'presentToday', 'lateToday', 'absentToday', 'attendanceRate', 'pendingLeaves', 
                'pendingCount', 'recentAttendance', 'trendLabels', 'trendPresent', 'trendLate', 'trendAbsent', 'branchName'
            ));
        }
        
        // BRANCH ADMIN
        if ($user->isBranchAdmin()) {
            $adminProfile = $user->getAdminProfile();
            
            // Get branch info with fallback
            $branchId = $adminProfile ? ($adminProfile->branch_id ?? 1) : 1;
            $branchName = 'System Default';
            
            if ($adminProfile && $adminProfile->branch && $adminProfile->branch->branch_name) {
                $branchName = $adminProfile->branch->branch_name;
            }
            
            // Get branch employees - ACTIVE only
            $totalEmployees = EmployeeProfile::where('branch_id', $branchId)
                ->whereHas('user', function($q) {
                    $q->where('is_active', true);
                })->count();
            $presentToday = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->whereDate('attendance_date', today())->whereNotNull('am_in')->count();
            
            // Late today
            $lateToday = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->whereDate('attendance_date', today())->where('late_minutes', '>', 0)->count();
            
            // Pending leaves
            $pendingLeaves = LeaveRequest::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->where('status', 'pending')->count();
            
            $pendingCount = User::where('id_verification_status', 'pending')->count();
            $absentToday = max(0, $totalEmployees - $presentToday);
            $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
            
            // Build 7-day trend data
            $trendDates = collect(range(6, 0, -1))->map(fn($daysAgo) => today()->subDays($daysAgo));
            $trendLabels = $trendDates->map(fn($date) => $date->format('M d'))->toArray();
            $trendPresent = [];
            $trendLate = [];
            $trendAbsent = [];
            
            foreach ($trendDates as $date) {
                $presentCount = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })->whereDate('attendance_date', $date)->whereNotNull('am_in')->count();
                $lateCount = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })->whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->count();
                $branchEmployeeCount = EmployeeProfile::where('branch_id', $branchId)
                    ->whereHas('user', function($q) {
                        $q->where('is_active', true);
                    })->count();
                $absentCount = max(0, $branchEmployeeCount - $presentCount);
                
                $trendPresent[] = $presentCount;
                $trendLate[] = $lateCount;
                $trendAbsent[] = $absentCount;
            }
            
            // Get recent attendance records for the branch (limit to 5 most recent)
            $recentAttendance = AttendanceLog::with('employeeProfile')
                ->whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })
                ->latest('attendance_date')
                ->limit(5)
                ->get()
                ->map(function($log) {
                    $profile = $log->employeeProfile;
                    return [
                        'date' => $log->attendance_date ? $log->attendance_date->format('M d, Y') : 'N/A',
                        'employee' => $profile ? ($profile->first_name . ' ' . $profile->last_name) : 'Unknown',
                        'employee_number' => $profile ? $profile->employee_number : 'N/A',
                        'am_in' => $log->am_in ? $log->am_in->format('h:i A') : '--',
                        'am_out' => $log->am_out ? $log->am_out->format('h:i A') : '--',
                        'pm_in' => $log->pm_in ? $log->pm_in->format('h:i A') : '--',
                        'pm_out' => $log->pm_out ? $log->pm_out->format('h:i A') : '--',
                        'status' => $log->status ?? 'N/A',
                        'late_minutes' => (int)($log->late_minutes ?? 0),
                        'overtime_hours' => (float)($log->overtime_hours ?? 0),
                        'verification_method' => $log->verification_method ?? 'manual',
                    ];
                })->toArray();
            
            return view('admin.dashboard', compact(
                'totalEmployees', 'presentToday', 'lateToday', 'absentToday', 'attendanceRate', 'pendingLeaves', 
                'pendingCount', 'branchName', 'trendLabels', 'trendPresent', 'trendLate', 'trendAbsent', 'recentAttendance'
            ));
        }
        
        // FINANCE OFFICER
        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            
            // Get branch info with fallback
            $branchId = $financeProfile ? ($financeProfile->branch_id ?? 1) : 1;
            $branchName = 'System Default';
            
            if ($financeProfile && $financeProfile->branch && $financeProfile->branch->branch_name) {
                $branchName = $financeProfile->branch->branch_name;
            }
            
            // Get employee profile for DTR
            $employeeProfile = EmployeeProfile::where('user_id', $user->id)->first();
            if (!$employeeProfile && $financeProfile && $financeProfile->employee_profile_id) {
                $employeeProfile = EmployeeProfile::find($financeProfile->employee_profile_id);
            }
            
            // Get branch employees - ACTIVE only
            $totalEmployees = EmployeeProfile::where('branch_id', $branchId)
                ->whereHas('user', function($q) {
                    $q->where('is_active', true);
                })->count();
            $presentToday = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->whereDate('attendance_date', today())->whereNotNull('am_in')->count();
            
            // Late today
            $lateToday = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->whereDate('attendance_date', today())->where('late_minutes', '>', 0)->count();
            
            // Pending leaves
            $pendingLeaves = LeaveRequest::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->where('status', 'pending')->count();
            
            // Monthly payroll total
            $monthlyPayroll = PayrollEntry::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereMonth('created_at', now()->month)->sum('net_pay') ?? 0;
            
            // Today's attendance records
            $todayAttendance = AttendanceLog::with('employeeProfile')
                ->whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->whereDate('attendance_date', today())
                ->get();
            
            // Last payroll
            $lastPayroll = PayrollEntry::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->latest()->first();
            
            $stats = [
                'total_employees' => $totalEmployees,
                'total_present_today' => $presentToday,
                'total_late_today' => $lateToday,
                'total_absent_today' => max(0, $totalEmployees - $presentToday),
                'monthly_payroll_total' => $monthlyPayroll,
                'pending_leaves' => $pendingLeaves,
            ];
            
            return view('finance.dashboard', compact(
                'branchName', 'stats', 'todayAttendance',
                'lastPayroll', 'employeeProfile'
            ));
        }

        // REGULAR ADMIN
        if ($user->isAdmin() && !$user->isSuperAdmin()) {
            $adminProfile = $user->getAdminProfile();
            
            // Get branch info
            $branchName = 'System Default';
            $branchId = 1;
            
            if ($adminProfile) {
                if ($adminProfile->branch && $adminProfile->branch->branch_name) {
                    $branchName = $adminProfile->branch->branch_name;
                }
                if ($adminProfile->branch_id) {
                    $branchId = $adminProfile->branch_id;
                }
            }
            
            // Get branch employees - ACTIVE only
            $totalEmployees = EmployeeProfile::where('branch_id', $branchId)
                ->whereHas('user', function($q) {
                    $q->where('is_active', true);
                })->count();
            $presentToday = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->whereDate('attendance_date', today())->whereNotNull('am_in')->count();
            $lateToday = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            })->whereDate('attendance_date', today())->where('late_minutes', '>', 0)->count();
            $pendingLeaves = LeaveRequest::whereHas('employeeProfile', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->where('status', 'pending')->count();
            $pendingCount = User::where('id_verification_status', 'pending')->count();
            $absentToday = max(0, $totalEmployees - $presentToday);
            $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
            
            // Build 7-day trend data
            $trendDates = collect(range(6, 0, -1))->map(fn($daysAgo) => today()->subDays($daysAgo));
            $trendLabels = $trendDates->map(fn($date) => $date->format('M d'))->toArray();
            $trendPresent = [];
            $trendLate = [];
            $trendAbsent = [];
            
            foreach ($trendDates as $date) {
                $presentCount = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })->whereDate('attendance_date', $date)->whereNotNull('am_in')->count();
                $lateCount = AttendanceLog::whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })->whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->count();
                $branchEmployeeCount = EmployeeProfile::where('branch_id', $branchId)
                    ->whereHas('user', function($q) {
                        $q->where('is_active', true);
                    })->count();
                $absentCount = max(0, $branchEmployeeCount - $presentCount);
                
                $trendPresent[] = $presentCount;
                $trendLate[] = $lateCount;
                $trendAbsent[] = $absentCount;
            }
            
            // Get recent attendance records for the branch (limit to 5 most recent)
            $recentAttendance = AttendanceLog::with('employeeProfile')
                ->whereHas('employeeProfile', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->latest('attendance_date')
                ->limit(5)
                ->get()
                ->map(function($log) {
                    $profile = $log->employeeProfile;
                    return [
                        'date' => $log->attendance_date ? $log->attendance_date->format('M d, Y') : 'N/A',
                        'employee' => $profile ? ($profile->first_name . ' ' . $profile->last_name) : 'Unknown',
                        'employee_number' => $profile ? $profile->employee_number : 'N/A',
                        'am_in' => $log->am_in ? $log->am_in->format('h:i A') : '--',
                        'am_out' => $log->am_out ? $log->am_out->format('h:i A') : '--',
                        'pm_in' => $log->pm_in ? $log->pm_in->format('h:i A') : '--',
                        'pm_out' => $log->pm_out ? $log->pm_out->format('h:i A') : '--',
                        'status' => $log->status ?? 'N/A',
                        'late_minutes' => (int)($log->late_minutes ?? 0),
                        'overtime_hours' => (float)($log->overtime_hours ?? 0),
                        'verification_method' => $log->verification_method ?? 'manual',
                    ];
                })->toArray();
            
            return view('admin.dashboard', compact(
                'totalEmployees', 'presentToday', 'lateToday', 'absentToday', 'attendanceRate', 'pendingLeaves', 
                'pendingCount', 'branchName', 'trendLabels', 'trendPresent', 'trendLate', 'trendAbsent', 'recentAttendance'
            ));
        }
        
        // REGULAR EMPLOYEE
        $profile = $user->getEmployeeProfile();
        
        if (!$profile) {
            if ($user->role === 'admin') {
                $adminProfile = $user->getAdminProfile();
                if ($adminProfile && $adminProfile->employee_profile_id) {
                    $profile = EmployeeProfile::find($adminProfile->employee_profile_id);
                }
            } elseif (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
                $financeProfile = $user->getFinanceProfile();
                if ($financeProfile && $financeProfile->employee_profile_id) {
                    $profile = EmployeeProfile::find($financeProfile->employee_profile_id);
                }
            }
        }
        
        if (!$profile) {
            return redirect('/login')->with('error', 'Employee profile not found');
        }
        
        $todayAttendance = AttendanceLog::where('employee_profile_id', $profile->id)
            ->whereDate('attendance_date', today())->first();
        
        $pendingLeaves = LeaveRequest::where('employee_profile_id', $profile->id)
            ->where('status', 'pending')->count();
        
        $recentAttendance = AttendanceLog::where('employee_profile_id', $profile->id)
            ->whereDate('attendance_date', today())
            ->latest()
            ->get();

        $stats = [
            'days_present' => AttendanceLog::where('employee_profile_id', $profile->id)
                ->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year)
                ->whereNotNull('am_in')
                ->count(),
            'days_late' => AttendanceLog::where('employee_profile_id', $profile->id)
                ->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year)
                ->where('late_minutes', '>', 0)
                ->count(),
        ];
        
        $hasFingerprint = $profile->is_fingerprint_registered ?? false;
        
        $profile->loadMissing('shift');

        return view('employee.dashboard', compact(
            'profile', 'todayAttendance', 'pendingLeaves', 
            'recentAttendance', 'hasFingerprint', 'stats'
        ));
    }

    public function profile()
    {
        $user = Auth::user();
        
        // Get the appropriate profile based on user role
        $profile = null;
        $profileType = '';
        
        if ($user->role === 'employee') {
            $profile = $user->getEmployeeProfile();
            $profileType = 'Employee';
        } elseif (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            $profile = $user->getFinanceProfile();
            $profileType = $user->role === 'finance_head' ? 'Finance Head' : 'Finance Officer';
        } elseif ($user->role === 'admin') {
            $profile = $user->getAdminProfile();
            $profileType = 'Administrator';
        }
        
        // If no profile found, try to get employee profile for admin/finance officers
        if (!$profile) {
            if ($user->role === 'admin') {
                $adminProfile = $user->getAdminProfile();
                if ($adminProfile && $adminProfile->employee_profile_id) {
                    $profile = EmployeeProfile::find($adminProfile->employee_profile_id);
                    $profileType = 'Administrator (Employee Profile)';
                }
            } elseif (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
                $financeProfile = $user->getFinanceProfile();
                if ($financeProfile && $financeProfile->employee_profile_id) {
                    $profile = EmployeeProfile::find($financeProfile->employee_profile_id);
                    $profileType = $user->role === 'finance_head' ? 'Finance Head (Employee Profile)' : 'Finance Officer (Employee Profile)';
                }
            }
        }
        
        return view('profile', compact('user', 'profile', 'profileType'));
    }

    public function updatePassword(\Illuminate\Http\Request $request)
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('password_updated', 'Your password has been updated successfully.');
    }

    public function saveSignature(\Illuminate\Http\Request $request)
    {
        $profile = $request->user()->getEmployeeProfile();
        if (!$profile && in_array($request->user()->role, ['finance_officer', 'finance_head'], true)) {
            $profile = $request->user()->getFinanceProfile();
        }
        if (!$profile && $request->user()->role === 'admin') {
            $profile = $request->user()->getAdminProfile();
        }
        abort_unless($profile, 403);

        $validated = $request->validate([
            'signature' => ['required', 'string', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
        ]);

        $imageData = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1), true);
        abort_unless($imageData !== false && strlen($imageData) <= 2 * 1024 * 1024, 422, 'Invalid signature image.');

        $path = 'signatures/' . strtolower(class_basename($profile)) . '-' . $profile->id . '.png';
        abort_unless(Storage::disk('public')->put($path, $imageData), 500, 'Unable to save signature image.');
        $profile->update(['signature_path' => $path]);

        return back()->with('signature_updated', 'E-Signature saved successfully.');
    }

    public function saveProfilePhoto(\Illuminate\Http\Request $request)
    {
        $profile = $request->user()->getEmployeeProfile();
        if (!$profile && in_array($request->user()->role, ['finance_officer', 'finance_head'], true)) {
            $profile = $request->user()->getFinanceProfile();
        }
        if (!$profile && $request->user()->role === 'admin') {
            $profile = $request->user()->getAdminProfile();
        }
        abort_unless($profile, 403);

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldPath = $profile->profile_photo;
        $extension = strtolower($validated['profile_photo']->extension());
        $path = 'profile-photos/' . strtolower(class_basename($profile)) . '-' . $profile->id . '.' . $extension;

        abort_unless(Storage::disk('public')->putFileAs('profile-photos', $validated['profile_photo'], basename($path)), 500, 'Unable to save profile photo.');
        $profile->update(['profile_photo' => $path]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('photo_updated', 'Profile photo updated successfully.');
    }

    public function profilePhoto(string $type, int $id)
    {
        $profileClass = match (strtolower($type)) {
            'employeeprofile', 'employee' => EmployeeProfile::class,
            'financeprofile', 'finance' => FinanceProfile::class,
            'adminprofile', 'admin' => AdminProfile::class,
            default => null,
        };

        abort_unless($profileClass, 404);

        $profile = $profileClass::findOrFail($id);
        $user = Auth::user();
        abort_unless($profile->user_id === $user->id || $user->isAdmin(), 403);
        abort_unless($profile->profile_photo && Storage::disk('public')->exists($profile->profile_photo), 404);

        return response()->file(Storage::disk('public')->path($profile->profile_photo), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function signature(EmployeeProfile $profile)
    {
        $user = Auth::user();
        $canView = $profile->user_id === $user->id || $user->isAdmin();

        if (!$canView) {
            abort(403);
        }

        $path = $profile->signature_path;
        if (!$path || !Storage::disk('public')->exists($path)) {
            $path = FinanceProfile::where('employee_profile_id', $profile->id)->value('signature_path');
        }
        if (!$path || !Storage::disk('public')->exists($path)) {
            $path = AdminProfile::where('employee_profile_id', $profile->id)->value('signature_path');
        }

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function profileSignature(string $type, int $id)
    {
        $profileClass = match (strtolower($type)) {
            'employeeprofile', 'employee' => EmployeeProfile::class,
            'financeprofile', 'finance' => FinanceProfile::class,
            'adminprofile', 'admin' => AdminProfile::class,
            default => null,
        };

        abort_unless($profileClass, 404);

        $profile = $profileClass::findOrFail($id);
        $user = Auth::user();
        abort_unless($profile->user_id === $user->id || $user->isAdmin(), 403);

        $path = $profile->signature_path;
        if ((!$path || !Storage::disk('public')->exists($path)) && $profile instanceof EmployeeProfile) {
            $path = FinanceProfile::where('employee_profile_id', $profile->id)->value('signature_path');
        }
        if ((!$path || !Storage::disk('public')->exists($path)) && $profile instanceof EmployeeProfile) {
            $path = AdminProfile::where('employee_profile_id', $profile->id)->value('signature_path');
        }

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function profileDocument()
    {
        $user = Auth::user();

        if (!$user->id_document_path || !Storage::disk('public')->exists($user->id_document_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($user->id_document_path));
    }
}
