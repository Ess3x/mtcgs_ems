<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\LeaveRequest;

class DTR extends Model
{
    use SoftDeletes;

    private array $runtimeBreakdown = [];

    protected $table = 'dtrs';

    protected $fillable = [
        'employee_profile_id',
        'period_start',
        'period_end',
        'status',
        'remarks',
        'total_hours',
        'days_present',
        'days_absent',
        'overtime_hours',
        'late_minutes',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the employee profile associated with this DTR
     */
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    /**
     * Get payroll entry for this DTR
     */
    public function payrollEntry()
    {
        return $this->hasOne(PayrollEntry::class, 'dtr_id');
    }

    /**
     * Get the user who approved this DTR
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all attendance logs for this DTR period
     */
    public function attendanceLogs()
    {
        return AttendanceLog::where('employee_profile_id', $this->employee_profile_id)
            ->whereDate('attendance_date', '>=', $this->period_start->toDateString())
            ->whereDate('attendance_date', '<=', $this->period_end->toDateString())
            ->orderBy('attendance_date')
            ->get();
    }

    /**
     * Get attendance logs relationship
     */
    public function logs()
    {
        return $this->belongsToMany(AttendanceLog::class, 'dtr_attendance_logs', 'dtr_id', 'attendance_log_id');
    }

    public static function normalizeLateMinutesForLog($log): int
    {
        if (!$log) {
            return 0;
        }

        $lateMinutes = (int) ($log->late_minutes ?? 0);
        if ($lateMinutes <= 0) {
            return 0;
        }

        $amIn = $log->am_in instanceof Carbon ? $log->am_in : ($log->am_in ? Carbon::parse($log->am_in) : null);
        $pmIn = $log->pm_in instanceof Carbon ? $log->pm_in : ($log->pm_in ? Carbon::parse($log->pm_in) : null);

        if (($amIn && ($amIn->hour < 5 || $amIn->hour >= 18)) || ($pmIn && ($pmIn->hour < 12 || $pmIn->hour >= 20))) {
            return 0;
        }

        if ($lateMinutes > 240) {
            return 0;
        }

        return $lateMinutes;
    }

    /**
     * Calculate totals from attendance logs
     */
    public function calculateTotals()
    {
        $logs = $this->attendanceLogs();
        $logsByDate = $logs->keyBy(function ($log) {
            return $log->attendance_date->format('Y-m-d');
        });
        $workingDayService = app(\App\Services\WorkingDayService::class);
        $branchId = $this->employeeProfile?->branch_id;

        $daysPresent = 0;
        $daysAbsent = 0;
        $paidLeaveDays = 0;
        $leaveWithoutPayDays = 0;
        $totalHours = 0;
        $earlyOutMinutes = 0;
        $lateMinutesTotal = 0;

        $approvedLeaves = LeaveRequest::where('employee_profile_id', $this->employee_profile_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $this->period_end)
            ->whereDate('end_date', '>=', $this->period_start)
            ->get();

        $approvedLeaveByDate = [];
        foreach ($approvedLeaves as $leave) {
            $current = max($leave->start_date->copy(), $this->period_start->copy());
            $end = min($leave->end_date->copy(), $this->period_end->copy());
            while ($current <= $end) {
                if ($workingDayService->isWorkingDay($current, $branchId)) {
                    $approvedLeaveByDate[$current->format('Y-m-d')] = $leave;
                }
                $current->addDay();
            }
        }

        $current = $this->period_start->copy();
        while ($current <= $this->period_end) {
            if ($workingDayService->isWorkingDay($current, $branchId) || $logsByDate->has($current->format('Y-m-d'))) {
                $dateStr = $current->format('Y-m-d');
                $log = $logsByDate->get($dateStr);
                $approvedLeave = $approvedLeaveByDate[$dateStr] ?? null;
                $logStatus = $log ? strtolower((string) $log->status) : '';

                if ($approvedLeave && (bool) $approvedLeave->is_absent) {
                    $leaveWithoutPayDays++;
                } elseif ($approvedLeave) {
                    $paidLeaveDays++;
                } elseif (
                    !$log
                    && $this->status === 'approved'
                ) {
                    $daysAbsent++;
                } elseif ($log && in_array($logStatus, ['leave', 'leave_paid', 'on leave'])) {
                    $paidLeaveDays++;
                } elseif ($log && in_array($logStatus, ['absent', 'a'])) {
                    $daysAbsent++;
                } elseif ($log) {
                    $daysPresent++;
                    $normalizedLateMinutes = self::normalizeLateMinutesForLog($log);
                    $isValidMorningIn = $log->am_in
                        && $log->am_in->hour >= 5
                        && $log->am_in->hour < 18;
                    if ($isValidMorningIn) {
                        $scheduledStart = $log->am_in->copy()->setTime(7, 0, 0);
                        if ($log->am_in->greaterThanOrEqualTo($scheduledStart)) {
                            $lateMinutesTotal += max($normalizedLateMinutes, (int) $scheduledStart->diffInMinutes($log->am_in));
                        } else {
                            $lateMinutesTotal += $normalizedLateMinutes;
                        }
                    } else {
                        $lateMinutesTotal += $normalizedLateMinutes;
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
                    $totalHours += $minutes / 60;

                    if ($log->pm_out) {
                        $scheduledPmOut = $log->pm_out->copy()->setTimeFromTimeString(
                            $log->employeeProfile?->shift?->end_time ?: '17:00:00'
                        );
                        if ($log->pm_out->lt($scheduledPmOut)) {
                            $earlyOutMinutes += max(0, abs($scheduledPmOut->diffInMinutes($log->pm_out)));
                        }
                    }
                }
            }
            $current->addDay();
        }

        $this->days_present = $daysPresent;
        $this->total_hours = $totalHours;
        $this->overtime_hours = $logs->sum('overtime_hours') ?? 0;
        $this->late_minutes = $lateMinutesTotal;
        $this->days_absent = $daysAbsent;

        $this->runtimeBreakdown = [
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'paid_leave' => $paidLeaveDays,
            'leave_without_pay' => $leaveWithoutPayDays,
            'early_out_minutes' => $earlyOutMinutes,
            'total_hours' => $totalHours,
        ];

        if (Schema::hasColumn('dtrs', 'paid_leave_days')) {
            $this->attributes['paid_leave_days'] = $paidLeaveDays;
        }
        if (Schema::hasColumn('dtrs', 'leave_without_pay_days')) {
            $this->attributes['leave_without_pay_days'] = $leaveWithoutPayDays;
        }
        if (Schema::hasColumn('dtrs', 'early_out_minutes')) {
            $this->attributes['early_out_minutes'] = $earlyOutMinutes;
        }

        return $this;
    }

    public function getCalculationBreakdown(): array
    {
        $this->calculateTotals();

        $runtime = $this->runtimeBreakdown;

        return [
            'days_present' => (int) ($runtime['days_present'] ?? $this->days_present ?? 0),
            'days_absent' => (int) ($runtime['days_absent'] ?? $this->days_absent ?? 0),
            'paid_leave' => (int) ($runtime['paid_leave'] ?? 0),
            'leave_without_pay' => (int) ($runtime['leave_without_pay'] ?? 0),
            'early_out_minutes' => (int) ($runtime['early_out_minutes'] ?? 0),
            'total_hours' => (float) ($runtime['total_hours'] ?? $this->total_hours ?? 0),
        ];
    }

    public function getPaidLeaveDays(): int
    {
        return (int) ($this->getCalculationBreakdown()['paid_leave'] ?? 0);
    }

    public function getLeaveWithoutPayDays(): int
    {
        return (int) ($this->getCalculationBreakdown()['leave_without_pay'] ?? 0);
    }

    /**
     * Generate a new DTR for the current period
     */
    public static function generateForCurrentPeriod($employeeProfileId, $attendanceDate = null)
    {
        $today = $attendanceDate ? Carbon::parse($attendanceDate) : Carbon::today();
        [$period_start, $period_end] = self::cutoffPeriodFor($today);

        // Check if DTR already exists
        $existing = self::where('employee_profile_id', $employeeProfileId)
            ->whereDate('period_start', $period_start)
            ->whereDate('period_end', $period_end)
            ->first();

        if ($existing) {
            $existing->calculateTotals();
            $existing->save();

            return $existing;
        }

        // Create new DTR
        $dtr = self::create([
            'employee_profile_id' => $employeeProfileId,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'status' => 'draft',
        ]);

        // Calculate totals from attendance logs
        $dtr->calculateTotals();
        $dtr->save();

        return $dtr;
    }

    public static function cutoffPeriodFor(Carbon $date): array
    {
        if ($date->day <= 15) {
            return [$date->copy()->startOfMonth(), $date->copy()->day(15)];
        }

        return [$date->copy()->day(16), $date->copy()->endOfMonth()];
    }

    /**
     * Get total hours worked
     */
    public function getTotalHoursWorked()
    {
        return $this->total_hours ?? 0;
    }

    /**
     * Get total overtime hours
     */
    public function getTotalOvertimeHours()
    {
        return $this->overtime_hours ?? 0;
    }

    /**
     * Get total late minutes
     */
    public function getTotalLateMinutes()
    {
        $this->calculateTotals();

        return (int) ($this->late_minutes ?? 0);
    }

    public function getTotalEarlyOutMinutes()
    {
        $this->calculateTotals();

        return (int) ($this->runtimeBreakdown['early_out_minutes'] ?? $this->early_out_minutes ?? 0);
    }

    /**
     * Get days present
     */
    public function getDaysPresent()
    {
        $this->calculateTotals();

        return (int) ($this->days_present ?? 0);
    }

    /**
     * Get days absent
     */
    public function getDaysAbsent()
    {
        $this->calculateTotals();

        return (int) ($this->days_absent ?? 0);
    }

    /**
     * Get working days in period
     */
    public function getWorkingDays()
    {
        return app(\App\Services\WorkingDayService::class)->countWorkingDays(
            $this->period_start,
            $this->period_end,
            $this->employeeProfile?->branch_id
        );
    }

    /**
     * Determine whether the DTR can be submitted by the employee.
     */
    public function canSubmit()
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    /**
     * Whether this DTR should be visible to a Branch Head in pending review.
     */
    public function isVisibleToBranchAdmin()
    {
        return $this->status === 'submitted';
    }

    /**
     * Whether this DTR should be visible to a System Administrator in pending review.
     */
    public function isVisibleToSystemAdmin()
    {
        return $this->status === 'pending_system_admin';
    }

    public function isVisibleToFinanceHead()
    {
        return $this->status === 'pending_finance_head';
    }

    /**
     * Whether this DTR should be visible to finance staff for payroll preparation.
     */
    public function isVisibleToFinanceStaff()
    {
        return $this->status === 'approved';
    }

    /**
     * Submit the DTR to the Branch Head for approval.
     */
    public function submit()
    {
        $this->status = 'submitted';
        $this->approved_by = null;
        $this->approved_at = null;
        $this->save();

        return $this;
    }

    /**
     * Mark DTR as approved or forward it for System Administrator review.
     */
    public function approve($approverId = null, $approverRole = null)
    {
        if ($this->status === 'submitted' && $approverRole === 'branch_admin') {
            $this->status = 'pending_system_admin';
        } elseif ($this->status === 'pending_system_admin' && in_array($approverRole, ['super_admin', 'system_admin'], true)) {
            $this->status = 'pending_finance_head';
        } elseif ($this->status === 'pending_finance_head' && $approverRole === 'finance_head') {
            $this->status = 'approved';
        } elseif ($this->status !== 'approved') {
            $this->status = 'approved';
        }

        if ($approverId) {
            $this->approved_by = $approverId;
        }
        $this->approved_at = now();
        $this->save();

        return $this;
    }
};
