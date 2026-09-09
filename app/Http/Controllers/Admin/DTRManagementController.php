<?php

namespace App\Http\Controllers\Admin;

use App\Models\DTR;
use App\Models\EmployeeProfile;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DTRManagementController
{
    /**
     * Show DTR management dashboard for admins
     */
    public function index()
    {
        $user = Auth::user();

        // Check authorization - only admins and finance officers can access
        if (!$user->isAdmin() && !$user->isFinanceOfficer()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $pendingQuery = DTR::query()->with('employeeProfile');
        // Keep all approved DTRs visible as history, including those already linked to payroll.
        $approvedQuery = DTR::where('status', 'approved')->with(['employeeProfile.branch', 'payrollEntry']);

        if ($user->isBranchAdmin()) {
            $pendingQuery->where('status', 'submitted');
            $branchId = $user->getEffectiveBranchId();
            if ($branchId) {
                $pendingQuery->whereHas('employeeProfile', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
                $approvedQuery->whereHas('employeeProfile', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            } else {
                $pendingQuery->whereRaw('0 = 1');
                $approvedQuery->whereRaw('0 = 1');
            }
        } elseif ($user->isSuperAdmin()) {
            $pendingQuery->where('status', 'pending_system_admin');
        } elseif ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $ownEmployeeId = $financeProfile?->employee_profile_id;

            if ($user->isFinanceHead() && $financeProfile?->branch_id) {
                $pendingQuery->where('status', 'approved');
                $pendingQuery->whereHas('employeeProfile', function ($query) use ($financeProfile) {
                    $query->where('branch_id', $financeProfile->branch_id);
                });
                $approvedQuery->whereHas('employeeProfile', function ($query) use ($financeProfile) {
                    $query->where('branch_id', $financeProfile->branch_id);
                });
            } elseif (!$ownEmployeeId) {
                $pendingQuery->whereRaw('0 = 1');
                $approvedQuery->whereRaw('0 = 1');
            } else {
                $pendingQuery->where('employee_profile_id', $ownEmployeeId)->where('status', 'approved');
                $approvedQuery->where('employee_profile_id', $ownEmployeeId)->where('status', 'approved');
            }
        }

        // Get all DTRs with pending approval (for Branch Head / admins)
        $pendingDTRs = $pendingQuery
            ->orderBy('period_end', 'desc')
            ->paginate(20, ['*'], 'pending_dtr_page');

        // Get approved DTRs ready for payroll (for finance officers)
        $approvedDTRs = $approvedQuery
            ->orderBy('period_end', 'desc')
            ->paginate(20, ['*'], 'approved_dtr_page');

        $totalDTRsQuery = DTR::query();
        $approvedDTRsCountQuery = DTR::where('status', 'approved');
        $pendingCountQuery = DTR::query();

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $ownEmployeeId = $financeProfile?->employee_profile_id;
            if ($user->isFinanceHead() && $financeProfile?->branch_id) {
                $totalDTRsQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $financeProfile->branch_id));
                $approvedDTRsCountQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $financeProfile->branch_id));
                $pendingCountQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $financeProfile->branch_id))->where('status', 'approved');
            } elseif ($ownEmployeeId) {
                $totalDTRsQuery->where('employee_profile_id', $ownEmployeeId);
                $approvedDTRsCountQuery->where('employee_profile_id', $ownEmployeeId);
                $pendingCountQuery->where('employee_profile_id', $ownEmployeeId)->where('status', 'approved');
            } else {
                $totalDTRsQuery->whereRaw('0 = 1');
                $approvedDTRsCountQuery->whereRaw('0 = 1');
                $pendingCountQuery->whereRaw('0 = 1');
            }
        }

        // Get DTR stats
        $totalDTRs = $totalDTRsQuery->count();
        $approvedDTRsCount = $approvedDTRsCountQuery->count();
        $pendingCount = $user->isBranchAdmin()
            ? DTR::where('status', 'submitted')->count()
            : ($user->isFinanceOfficer() ? $pendingCountQuery->count() : DTR::where('status', 'pending_system_admin')->count());
        $rejectedDTRs = $user->isFinanceOfficer() ? 0 : DTR::where('status', 'rejected')->count();

        $canApprove = $user->isAdmin() && ($user->isSuperAdmin() || $user->isBranchAdmin());

        return view('admin.dtr.index', compact(
            'pendingDTRs',
            'approvedDTRs',
            'totalDTRs',
            'approvedDTRsCount',
            'pendingCount',
            'rejectedDTRs',
            'canApprove'
        ));
    }

    public function exportSubmittedExcel()
    {
        $user = Auth::user();

        if (!$user->isBranchAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Only Branch Heads and administrators can export submitted DTRs.');
        }

        $query = DTR::with('employeeProfile.branch')
            ->where('status', 'submitted')
            ->orderBy('period_start')
            ->orderBy('employee_profile_id');

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            $query->whereHas('employeeProfile', fn ($profileQuery) => $profileQuery->where('branch_id', $branchId));
        }

        $dtrs = $query->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Submitted DTRs');
        $sheet->mergeCells('A1:Q1');
        $sheet->setCellValue('A1', 'MOTHER THERESA COLEGIO GROUP OF SCHOOLS - SUBMITTED DTRs');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $headers = [
            'Employee', 'Employee No.', 'Branch', 'DTR Period', 'DTR Status', 'Date',
            'AM In', 'AM Out', 'PM In', 'PM Out', 'Daily Hours', 'Late (min)',
            'Overtime (hrs)', 'Daily Status', 'Correction Status', 'Correction Reason', 'Submitted At',
        ];
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:Q3')->getFont()->setBold(true);
        $sheet->getStyle('A3:Q3')->getFill()->setFillType('solid')->getStartColor()->setRGB('D9EAF7');

        $row = 4;
        foreach ($dtrs as $dtr) {
            $employee = $dtr->employeeProfile;
            $logs = AttendanceLog::where('employee_profile_id', $dtr->employee_profile_id)
                ->whereBetween('attendance_date', [$dtr->period_start, $dtr->period_end])
                ->orderBy('attendance_date')
                ->get();
            $dtr->calculateTotals();
            $summary = $dtr->getCalculationBreakdown();
            $period = $dtr->period_start->format('M d, Y') . ' - ' . $dtr->period_end->format('M d, Y');

            if ($logs->isEmpty()) {
                $sheet->fromArray([[
                    $employee?->first_name . ' ' . $employee?->last_name, $employee?->employee_number,
                    $employee?->branch?->branch_name, $period, ucfirst($dtr->status), '--',
                    '--', '--', '--', '--', 0, 0, 0, 'No attendance log',
                    $dtr->status, $dtr->remarks ?? '', optional($dtr->created_at)?->format('M d, Y h:i A'),
                ]], null, 'A' . $row++);
                continue;
            }

            foreach ($logs as $log) {
                $dailyHours = $log->am_in && $log->pm_out
                    ? round($log->am_in->diffInMinutes($log->pm_out) / 60, 2)
                    : 0;
                $sheet->fromArray([[
                    $employee?->first_name . ' ' . $employee?->last_name, $employee?->employee_number,
                    $employee?->branch?->branch_name, $period, ucfirst($dtr->status), $log->attendance_date->format('M d, Y'),
                    $log->am_in?->format('h:i A') ?? '--', $log->am_out?->format('h:i A') ?? '--',
                    $log->pm_in?->format('h:i A') ?? '--', $log->pm_out?->format('h:i A') ?? '--',
                    $dailyHours, (int) ($log->late_minutes ?? 0), (float) ($log->overtime_hours ?? 0),
                    $log->getDtrStatus(), $log->override_status ?? 'none', $log->override_reason ?? '',
                    optional($dtr->created_at)?->format('M d, Y h:i A'),
                ]], null, 'A' . $row++);
            }
        }

        foreach (range('A', 'Q') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A4');

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'submitted-dtrs-' . now()->format('Ymd-His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Show DTR details for approval
     */
    public function show($dtrId)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isFinanceOfficer()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $isOutsideBranch = !$financeProfile || $dtr->employeeProfile?->branch_id !== $financeProfile->branch_id;
            $canView = $user->isFinanceHead()
                ? !$isOutsideBranch
                : $dtr->employee_profile_id === $financeProfile?->employee_profile_id;
            if (!$canView) {
                return redirect('/dashboard')->with('error', 'You can only view DTR records in your assigned scope.');
            }
        }
        
        $attendanceLogs = AttendanceLog::where('employee_profile_id', $dtr->employee_profile_id)
            ->whereBetween('attendance_date', [$dtr->period_start, $dtr->period_end])
            ->orderBy('attendance_date')
            ->get();

        $approvedLeaves = \App\Models\LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dtr->period_end)
            ->whereDate('end_date', '>=', $dtr->period_start)
            ->get();

        $breakdown = $dtr->getCalculationBreakdown();
        $adminDaysAbsent = 0;
        $current = $dtr->period_start->copy();
        while ($current <= $dtr->period_end) {
            if (!$current->isWeekend()) {
                $dateKey = $current->toDateString();
                $hasLog = $attendanceLogs->contains(fn ($log) => $log->attendance_date->toDateString() === $dateKey);
                $hasLeave = $approvedLeaves->contains(fn ($leave) => $current->betweenIncluded($leave->start_date, $leave->end_date));
                if (!$hasLog && !$hasLeave) {
                    $adminDaysAbsent++;
                }
            }
            $current->addDay();
        }

        $adminDaysAbsent += $attendanceLogs->filter(fn ($log) => in_array(strtolower((string) $log->status), ['absent', 'a'], true))->count();
        $totalEarlyOutMinutes = $attendanceLogs->sum(function ($log) {
            if (!$log->pm_out) {
                return 0;
            }

            $scheduledEnd = $log->pm_out->copy()->setTime(17, 0, 0);

            return $log->pm_out->lt($scheduledEnd)
                ? (int) $log->pm_out->diffInMinutes($scheduledEnd)
                : 0;
        });

        $stats = [
            'total_hours' => $breakdown['total_hours'],
            'total_overtime' => $dtr->getTotalOvertimeHours(),
            'total_late_minutes' => $dtr->getTotalLateMinutes(),
            'total_early_out_minutes' => $totalEarlyOutMinutes,
            'days_present' => $breakdown['days_present'],
            'days_absent' => $adminDaysAbsent,
            'total_paid_leave' => $breakdown['paid_leave'],
            'total_leave_without_pay' => $breakdown['leave_without_pay'],
            'working_days' => max(1, $breakdown['days_present'] + $breakdown['days_absent'] + $breakdown['paid_leave'] + $breakdown['leave_without_pay']),
        ];

        return view('admin.dtr.show', compact('dtr', 'attendanceLogs', 'approvedLeaves', 'stats'));
    }

    /**
     * Approve DTR (Admin only)
     */
    public function approve($dtrId)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Only administrators can approve DTRs');
        }

        if (!$user->isSuperAdmin() && !$user->isBranchAdmin()) {
            return redirect()->back()->with('error', 'Only the Branch Head or System Administrator can approve DTRs');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || !$dtr->employeeProfile || $dtr->employeeProfile->branch_id !== $branchId) {
                return redirect()->back()->with('error', 'You can only approve DTRs from your branch.');
            }

            if ($dtr->status !== 'submitted') {
                return redirect()->back()->with('error', 'This DTR is no longer pending branch approval.');
            }

            $dtr->approve($user->id, 'branch_admin');
            $this->notifySystemReviewers($dtr);
            return redirect()->back()->with('success', 'DTR approved by Branch Head and forwarded to the System Administrator for review.');
        }

        if ($dtr->status !== 'pending_system_admin') {
            return redirect()->back()->with('error', 'This DTR is not awaiting System Administrator review.');
        }

        $dtr->approve($user->id, 'super_admin');

        $dtr->employeeProfile?->user?->notify(new SystemNotification(
            'DTR approved',
            'Your DTR has been approved by the System Administrator.',
            'dtr_approved',
            route('employee.dtr.show', $dtr->id)
        ));

        return redirect()->back()->with('success', 'DTR reviewed and approved by the System Administrator.');
    }

    /**
     * Reject DTR with remarks (Admin only)
     */
    public function reject($dtrId)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Only administrators can reject DTRs');
        }

        if (!$user->isSuperAdmin() && !$user->isBranchAdmin()) {
            return redirect()->back()->with('error', 'Only the Branch Head or System Administrator can reject DTRs');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || !$dtr->employeeProfile || $dtr->employeeProfile->branch_id !== $branchId) {
                return redirect()->back()->with('error', 'You can only reject DTRs from your branch.');
            }
        }

        // Reset DTR to draft status for employee to resubmit
        $dtr->update([
            'status' => 'draft',
            'remarks' => request('remarks') ?? 'Rejected by administrator'
        ]);

        $dtr->employeeProfile?->user?->notify(new SystemNotification(
            'DTR rejected',
            'Your DTR was returned for correction. Please review the remarks and submit it again.',
            'dtr_rejected',
            route('employee.dtr.show', $dtr->id)
        ));

        return redirect()->back()->with('success', 'DTR rejected and sent back to employee');
    }

    public function approveAttendanceAdjustment(AttendanceLog $attendance)
    {
        $user = Auth::user();
        if (!$user->isAdmin() || (!$user->isBranchAdmin() && !$user->isSuperAdmin())) {
            abort(403);
        }

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || $attendance->employeeProfile?->branch_id !== $branchId) {
                abort(403);
            }
            if ($attendance->override_status !== 'pending_branch') {
                return back()->with('error', 'This adjustment is not awaiting Branch Head review.');
            }

            $attendance->update(['override_status' => 'pending_system_admin', 'override_reviewed_by' => $user->id, 'override_reviewed_at' => now()]);
            $this->notifyAttendanceSystemReviewers($attendance, 'Attendance adjustment needs final approval', 'attendance_adjustment_pending_system');

            return back()->with('success', 'Adjustment forwarded to HR/System Administrator.');
        }

        if ($attendance->override_status !== 'pending_system_admin') {
            return back()->with('error', 'This adjustment is not awaiting final review.');
        }
        if (!$attendance->corrected_time_in && !$attendance->corrected_pm_in && !$attendance->corrected_time_out) {
            return back()->with('error', 'This adjustment has no corrected attendance time and cannot be approved.');
        }

        $updates = [
            'override_status' => 'approved',
            'override_reviewed_by' => $user->id,
            'override_reviewed_at' => now(),
            'status' => 'present',
            'late_minutes' => 0,
        ];
        if ($attendance->corrected_time_in) {
            $updates['am_in'] = $attendance->corrected_time_in;
        }
        if ($attendance->corrected_pm_in) {
            $updates['pm_in'] = $attendance->corrected_pm_in;
        }
        if ($attendance->corrected_time_out) {
            $updates['pm_out'] = $attendance->corrected_time_out;
        }
        $attendance->update($updates);
        $dtr = DTR::where('employee_profile_id', $attendance->employee_profile_id)
            ->whereDate('period_start', '<=', $attendance->attendance_date)
            ->whereDate('period_end', '>=', $attendance->attendance_date)
            ->first();
        if ($dtr) {
            $dtr->calculateTotals()->save();
        }
        $attendance->employeeProfile?->user?->notify(new SystemNotification(
            'Attendance adjustment approved',
            'Your attendance time correction was approved as Present.',
            'attendance_adjustment_approved',
            route('employee.dtr.show', $this->dtrIdForAttendance($attendance))
        ));

        return back()->with('success', 'Attendance adjustment approved as Present.');
    }

    public function rejectAttendanceAdjustment(AttendanceLog $attendance)
    {
        $user = Auth::user();
        if (!$user->isAdmin() || (!$user->isBranchAdmin() && !$user->isSuperAdmin())) {
            abort(403);
        }

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || $attendance->employeeProfile?->branch_id !== $branchId) {
                abort(403);
            }
        }

        if (!in_array($attendance->override_status, ['pending_branch', 'pending_system_admin'], true)) {
            return back()->with('error', 'This adjustment is no longer pending.');
        }

        $attendance->update(['override_status' => 'rejected', 'override_reviewed_by' => $user->id, 'override_reviewed_at' => now()]);
        $attendance->employeeProfile?->user?->notify(new SystemNotification(
            'Attendance adjustment rejected',
            'Your request to mark the late time-in as Present was rejected.',
            'attendance_adjustment_rejected',
            route('employee.dtr.show', $this->dtrIdForAttendance($attendance))
        ));

        return back()->with('success', 'Attendance adjustment rejected.');
    }

    private function notifyAttendanceSystemReviewers(AttendanceLog $attendance, string $title, string $type): void
    {
        User::where('role', 'admin')->where(function ($query) {
            $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
        })->where('is_active', true)->get()->each(fn ($recipient) => $recipient->notify(new SystemNotification(
            $title,
            'An attendance adjustment was forwarded for final review.',
            $type,
            route('admin.dtr.show', $this->dtrIdForAttendance($attendance))
        )));
    }

    private function dtrIdForAttendance(AttendanceLog $attendance): int
    {
        return (int) DTR::where('employee_profile_id', $attendance->employee_profile_id)
            ->whereDate('period_start', '<=', $attendance->attendance_date)
            ->whereDate('period_end', '>=', $attendance->attendance_date)
            ->orderByDesc('id')
            ->value('id');
    }

    private function notifySystemReviewers(DTR $dtr): void
    {
        User::where('role', 'admin')
            ->where(function ($query) {
                $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
            })
            ->where('is_active', true)
            ->get()
            ->each(fn ($recipient) => $recipient->notify(new SystemNotification(
                'DTR needs final approval',
                'A DTR has been approved by the Branch Head and is waiting for final review.',
                'dtr_pending_system',
                route('admin.dtr.show', $dtr->id)
            )));
    }

    /**
     * Show all DTRs for a specific employee
     */
    public function employeeDTRs($employeeId)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isFinanceOfficer()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $employeeProfile = EmployeeProfile::findOrFail($employeeId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            if (!$financeProfile || $employeeProfile->id !== $financeProfile->employee_profile_id) {
                return redirect('/dashboard')->with('error', 'You can only view your own DTR records.');
            }
        }
        
        $dtrs = DTR::where('employee_profile_id', $employeeId)
            ->orderBy('period_start', 'desc')
            ->paginate(20);

        return view('admin.dtr.employee-dtrs', compact('employeeProfile', 'dtrs'));
    }
}
