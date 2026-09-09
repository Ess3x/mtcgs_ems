<?php

namespace App\Http\Controllers;

use App\Models\DTR;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DTRController extends Controller
{
    private function resolveEmployeeProfileForCurrentUser($user)
    {
        $employeeProfile = EmployeeProfile::where('user_id', $user->id)->first();

        if ($employeeProfile) {
            return $employeeProfile;
        }

        if ($user->role === 'branch_head') {
            $branchHeadProfile = $user->getBranchHeadProfile();
            if ($branchHeadProfile && $branchHeadProfile->employee_profile_id) {
                return EmployeeProfile::find($branchHeadProfile->employee_profile_id);
            }
        }

        if ($user->role === 'admin') {
            $adminProfile = $user->getAdminProfile();
            if ($adminProfile && $adminProfile->employee_profile_id) {
                return EmployeeProfile::find($adminProfile->employee_profile_id);
            }
        }

        if (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            $financeProfile = $user->getFinanceProfile();
            if ($financeProfile && $financeProfile->employee_profile_id) {
                return EmployeeProfile::find($financeProfile->employee_profile_id);
            }
        }

        return null;
    }

    private function findCurrentDTR($employeeProfileId)
    {
        $today = Carbon::today();
        [$periodStart, $periodEnd] = DTR::cutoffPeriodFor($today);

        return DTR::where('employee_profile_id', $employeeProfileId)
            ->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd)
            ->first();
    }

    /**
     * Show DTR list for the authenticated employee
     */
    public function index()
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);

        if (!$employeeProfile) {
            return redirect('/dashboard')->with('error', 'Employee profile not found');
        }

        DTR::generateForCurrentPeriod($employeeProfile->id);

        // Get all DTRs for this employee, sorted by period_start descending
        $dtrs = DTR::where('employee_profile_id', $employeeProfile->id)
            ->orderBy('period_start', 'desc')
            ->paginate(12);

        $currentDTR = $this->findCurrentDTR($employeeProfile->id);

        return view('employee.dtr.index', compact('dtrs', 'currentDTR', 'employeeProfile'));
    }

    /**
     * Show a specific DTR with all attendance records
     */
    public function show($dtrId)
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);

        if (!$employeeProfile) {
            return redirect('/dashboard')->with('error', 'Employee profile not found');
        }

        $dtr = DTR::findOrFail($dtrId);
        $dtr->calculateTotals();
        $dtr->save();

        // Check authorization - employee can only view their own DTR
        if ($dtr->employee_profile_id !== $employeeProfile->id && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $previousDTR = DTR::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('period_start', '<', $dtr->period_start)
            ->orderByDesc('period_start')
            ->first();
        $nextDTR = DTR::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('period_start', '>', $dtr->period_start)
            ->orderBy('period_start')
            ->first();

        // Get attendance logs for this period
        $attendanceLogs = AttendanceLog::where('employee_profile_id', $dtr->employee_profile_id)
            ->whereBetween('attendance_date', [$dtr->period_start, $dtr->period_end])
            ->orderBy('attendance_date')
            ->get();

        $approvedLeaves = LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dtr->period_end)
            ->whereDate('end_date', '>=', $dtr->period_start)
            ->get();

        // Prepare calendar data
        $daysInPeriod = $this->getDaysInPeriod($dtr->period_start, $dtr->period_end, $attendanceLogs, $approvedLeaves, $employeeProfile, $dtr->status);

        // Match the summary values to the exact time-related data rendered in each DTR row.
        $totalHours = 0.0;
        $totalLateMinutes = 0;
        $totalEarlyOutMinutes = 0;
        $daysPresent = 0;
        $daysAbsent = 0;
        $totalPaidLeave = 0;
        $totalLeaveWithoutPay = 0;

        foreach ($daysInPeriod as $day) {
            $status = $day['status'] ?? null;
            $log = $day['log'] ?? null;

            if ($status === 'present' || $status === 'late') {
                $daysPresent++;
                $totalHours += $this->calculateLogHours($log);
            } elseif ($status === 'absent') {
                $daysAbsent++;
            } elseif ($status === 'leave') {
                $totalPaidLeave++;
            } elseif ($status === 'lwop') {
                $totalLeaveWithoutPay++;
            }

            if ($log) {
                $lateMinutes = DTR::normalizeLateMinutesForLog($log);
                if ($log->am_in) {
                    $scheduledStart = $log->am_in->copy()->setTime(7, 0, 0);
                    if ($log->am_in->greaterThanOrEqualTo($scheduledStart)) {
                        $lateMinutes = max($lateMinutes, (int) $scheduledStart->diffInMinutes($log->am_in));
                    }
                }
                $totalLateMinutes += $lateMinutes;

                if ($log->pm_out) {
                    $scheduledPmOut = $log->pm_out->copy()->setTimeFromTimeString(
                        $log->employeeProfile?->shift?->end_time ?: '17:00:00'
                    );
                    if ($log->pm_out->lt($scheduledPmOut)) {
                        $totalEarlyOutMinutes += max(0, (int) abs($scheduledPmOut->diffInMinutes($log->pm_out)));
                    }
                }
            }
        }

        $stats = [
            'total_hours' => round($totalHours, 2),
            'total_late_minutes' => $totalLateMinutes,
            'total_early_out_minutes' => $totalEarlyOutMinutes,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'total_paid_leave' => $totalPaidLeave,
            'total_leave_without_pay' => $totalLeaveWithoutPay,
            'working_days' => max(1, $daysPresent + $daysAbsent + $totalPaidLeave + $totalLeaveWithoutPay),
        ];

        return view('employee.dtr.show', compact('dtr', 'attendanceLogs', 'daysInPeriod', 'stats', 'employeeProfile', 'previousDTR', 'nextDTR'));
    }

    public function downloadPdf($dtrId)
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);
        $dtr = DTR::findOrFail($dtrId);

        if (!$employeeProfile || ($dtr->employee_profile_id !== $employeeProfile->id && !$user->isAdmin())) {
            abort(403, 'Unauthorized access');
        }

        $dtr->calculateTotals();
        $attendanceLogs = AttendanceLog::where('employee_profile_id', $dtr->employee_profile_id)
            ->whereBetween('attendance_date', [$dtr->period_start, $dtr->period_end])
            ->orderBy('attendance_date')
            ->get();
        $approvedLeaves = LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dtr->period_end)
            ->whereDate('end_date', '>=', $dtr->period_start)
            ->get();
        $daysInPeriod = $this->getDaysInPeriod($dtr->period_start, $dtr->period_end, $attendanceLogs, $approvedLeaves, $dtr->employeeProfile, $dtr->status);
        $stats = [
            'total_hours' => round($dtr->total_hours ?? 0, 2),
            'days_present' => (int) ($dtr->days_present ?? 0),
            'days_absent' => (int) ($dtr->days_absent ?? 0),
            'late_minutes' => (int) ($dtr->late_minutes ?? 0),
            'working_days' => $dtr->getWorkingDays(),
        ];
        $dtrEmployeeProfile = $dtr->employeeProfile;
        $signaturePath = $dtrEmployeeProfile?->signature_path;
        if (!$signaturePath) {
            $signaturePath = FinanceProfile::where('employee_profile_id', $dtr->employee_profile_id)->value('signature_path');
        }
        if (!$signaturePath) {
            $signaturePath = AdminProfile::where('employee_profile_id', $dtr->employee_profile_id)->value('signature_path');
        }
        $employeeSignature = null;
        if ($signaturePath && Storage::disk('public')->exists($signaturePath)) {
            $employeeSignature = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($signaturePath));
        }
        $logoPath = public_path('images/logo.jpg');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('pdf.dtr', compact('dtr', 'employeeProfile', 'daysInPeriod', 'stats', 'employeeSignature', 'logo'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('dtr-' . $employeeProfile->employee_number . '-' . $dtr->period_start->format('Ymd') . '-' . $dtr->period_end->format('Ymd') . '.pdf');
    }

    public function downloadExcel($dtrId)
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);
        $dtr = DTR::findOrFail($dtrId);

        if (!$employeeProfile || ($dtr->employee_profile_id !== $employeeProfile->id && !$user->isAdmin())) {
            abort(403, 'Unauthorized access');
        }

        $dtr->calculateTotals();
        $attendanceLogs = AttendanceLog::where('employee_profile_id', $dtr->employee_profile_id)
            ->whereBetween('attendance_date', [$dtr->period_start, $dtr->period_end])
            ->orderBy('attendance_date')
            ->get();
        $approvedLeaves = LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dtr->period_end)
            ->whereDate('end_date', '>=', $dtr->period_start)
            ->get();
        $daysInPeriod = $this->getDaysInPeriod($dtr->period_start, $dtr->period_end, $attendanceLogs, $approvedLeaves, $dtr->employeeProfile, $dtr->status);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DTR');
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'MOTHER THERESA COLEGIO GROUP OF SCHOOLS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'DAILY TIME RECORD');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sheet->fromArray([
            ['Employee', $employeeProfile->first_name . ' ' . $employeeProfile->last_name, 'Employee No.', $employeeProfile->employee_number],
            ['Period', $dtr->period_start->format('M d, Y') . ' - ' . $dtr->period_end->format('M d, Y'), 'Status', ucfirst($dtr->status)],
            [],
            ['Total Hours', (float) ($dtr->total_hours ?? 0), 'Working Days', $dtr->getWorkingDays(), 'Days Present', (int) ($dtr->days_present ?? 0), 'Days Absent', (int) ($dtr->days_absent ?? 0)],
            [],
            ['Date', 'AM In', 'AM Out', 'PM In', 'PM Out', 'Late (min)', 'Status'],
        ], null, 'A4');

        $row = 10;
        foreach ($daysInPeriod as $day) {
            $log = $day['log'] ?? null;
            $sheet->fromArray([[
                $day['date']->format('M d, Y'),
                $log?->am_in?->format('h:i A') ?? '--',
                $log?->am_out?->format('h:i A') ?? '--',
                $log?->pm_in?->format('h:i A') ?? '--',
                $log?->pm_out?->format('h:i A') ?? '--',
                (int) ($log?->late_minutes ?? 0),
                ucfirst($day['status'] ?? 'N/A'),
            ]], null, 'A' . $row++);
        }

        $sheet->getStyle('A9:G9')->getFont()->setBold(true);
        $sheet->getStyle('A9:G9')->getFill()->setFillType('solid')->getStartColor()->setRGB('E5E7EB');
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'dtr-' . $employeeProfile->employee_number . '-' . $dtr->period_start->format('Ymd') . '-' . $dtr->period_end->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    /**
     * Submit DTR for approval
     */
    public function submit($dtrId)
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);

        if (!$employeeProfile) {
            return redirect('/dashboard')->with('error', 'Employee profile not found');
        }

        $dtr = DTR::findOrFail($dtrId);

        // Check authorization
        if ($dtr->employee_profile_id !== $employeeProfile->id) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        // Check if DTR can be submitted
        if (!$dtr->canSubmit()) {
            return redirect()->back()->with('error', 'DTR cannot be submitted in its current status');
        }

        $signaturePath = $employeeProfile->signature_path;
        if (!$signaturePath || !Storage::disk('public')->exists($signaturePath)) {
            $signaturePath = FinanceProfile::where('employee_profile_id', $employeeProfile->id)->value('signature_path');
        }
        if (!$signaturePath || !Storage::disk('public')->exists($signaturePath)) {
            $signaturePath = AdminProfile::where('employee_profile_id', $employeeProfile->id)->value('signature_path');
        }
        if (!$signaturePath || !Storage::disk('public')->exists($signaturePath)) {
            return redirect()->route('profile')->with('error', 'Please save your E-Signature in your Profile before submitting your DTR.');
        }

        // Submit DTR
        $dtr->submit();

        $this->notifyDTRBranchReviewers($employeeProfile, $dtr);

        return redirect()->back()->with('success', 'DTR submitted for approval');
    }

    public function requestAttendanceAdjustment(Request $request, AttendanceLog $attendance)
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);

        if (!$employeeProfile || $attendance->employee_profile_id !== $employeeProfile->id) {
            abort(403);
        }

        $request->validate([
            'corrected_time_in' => 'nullable|date_format:H:i',
            'corrected_pm_in' => 'nullable|date_format:H:i',
            'corrected_time_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $dtrStatus = $attendance->getDtrStatus();
        $hasCorrection = $request->filled('corrected_time_in')
            || $request->filled('corrected_pm_in')
            || $request->filled('corrected_time_out');
        $missingMorning = !$attendance->am_in && !$request->filled('corrected_time_in');
        $missingAfternoon = (!$attendance->pm_in || !$attendance->pm_out)
            && (!$request->filled('corrected_pm_in') || !$request->filled('corrected_time_out'));
        if (!$hasCorrection || ($dtrStatus === 'Half Day' && ($missingMorning || $missingAfternoon))) {
            return back()->with('error', 'Please enter the corrected attendance time before submitting.');
        }

        $legacyApprovedWithoutCorrection = $attendance->override_status === 'approved'
            && !$attendance->corrected_time_in
            && $attendance->am_in
            && $attendance->am_in->format('H:i:s') > '07:00:00';

        if (!$legacyApprovedWithoutCorrection && !in_array($dtrStatus, ['Late', 'Late / Early Out', 'Early Out', 'Half Day'], true)) {
            return back()->with('error', 'Only late attendance can request a Present adjustment.');
        }

        if (!$legacyApprovedWithoutCorrection && in_array($attendance->override_status, ['pending_branch', 'pending_system_admin', 'approved'], true)) {
            return back()->with('error', 'This attendance adjustment is already active or approved.');
        }

        $attendance->update([
            'override_status' => 'pending_branch',
            'override_reason' => $request->reason,
            'corrected_time_in' => $this->correctedDateTime($attendance, $request->corrected_time_in),
            'corrected_pm_in' => $this->correctedDateTime($attendance, $request->corrected_pm_in),
            'corrected_time_out' => $this->correctedDateTime($attendance, $request->corrected_time_out),
            'override_requested_by' => $user->id,
            'override_reviewed_by' => null,
            'override_reviewed_at' => null,
        ]);

        $this->notifyAttendanceBranchReviewers($attendance);

        return back()->with('success', 'Attendance adjustment request submitted for Branch Head review.');
    }

    private function correctedDateTime(AttendanceLog $attendance, ?string $time): ?Carbon
    {
        return $time ? Carbon::createFromFormat(
            'Y-m-d H:i',
            $attendance->attendance_date->format('Y-m-d') . ' ' . $time,
            config('app.timezone')
        ) : null;
    }

    private function notifyAttendanceBranchReviewers(AttendanceLog $attendance): void
    {
        $profile = $attendance->employeeProfile;
        User::where(function ($query) use ($profile) {
            $query->where(function ($branchQuery) use ($profile) {
                $branchQuery->where('role', 'branch_head')
                    ->whereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $profile->branch_id));
            })->orWhere(function ($branchQuery) use ($profile) {
                $branchQuery->where('role', 'admin')->where('admin_type', 'branch_admin')
                    ->where(function ($userQuery) use ($profile) {
                        $userQuery->where('branch_id', $profile->branch_id)
                            ->orWhereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $profile->branch_id));
                    });
            });
        })->where('is_active', true)->get()->each(fn ($recipient) => $recipient->notify(new SystemNotification(
            'Attendance adjustment needs review',
            'An employee requested to mark a late time-in as Present.',
            'attendance_adjustment_pending_branch',
            route('admin.dtr.index')
        )));
    }

    private function notifyDTRBranchReviewers(EmployeeProfile $profile, DTR $dtr): void
    {
        User::where(function ($query) use ($profile) {
                $query->where(function ($branchQuery) use ($profile) {
                    $branchQuery->where('role', 'branch_head')
                        ->whereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $profile->branch_id));
                })->orWhere(function ($branchQuery) use ($profile) {
                    $branchQuery->where('role', 'admin')
                        ->where('admin_type', 'branch_admin')
                        ->where(function ($userQuery) use ($profile) {
                            $userQuery->where('branch_id', $profile->branch_id)
                                ->orWhereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $profile->branch_id));
                        });
                });
            })
            ->where('is_active', true)
            ->get()
            ->each(fn ($recipient) => $recipient->notify(new SystemNotification(
                'DTR needs review',
                'An employee DTR is waiting for Branch Head review.',
                'dtr_pending_branch',
                route('admin.dtr.show', $dtr->id)
            )));
    }

    /**
     * Get DTR statistics summary
     */
    public function summary()
    {
        $user = Auth::user();
        $employeeProfile = $this->resolveEmployeeProfileForCurrentUser($user);

        if (!$employeeProfile) {
            return redirect('/dashboard')->with('error', 'Employee profile not found');
        }

        DTR::generateForCurrentPeriod($employeeProfile->id);

        $currentDTR = $this->findCurrentDTR($employeeProfile->id);

        if (!$currentDTR) {
            return redirect()->route('employee.dtr.index')->with('error', 'No DTR has been generated for the current period yet.');
        }
        
        // Get previous DTR
        $previousDTR = DTR::where('employee_profile_id', $employeeProfile->id)
            ->where('period_end', '<', $currentDTR->period_start)
            ->orderBy('period_end', 'desc')
            ->first();

        $currentStats = [
            'total_hours' => $currentDTR->getTotalHoursWorked(),
            'total_overtime' => $currentDTR->getTotalOvertimeHours(),
            'days_present' => $currentDTR->getDaysPresent(),
            'working_days' => $currentDTR->getWorkingDays(),
        ];

        $previousStats = $previousDTR ? [
            'total_hours' => $previousDTR->getTotalHoursWorked(),
            'total_overtime' => $previousDTR->getTotalOvertimeHours(),
            'days_present' => $previousDTR->getDaysPresent(),
            'working_days' => $previousDTR->getWorkingDays(),
        ] : null;

        return view('employee.dtr.summary', compact('currentDTR', 'previousDTR', 'currentStats', 'previousStats', 'employeeProfile'));
    }

    private function calculateLogHours($log): float
    {
        if (!$log) {
            return 0.0;
        }

        $minutes = 0;

        if ($log->am_in && $log->am_out) {
            $minutes += $log->am_in->diffInMinutes($log->am_out);
        }

        if ($log->pm_in && $log->pm_out) {
            $minutes += $log->pm_in->diffInMinutes($log->pm_out);
        }

        if (!$minutes && $log->am_in && $log->pm_out) {
            $minutes = max(0, $log->am_in->diffInMinutes($log->pm_out) - 60);
        }

        return round($minutes / 60, 2);
    }

    private function calculatePeriodHours($attendanceLogs): float
    {
        $total = 0.0;

        foreach ($attendanceLogs as $log) {
            if ($log && !in_array(strtolower((string) $log->status), ['absent', 'a', 'leave', 'leave_paid', 'on leave'], true)) {
                $total += $this->calculateLogHours($log);
            }
        }

        return round($total, 2);
    }

    /**
     * Prepare days data for calendar view
     */
    private function getDaysInPeriod($start, $end, $attendanceLogs, $approvedLeaves = null, $employeeProfile = null, $dtrStatus = null)
    {
        $days = [];
        $current = $start->copy();

        $logsByDate = $attendanceLogs->keyBy(function($log) {
            return $log->attendance_date->format('Y-m-d');
        });
        $approvedLeaves = $approvedLeaves ?: collect();
        $workingDayService = app(\App\Services\WorkingDayService::class);
        $branchId = $employeeProfile?->branch_id;

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $log = $logsByDate->get($dateStr);
            $isHoliday = $workingDayService->isHoliday($current, $branchId);
            $isWorkingDay = $workingDayService->isWorkingDay($current, $branchId);
            $approvedLeave = $isWorkingDay ? $approvedLeaves->first(function ($leave) use ($current) {
                return $current->betweenIncluded($leave->start_date, $leave->end_date);
            }) : null;
            $isApprovedLeave = (bool) $approvedLeave;
            $isLWOP = $isApprovedLeave && (bool) $approvedLeave->is_absent;

            $dayData = [
                'date' => $current->copy(),
                'day_name' => $current->format('l'),
                'day_number' => $current->day,
                'is_weekend' => in_array($current->dayOfWeek, [0, 6]),
                'is_holiday' => $isHoliday,
                'log' => $log,
            ];

            if ($isLWOP) {
                $dayData['status'] = 'lwop';
                $dayData['am_in'] = '--';
                $dayData['am_out'] = '--';
                $dayData['pm_in'] = '--';
                $dayData['pm_out'] = '--';
                $dayData['late_minutes'] = 0;
                $dayData['overtime'] = 0;
            } elseif ($isApprovedLeave) {
                $dayData['status'] = 'leave';
                $dayData['am_in'] = '--';
                $dayData['am_out'] = '--';
                $dayData['pm_in'] = '--';
                $dayData['pm_out'] = '--';
                $dayData['late_minutes'] = 0;
                $dayData['overtime'] = 0;
            } elseif ($log) {
                $logStatus = strtolower((string) $log->status);
                $hasLateMinutes = (int) ($log->late_minutes ?? 0) > 0;
                $scheduledStart = $current->copy()->setTime(7, 0, 0);
                $isAtOrAfterScheduledStart = $log->am_in && $log->am_in->greaterThanOrEqualTo($scheduledStart);
                $dayData['status'] = in_array($logStatus, ['leave', 'leave_paid', 'on leave'])
                    ? 'leave'
                    : (in_array($logStatus, ['absent', 'a']) ? 'absent' : ($logStatus === 'late' || $hasLateMinutes || $isAtOrAfterScheduledStart ? 'late' : 'present'));
                $dayData['am_in'] = $log->am_in ? $log->am_in->format('h:i A') : '--';
                $dayData['am_out'] = $log->am_out ? $log->am_out->format('h:i A') : '--';
                $dayData['pm_in'] = $log->pm_in ? $log->pm_in->format('h:i A') : '--';
                $dayData['pm_out'] = $log->pm_out ? $log->pm_out->format('h:i A') : '--';
                $dayData['late_minutes'] = (int) ($log->late_minutes ?? 0);
                if ($log->am_in) {
                    $scheduledStart = $log->am_in->copy()->setTime(7, 0, 0);
                    if ($log->am_in->greaterThanOrEqualTo($scheduledStart)) {
                        $dayData['late_minutes'] = max($dayData['late_minutes'], (int) $scheduledStart->diffInMinutes($log->am_in));
                    }
                }
                $dayData['overtime'] = $log->overtime_hours ?? 0;
                $dayData['is_early_out'] = $log->pm_out && $log->pm_out->lt(\Carbon\Carbon::today()->setTime(17, 0, 0));
            } else {
                $dayData['status'] = $dayData['is_weekend']
                    ? 'weekend'
                    : ($isHoliday ? 'holiday' : ($dtrStatus === 'approved' ? 'absent' : 'empty'));
            }

            $days[] = $dayData;
            $current->addDay();
        }

        return $days;
    }

}
