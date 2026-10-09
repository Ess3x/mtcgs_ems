

<?php $__env->startSection('title', $isReadOnly ? 'View Payroll Entry' : 'Edit Payroll Entry'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .payroll-summary-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f5ff 100%);
        box-shadow: 0 10px 24px rgba(95, 62, 131, 0.05);
        height: 100%;
    }
    .payroll-summary-card .card-body {
        padding: 1rem 1.1rem;
    }
    .payroll-summary-label {
        display: block;
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #6b7280;
        font-weight: 700;
    }
    .payroll-summary-value {
        display: block;
        margin-top: 0.45rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: #312e81;
        line-height: 1.2;
    }
    .payroll-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 1.2rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.02);
    }
    .payroll-entry-page {
        max-width: 1120px;
        margin: 0 auto;
    }
    .payroll-entry-page .payroll-section .row > .col-md-3 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .payroll-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.7rem;
        border-bottom: 1px solid #eef2ff;
    }
    .payroll-section-header h5 {
        margin: 0;
        font-weight: 700;
        color: #2a2a59;
    }
    .payroll-section-header .badge {
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .payroll-field {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        height: calc(2.7rem + 2px);
    }
    .payroll-field:focus {
        background: #fff;
        border-color: #a855f7;
        box-shadow: 0 0 0 0.2rem rgba(168, 85, 247, 0.12);
    }
    .payroll-readonly {
        background: #f5f3ff;
        color: #4c1d95;
        font-weight: 600;
    }
    .payroll-currency-group {
        display: flex;
        width: 100%;
    }
    .payroll-currency-group .input-group-text {
        background: rgba(245, 243, 255, 0.9);
        border: 1px solid #e5e7eb;
        border-right: none;
        color: #4c1d95;
        font-weight: 700;
        min-width: 2.5rem;
        justify-content: center;
    }
    .payroll-currency-group .payroll-field {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .payroll-amount {
        color: #4c1d95;
        font-weight: 700;
    }
    .payroll-actions {
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid #eef2ff;
    }

    body.dark-mode .payroll-summary-card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.98) 0%, rgba(15, 23, 42, 0.98) 100%) !important;
        border-color: rgba(148, 163, 184, 0.35) !important;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.35);
        color: #f8fafc !important;
    }
    body.dark-mode .payroll-summary-card .card-body {
        background: transparent !important;
    }
    body.dark-mode .payroll-summary-label {
        color: #cbd5e1 !important;
        opacity: 1 !important;
    }
    body.dark-mode .payroll-summary-value {
        color: #f8fafc !important;
        text-shadow: none !important;
    }
    body.dark-mode .payroll-section {
        background: #182233 !important;
        border-color: rgba(148, 163, 184, 0.35) !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
    }
    body.dark-mode .payroll-section-header h5 {
        color: #f8fafc !important;
    }
    body.dark-mode .payroll-section-header .badge {
        background: rgba(99, 102, 241, 0.18) !important;
        color: #e2e8f0 !important;
    }
    body.dark-mode .payroll-field,
    body.dark-mode .payroll-field.form-control,
    body.dark-mode .payroll-field.form-select {
        background: #0f172a !important;
        color: #f8fafc !important;
        border-color: rgba(148, 163, 184, 0.5) !important;
        -webkit-text-fill-color: #f8fafc !important;
    }
    body.dark-mode .payroll-field:focus {
        background: #0f172a !important;
        border-color: #a78bfa !important;
        box-shadow: 0 0 0 0.2rem rgba(167, 139, 250, 0.25) !important;
    }
    body.dark-mode .payroll-readonly {
        background: rgba(91, 97, 177, 0.18) !important;
        color: #f8fafc !important;
        border-color: rgba(167, 139, 250, 0.45) !important;
        font-weight: 700 !important;
    }
    body.dark-mode .payroll-currency-group .input-group-text {
        background: rgba(15, 23, 42, 0.9) !important;
        color: #d8b4fe !important;
        border-color: rgba(148, 163, 184, 0.5) !important;
    }
    body.dark-mode .payroll-currency-group .payroll-field {
        background: #0f172a !important;
        color: #f8fafc !important;
        border-color: rgba(148, 163, 184, 0.5) !important;
    }
    body.dark-mode .payroll-amount {
        color: #d8b4fe !important;
    }
    body.dark-mode .form-label,
    body.dark-mode label,
    body.dark-mode .payroll-section label {
        color: #f8fafc !important;
    }
    body.dark-mode .text-muted,
    body.dark-mode p.text-muted,
    body.dark-mode .payroll-section p,
    body.dark-mode .payroll-section .text-muted {
        color: #cbd5e1 !important;
    }
    @media (max-width: 767.98px) {
        .payroll-entry-page .payroll-section .row > .col-md-3 {
            flex-basis: 100%;
            max-width: 100%;
        }
    }
    body.dark-mode input.payroll-field::placeholder,
    body.dark-mode .payroll-field::placeholder {
        color: rgba(226, 232, 240, 0.75) !important;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid payroll-entry-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?php echo e($isReadOnly ? 'View Payroll Entry' : 'Edit Payroll Entry'); ?></h1>
            <p class="text-muted mb-0">Employee: <?php echo e($entry->employee->first_name); ?> <?php echo e($entry->employee->last_name); ?> (<?php echo e($entry->employee->employee_number); ?>)</p>
            <p class="text-muted">Payroll Period: <?php echo e($entry->payrollPeriod->period_code); ?> (<?php echo e($entry->payrollPeriod->start_date); ?> to <?php echo e($entry->payrollPeriod->end_date); ?>)</p>
        </div>
        <div>
            <a href="<?php echo e(route('admin.payroll.entries', $entry->payroll_period_id)); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Entries
            </a>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Total Day Present</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['days_present'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Total Day Absent</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['days_absent'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Total Half Day</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['half_day_days'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Late Minutes</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['late_minutes'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Early Out Minutes</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['early_out_minutes'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Paid Leave</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['paid_leave'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Leave Without Pay</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['leave_without_pay'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Total Holiday</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['total_holidays'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Total Suspension</span>
                            <span class="payroll-summary-value"><?php echo e($dtrStats['total_suspensions'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="payroll-summary-card">
                        <div class="card-body">
                            <span class="payroll-summary-label">Total Suspended Hours</span>
                            <span class="payroll-summary-value"><?php echo e(number_format((float) ($dtrStats['total_suspended_hours'] ?? 0), 2)); ?> hrs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="<?php echo e(route('admin.payroll.entry.update', $entry->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <fieldset <?php echo e($isReadOnly ? 'disabled' : ''); ?>>

                <?php
                    $daysPresentValue = $dtrStats['days_present'] ?? $entry->days_present ?? 0;
                    $halfDayDaysValue = (int) ($dtrStats['half_day_days'] ?? 0);
                    $daysAbsentValue = (int) ($dtrStats['days_absent'] ?? $entry->days_absent ?? 0);
                    $isFinanceHead = $isFinanceHead ?? auth()->user()->isFinanceHead();
                    $canCorrectReturnedEntry = $canCorrectReturnedEntry ?? ($entry->correction_stage === 'fh_correction' && (auth()->user()?->isFinanceHead() || auth()->user()?->role === 'finance_head'));
                    $workingDaysValue = max(1, $entry->dtr?->getWorkingDays() ?? ($daysPresentValue + $daysAbsentValue + (int) ($dtrStats['paid_leave'] ?? 0) + (int) ($dtrStats['leave_without_pay'] ?? 0)));
                    $defaultDailyRateValue = $entry->basic_pay > 0 ? $entry->basic_pay / $workingDaysValue : 0;
                    $isDailyRateLocked = $entry->status === 'approved' || ($entry->payrollPeriod?->status === 'approved' && !$isFinanceHead);
                    $entryBreakdown = is_string($entry->payroll_breakdown) ? json_decode($entry->payroll_breakdown, true) ?? [] : (array) $entry->payroll_breakdown;
                    $holidayBonusValue = (float) old('holiday_bonus', $entryBreakdown['holiday_bonus'] ?? 0);
                    $holidayDeductionValue = (float) ($dtrStats['holiday_deduction'] ?? 0);
                    $suspensionDeductionValue = (float) ($dtrStats['suspension_deduction'] ?? 0);
                    $suspendedHoursToPayValue = (float) old('suspended_hours_to_pay', $entryBreakdown['suspended_hours_to_pay'] ?? ($dtrStats['total_suspended_hours'] ?? 0));
                    $storedDailyRateValue = isset($entryBreakdown['daily_rate']) && is_numeric($entryBreakdown['daily_rate'])
                        ? (float) $entryBreakdown['daily_rate']
                        : $defaultDailyRateValue;
                    $storedCashAdvanceValue = (float) ($entry->cash_advance_deduction ?? 0) > 0
                        ? (float) $entry->cash_advance_deduction
                        : (float) ($entryBreakdown['cash_advance_deduction'] ?? 0);
                    $cashAdvanceValue = old('cash_advance_deduction', $storedCashAdvanceValue);
                    $cashChargeValue = (float) ($entry->cash_charge_deduction ?? 0);
                    $dailyRateValue = $storedDailyRateValue;
                    $totalDailyRateValue = $daysPresentValue * $dailyRateValue;
                    $lateMinutesTotal = (int) ($dtrStats['late_minutes'] ?? 0);
                    $earlyOutMinutesTotal = (int) ($dtrStats['early_out_minutes'] ?? 0);
                    $lateDeductionValue = $lateMinutesTotal;
                    $earlyOutDeductionValue = $earlyOutMinutesTotal;
                    $paidLeaveTotal = (int) ($dtrStats['paid_leave'] ?? 0);
                    $leaveWithoutPayTotal = (int) ($dtrStats['leave_without_pay'] ?? 0);
                    $paidLeaveValue = $paidLeaveTotal * $dailyRateValue;
                    $leaveWithoutPayValue = $leaveWithoutPayTotal * $dailyRateValue;
                    $absentDeductionValue = $daysAbsentValue * $dailyRateValue;
                    $halfDayDeductionValue = $halfDayDaysValue * $dailyRateValue * 0.5;
                    $suspensionPayValue = $suspendedHoursToPayValue * \App\Services\PayrollComputationService::SUSPENSION_HOURLY_RATE;
                    $overtimePayDisplayValue = (float) ($entry->overtime_pay ?? 0) + $holidayBonusValue + $suspensionPayValue;
                ?>
                <div class="payroll-section">
                    <div class="payroll-section-header">
                        <h5>Payroll Rate Setup</h5>
                        <span class="badge bg-light text-dark">Base Details</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Basic Pay</label>
                                <div class="payroll-currency-group input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" name="basic_pay" class="form-control payroll-field" value="<?php echo e(number_format($entry->basic_pay, 2, '.', '')); ?>" min="0" <?php echo e($isFinanceHead ? '' : 'readonly'); ?>>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Daily Rate</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" id="daily_rate_input" name="daily_rate" class="form-control payroll-field status-controlled-field" value="<?php echo e(number_format($dailyRateValue, 2, '.', '')); ?>" <?php echo e($isDailyRateLocked ? 'readonly' : ''); ?>>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Total Daily Rate</label>
                            <input type="text" id="total_daily_rate_display" class="form-control payroll-field payroll-readonly" value="₱<?php echo e(number_format($totalDailyRateValue, 2)); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="payroll-section">
                    <div class="payroll-section-header">
                        <h5>Additions</h5>
                        <span class="badge bg-success-subtle text-success">Earnings</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label>Holiday Bonus</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="holiday_bonus" id="holiday_bonus_input" class="form-control payroll-field status-controlled-field" value="<?php echo e(number_format($holidayBonusValue, 2, '.', '')); ?>" min="0" <?php echo e($isFinanceHead ? '' : 'readonly'); ?>>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Overtime Pay</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="text" id="overtime_pay_display" class="form-control payroll-field payroll-readonly" value="<?php echo e(number_format($overtimePayDisplayValue, 2, '.', '')); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Suspended Work Hours to Pay</label>
                            <input type="number" step="0.25" min="0" max="24" name="suspended_hours_to_pay" id="suspended_hours_to_pay_input" class="form-control payroll-field status-controlled-field" value="<?php echo e(number_format($suspendedHoursToPayValue, 2, '.', '')); ?>" <?php echo e($isFinanceHead ? '' : 'readonly'); ?>>
                            <small class="text-muted">₱60 per suspended work hour.</small>
                        </div>
                        <div class="col-md-3">
                            <label>Suspension Pay</label>
                            <input type="text" id="suspension_pay_display" class="form-control payroll-field payroll-readonly" value="₱<?php echo e(number_format($suspensionPayValue, 2)); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Total Paid Leave</label>
                            <input type="number" name="paid_leave" class="form-control payroll-field payroll-readonly" value="<?php echo e(old('paid_leave', $paidLeaveTotal)); ?>" min="0" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Paid Leave Rate</label>
                            <input type="text" id="paid_leave_rate_display" class="form-control payroll-field payroll-readonly" value="₱<?php echo e(number_format($paidLeaveValue, 2)); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="payroll-section">
                    <div class="payroll-section-header">
                        <h5>Deductions</h5>
                        <span class="badge bg-danger-subtle text-danger">Payroll deductions</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label>Late Deduction (Per Minute)</label>
                            <input type="text" class="form-control payroll-field payroll-readonly" value="₱<?php echo e(number_format($lateDeductionValue, 2)); ?>" readonly>
                            <input type="hidden" name="late_deduction" value="<?php echo e(number_format($lateDeductionValue, 2, '.', '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Early Out Deduction</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="early_out_deduction" class="form-control deduction-field payroll-field" value="<?php echo e(number_format($earlyOutDeductionValue, 2, '.', '')); ?>" min="0" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Absent Rate Deduction</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" id="absent_deduction_input" name="absent_deduction" class="form-control deduction-field payroll-field payroll-readonly" value="<?php echo e(number_format($absentDeductionValue, 2, '.', '')); ?>" min="0" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Half-Day Deduction (<?php echo e($halfDayDaysValue); ?> half-day<?php echo e($halfDayDaysValue === 1 ? '' : 's'); ?>)</label>
                            <input type="number" step="0.01" name="half_day_deduction" id="half_day_deduction_input" class="form-control deduction-field payroll-field payroll-readonly" value="<?php echo e(number_format($halfDayDeductionValue, 2, '.', '')); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Suspension Deduction</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="suspension_deduction" class="form-control deduction-field payroll-field status-controlled-field payroll-readonly" value="<?php echo e(number_format($suspensionDeductionValue, 2, '.', '')); ?>" min="0" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Holiday Deduction</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="holiday_deduction" class="form-control deduction-field payroll-field status-controlled-field payroll-readonly" value="<?php echo e(number_format($holidayDeductionValue, 2, '.', '')); ?>" min="0" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Leave Without Pay</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" id="leave_without_pay_input" name="leave_without_pay_amount" class="form-control deduction-field payroll-field status-controlled-field payroll-readonly" value="<?php echo e(number_format($leaveWithoutPayValue, 2, '.', '')); ?>" min="0" readonly>
                            </div>
                            <input type="hidden" name="leave_without_pay" value="<?php echo e(old('leave_without_pay', $leaveWithoutPayTotal)); ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Cash Advance Deduction</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="text" name="cash_advance_deduction" class="form-control deduction-field payroll-field" value="<?php echo e($cashAdvanceValue); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Cash Charges</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="text" name="cash_charge_deduction" class="form-control deduction-field payroll-field" value="<?php echo e(number_format($cashChargeValue, 2, '.', '')); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payroll-section">
                    <div class="payroll-section-header">
                        <h5>Contributions</h5>
                        <span class="badge bg-primary-subtle text-primary">Statutory</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label>SSS Contribution</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="sss_contribution" class="form-control deduction-field payroll-field status-controlled-field" value="<?php echo e(old('sss_contribution', $entry->sss_contribution)); ?>" min="0" <?php echo e($isDailyRateLocked ? 'readonly' : ''); ?>>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>PhilHealth Contribution</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="philhealth_contribution" class="form-control deduction-field payroll-field status-controlled-field" value="<?php echo e(old('philhealth_contribution', $entry->philhealth_contribution)); ?>" min="0" <?php echo e($isDailyRateLocked ? 'readonly' : ''); ?>>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Pag-IBIG Contribution</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="pagibig_contribution" class="form-control deduction-field payroll-field status-controlled-field" value="<?php echo e(old('pagibig_contribution', $entry->pagibig_contribution)); ?>" min="0" <?php echo e($isDailyRateLocked ? 'readonly' : ''); ?>>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Withholding Tax</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" name="withholding_tax" class="form-control deduction-field payroll-field status-controlled-field" value="<?php echo e(old('withholding_tax', $entry->withholding_tax)); ?>" min="0" <?php echo e($isDailyRateLocked ? 'readonly' : ''); ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payroll-section">
                    <div class="payroll-section-header">
                        <h5>Totals</h5>
                        <span class="badge bg-success-subtle text-success">Payroll summary</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label>Gross Pay</label>
                            <div class="payroll-currency-group input-group">
                                <span class="input-group-text">₱</span>
                                <input type="text" id="gross_pay_total" class="form-control payroll-field payroll-readonly" value="<?php echo e(number_format((float) ($entry->gross_pay ?? 0), 2, '.', '')); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Total Deductions</label>
                            <input type="text" id="total_deductions" class="form-control payroll-field payroll-readonly" value="₱<?php echo e(number_format($entry->total_deductions, 2)); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Status</label>
                            <select name="status" id="status_input" class="form-control payroll-field">
                                <option value="calculated" <?php echo e(old('status', $entry->status) === 'calculated' ? 'selected' : ''); ?>>Calculated</option>
                                <option value="approved" <?php echo e(old('status', $entry->status) === 'approved' ? 'selected' : ''); ?>>Approved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Computed Net Pay</label>
                            <input type="text" id="computed_net_pay" class="form-control payroll-field payroll-readonly" value="₱<?php echo e(number_format($entry->gross_pay - $entry->total_deductions + $holidayBonusValue + $suspensionPayValue, 2)); ?>" readonly>
                        </div>
                    </div>
                </div>

                </fieldset>
                <?php if(!$isReadOnly): ?>
                    <div class="payroll-actions text-end">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dailyRateInput = document.getElementById('daily_rate_input');
        const basicPayInput = document.querySelector('input[name="basic_pay"]');
        const leaveWithoutPayInput = document.getElementById('leave_without_pay_input');
        const absentDeductionInput = document.getElementById('absent_deduction_input');
        const holidayDeductionInput = document.querySelector('input[name="holiday_deduction"]');
        const suspensionDeductionInput = document.querySelector('input[name="suspension_deduction"]');
        const halfDayDeductionInput = document.getElementById('half_day_deduction_input');
        const suspendedHoursInput = document.getElementById('suspended_hours_to_pay_input');
        const suspensionPayDisplay = document.getElementById('suspension_pay_display');
        const totalDailyRateDisplay = document.getElementById('total_daily_rate_display');
        const paidLeaveRateDisplay = document.getElementById('paid_leave_rate_display');
        const grossPayDisplay = document.getElementById('gross_pay_total');
        const overtimePayDisplay = document.getElementById('overtime_pay_display');
        const totalDeductionsDisplay = document.getElementById('total_deductions');
        const computedNetPayDisplay = document.getElementById('computed_net_pay');
        const holidayBonusInput = document.getElementById('holiday_bonus_input');
        const statusInput = document.getElementById('status_input');

        if (!dailyRateInput || !totalDailyRateDisplay || !paidLeaveRateDisplay) {
            return;
        }

        const daysPresent = Number('<?php echo e($daysPresentValue); ?>') || 0;
        const paidLeave = Number('<?php echo e($paidLeaveTotal); ?>') || 0;
        const absentDays = Number('<?php echo e($daysAbsentValue); ?>') || 0;
        const halfDayDays = Number('<?php echo e($halfDayDaysValue); ?>') || 0;
        const leaveWithoutPayDays = Number('<?php echo e($leaveWithoutPayTotal); ?>') || 0;
        const missedHolidayDays = Number('<?php echo e($dtrStats['missed_holiday_days'] ?? 0); ?>') || 0;
        const deductibleSuspensionDays = Number('<?php echo e($dtrStats['deductible_suspension_days'] ?? 0); ?>') || 0;
        const overtimePay = Number('<?php echo e($entry->overtime_pay ?? 0); ?>') || 0;
        const suspensionHourlyRate = Number('<?php echo e(\App\Services\PayrollComputationService::SUSPENSION_HOURLY_RATE); ?>') || 0;
        const isFinanceHead = <?php echo json_encode($isFinanceHead, 15, 512) ?>;
        const canCorrectReturnedEntry = <?php echo json_encode($canCorrectReturnedEntry ?? false, 15, 512) ?>;

        const formatCurrency = (value) => '₱' + Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const syncDeductionSummary = () => {
            const rate = Number(dailyRateInput.value) || 0;
            const leaveWithoutPayAmount = leaveWithoutPayDays * rate;
            const deductionFields = document.querySelectorAll([
                'input[name="sss_contribution"]',
                'input[name="philhealth_contribution"]',
                'input[name="pagibig_contribution"]',
                'input[name="withholding_tax"]',
                'input[name="cash_advance_deduction"]',
                'input[name="cash_charge_deduction"]',
                'input[name="late_deduction"]',
                'input[name="absent_deduction"]',
                'input[name="half_day_deduction"]',
                'input[name="suspension_deduction"]',
                'input[name="holiday_deduction"]',
                'input[name="early_out_deduction"]',
                'input[name="leave_without_pay_amount"]'
            ]);

            let totalDeductions = 0;
            deductionFields.forEach((field) => {
                const value = Number(field.value || 0);
                if (!Number.isNaN(value)) {
                    totalDeductions += value;
                }
            });

            if (totalDeductionsDisplay) {
                totalDeductionsDisplay.value = formatCurrency(totalDeductions);
            }

            if (computedNetPayDisplay) {
                const rate = Number(dailyRateInput.value) || 0;
                const suspendedHours = Number(suspendedHoursInput?.value) || 0;
                const suspensionPay = suspendedHours * suspensionHourlyRate;
                const grossPay = (Number(basicPayInput?.value) || 0) + overtimePay;
                const holidayBonus = Number(holidayBonusInput?.value || 0);
                if (overtimePayDisplay) {
                    overtimePayDisplay.value = (overtimePay + holidayBonus + suspensionPay).toFixed(2);
                }
                const computedNetPay = grossPay - totalDeductions + holidayBonus + suspensionPay;
                computedNetPayDisplay.value = formatCurrency(computedNetPay);
                if (grossPayDisplay) {
                    grossPayDisplay.value = formatCurrency(grossPay);
                }
            }
        };

        const updateDerivedValues = () => {
            const rate = Number(dailyRateInput.value) || 0;
            const totalDailyRate = daysPresent * rate;
            const paidLeaveRate = paidLeave * rate;
            const leaveWithoutPayAmount = leaveWithoutPayDays * rate;
            const absentDeduction = absentDays * rate;
            const halfDayDeduction = halfDayDays * rate * 0.5;
            const holidayDeduction = missedHolidayDays * rate;
            const suspensionDeduction = deductibleSuspensionDays * rate;
            const suspendedHours = Number(suspendedHoursInput?.value) || 0;
            const suspensionPay = suspendedHours * suspensionHourlyRate;

            totalDailyRateDisplay.value = formatCurrency(totalDailyRate);
            paidLeaveRateDisplay.value = formatCurrency(paidLeaveRate);

            if (leaveWithoutPayInput) {
                leaveWithoutPayInput.value = leaveWithoutPayAmount.toFixed(2);
            }

            if (absentDeductionInput) {
                absentDeductionInput.value = absentDeduction.toFixed(2);
            }
            if (halfDayDeductionInput) {
                halfDayDeductionInput.value = halfDayDeduction.toFixed(2);
            }
            if (holidayDeductionInput) {
                holidayDeductionInput.value = holidayDeduction.toFixed(2);
            }
            if (suspensionDeductionInput) {
                suspensionDeductionInput.value = suspensionDeduction.toFixed(2);
            }
            if (suspensionPayDisplay) {
                suspensionPayDisplay.value = formatCurrency(suspensionPay);
            }

            syncDeductionSummary();
        };

        const syncDailyRateLock = () => {
            const isApproved = statusInput && statusInput.value === 'approved' && !canCorrectReturnedEntry;
            document.querySelectorAll('.status-controlled-field').forEach((field) => {
                field.readOnly = field.classList.contains('payroll-readonly') || isApproved;
            });
        };

        const deductionInputs = document.querySelectorAll([
            'input[name="sss_contribution"]',
            'input[name="philhealth_contribution"]',
            'input[name="pagibig_contribution"]',
            'input[name="withholding_tax"]',
            'input[name="cash_advance_deduction"]',
            'input[name="cash_charge_deduction"]',
            'input[name="late_deduction"]',
            'input[name="absent_deduction"]',
            'input[name="suspension_deduction"]',
            'input[name="holiday_deduction"]',
            'input[name="early_out_deduction"]'
        ]);

        deductionInputs.forEach((input) => {
            input.addEventListener('input', syncDeductionSummary);
            input.addEventListener('change', syncDeductionSummary);
        });

        if (holidayBonusInput) {
            holidayBonusInput.addEventListener('input', syncDeductionSummary);
            holidayBonusInput.addEventListener('change', syncDeductionSummary);
        }

        if (suspendedHoursInput) {
            suspendedHoursInput.addEventListener('input', syncDeductionSummary);
            suspendedHoursInput.addEventListener('input', updateDerivedValues);
        }

        if (basicPayInput) {
            basicPayInput.addEventListener('input', syncDeductionSummary);
            basicPayInput.addEventListener('change', syncDeductionSummary);
        }

        if (leaveWithoutPayInput) {
            leaveWithoutPayInput.addEventListener('input', syncDeductionSummary);
            leaveWithoutPayInput.addEventListener('change', syncDeductionSummary);
        }

        dailyRateInput.addEventListener('input', updateDerivedValues);
        if (statusInput) {
            statusInput.addEventListener('change', syncDailyRateLock);
        }
        syncDailyRateLock();
        updateDerivedValues();
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\payroll\edit-entry.blade.php ENDPATH**/ ?>