@extends('layouts.app')

@section('title', 'Payslip - ' . $entry->employeeProfile->first_name . ' ' . $entry->employeeProfile->last_name)

@section('content')
<style>
    .payslip-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 16px 0;
    }

    .payslip-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .payslip-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 18px 24px;
        border-bottom: 3px solid #ffc107;
    }

    .payslip-header h2 {
        font-weight: 700;
        font-size: 24px;
        margin: 0;
    }

    .payslip-header .period-info {
        font-size: 12px;
        opacity: 0.9;
        margin-top: 4px;
    }

    .payslip-body {
        padding: 22px 26px;
    }

    .employee-section {
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .section-title {
        font-weight: 700;
        font-size: 14px;
        color: #333;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        font-size: 12px;
    }

    .info-label {
        color: #666;
        font-weight: 600;
    }

    .info-value {
        color: #333;
        font-weight: 500;
    }

    .earnings-deductions {
        margin: 18px 0;
    }

    .earnings-col, .deductions-col {
        padding: 0 10px;
    }

    .earnings-col {
        border-right: 1px solid #e9ecef;
    }

    .earnings-header {
        color: #28a745;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .deductions-header {
        color: #dc3545;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .item-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 12px;
        border-bottom: 1px solid #f1f3f5;
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-label {
        color: #666;
    }

    .item-value {
        font-weight: 600;
        color: #333;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-weight: 700;
        font-size: 13px;
        border-top: 2px solid #333;
        border-bottom: 2px solid #333;
        margin-top: 10px;
        color: #333;
    }

    .net-pay-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 14px;
        border-radius: 7px;
        margin: 18px 0;
        text-align: center;
    }

    .net-pay-section .label {
        font-size: 14px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .net-pay-section .amount {
        font-size: 32px;
        font-weight: 700;
        margin-top: 4px;
    }

    .attendance-summary {
        margin: 18px 0;
    }

    .stat-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        border-left: 4px solid #667eea;
    }

    .stat-box .number {
        font-size: 24px;
        font-weight: 700;
        color: #667eea;
    }

    .stat-box .label {
        font-size: 12px;
        color: #666;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .payslip-footer {
        background: #f8f9fa;
        padding: 12px 26px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .footer-info {
        font-size: 11px;
        color: #666;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-custom {
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 11px;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-back {
        background-color: #6c757d;
        color: white;
    }

    .btn-back:hover {
        background-color: #5a6268;
        text-decoration: none;
    }

    .btn-print {
        background-color: #667eea;
        color: white;
    }

    .btn-print:hover {
        background-color: #5568d3;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-released {
        background-color: #d4edda;
        color: #155724;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
    }

    body.dark-mode .payslip-container {
        background: linear-gradient(135deg, #111827 0%, #312e81 100%);
    }

    body.dark-mode .payslip-card,
    body.dark-mode .payslip-body,
    body.dark-mode .payslip-footer {
        background: #1e293b;
        color: #f8fafc;
    }

    body.dark-mode .payslip-card .employee-section,
    body.dark-mode .payslip-card .earnings-col {
        border-color: #475569;
    }

    body.dark-mode .payslip-card .section-title,
    body.dark-mode .payslip-card .info-value,
    body.dark-mode .payslip-card .item-value,
    body.dark-mode .payslip-card .total-row,
    body.dark-mode .payslip-card .footer-info {
        color: #f8fafc !important;
    }

    body.dark-mode .payslip-card .info-label,
    body.dark-mode .payslip-card .item-label,
    body.dark-mode .payslip-card .footer-info {
        color: #cbd5e1 !important;
    }

    body.dark-mode .payslip-card .item-row {
        border-bottom-color: #334155;
    }

    body.dark-mode .payslip-card .attendance-summary-card,
    body.dark-mode .payslip-card .stat-box {
        background: #0f172a;
        border-color: #818cf8;
        color: #f8fafc;
    }

    body.dark-mode .payslip-card .attendance-summary-card .label,
    body.dark-mode .payslip-card .stat-box .label {
        color: #cbd5e1;
    }

    body.dark-mode .payslip-card .attendance-summary-card .value,
    body.dark-mode .payslip-card .stat-box .number {
        color: #a5b4fc;
    }

    body.dark-mode .payslip-card .payslip-footer {
        border-top-color: #475569;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        html,
        body {
            width: 100%;
            min-height: 0;
            background: #fff !important;
            color: #111 !important;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sidebar,
        .top-navbar,
        .menu-toggle,
        .mobile-menu-toggle,
        .main-content > footer,
        .payslip-footer,
        .btn-custom {
            display: none !important;
        }

        .main-content,
        .main-content main {
            width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .payslip-container {
            min-height: 0;
            background: #fff !important;
            padding: 0 !important;
            width: 100% !important;
            zoom: 1;
        }

        .payslip-container > .container,
        .payslip-container > .container > .row,
        .payslip-container .col-lg-9 {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .payslip-card {
            width: 100%;
            box-shadow: none;
            border-radius: 0;
            page-break-inside: auto;
        }

        .payslip-header {
            padding: 10px 14px;
        }

        .payslip-body {
            padding: 10px 14px;
        }

        .payslip-header h2 { font-size: 20px; }
        .payslip-header .period-info { font-size: 10px; margin-top: 2px; }
        .payslip-header strong { font-size: 12px !important; }
        .attendance-summary-grid { gap: 4px !important; margin-bottom: 8px !important; }
        .attendance-summary-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .attendance-summary-card {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
            padding: 5px !important;
            min-height: 0 !important;
        }
        .attendance-summary-card .label,
        .attendance-summary-card .value {
            display: inline !important;
            white-space: nowrap;
        }
        .attendance-summary-card .label {
            font-size: 9px !important;
            color: #444 !important;
        }
        .attendance-summary-card .value {
            font-size: 11px !important;
            color: #111 !important;
        }
        .employee-section { margin-bottom: 8px; padding-bottom: 6px; }
        .section-title { font-size: 12px; margin-bottom: 3px; }
        .info-row { padding: 2px 0; font-size: 10px; }
        .earnings-deductions { margin: 8px 0; }
        .earnings-header, .deductions-header { font-size: 11px; margin-bottom: 3px; }
        .item-row { padding: 3px 0; font-size: 10px; }
        .total-row { padding: 5px 0; font-size: 11px; margin-top: 4px; }
        .net-pay-section { padding: 8px; margin: 8px 0; }
        .net-pay-section .label { font-size: 10px; }
        .net-pay-section .amount { font-size: 28px; margin-top: 2px; }
        .status-badge { padding: 3px 6px; font-size: 10px; }
        .attendance-summary { display: none !important; }

        .payslip-card,
        .payslip-card .payslip-body,
        .payslip-card .employee-section,
        .payslip-card .item-row,
        .payslip-card .total-row,
        .payslip-card .stat-box {
            color: #111 !important;
        }

        .payslip-card * {
            opacity: 1 !important;
        }

        .payslip-card h2,
        .payslip-card .period-info,
        .payslip-card .section-title,
        .payslip-card .earnings-header,
        .payslip-card .deductions-header,
        .payslip-card .info-label,
        .payslip-card .info-value,
        .payslip-card .item-label,
        .payslip-card .item-value,
        .payslip-card .total-row,
        .payslip-card .attendance-summary-card,
        .payslip-card .attendance-summary-card .label,
        .payslip-card .attendance-summary-card .value {
            color: #111 !important;
            text-shadow: none !important;
        }

        .payslip-card .info-label,
        .payslip-card .item-label,
        .payslip-card .footer-info {
            color: #444 !important;
        }

        .payslip-card .attendance-summary-card,
        .payslip-card .stat-box {
            background: #f8f9fa !important;
            color: #111 !important;
        }

        .earnings-deductions,
        .employee-section,
        .net-pay-section { page-break-inside: auto; }
    }

    @media (max-width: 768px) {
        .earnings-col {
            border-right: none;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .payslip-body {
            padding: 20px;
        }
    }
</style>

<div class="payslip-container">
    @php
        $breakdown = is_string($entry->payroll_breakdown)
            ? (json_decode($entry->payroll_breakdown, true) ?? [])
            : (array) ($entry->payroll_breakdown ?? []);
        $dtrStats = $dtrStats ?? [
            'days_present' => (int) ($breakdown['days_present'] ?? $entry->days_present ?? 0),
            'days_absent' => (int) ($breakdown['days_absent'] ?? $entry->days_absent ?? 0),
            'late_minutes' => (int) ($breakdown['late_minutes'] ?? ($entry->dtr?->getTotalLateMinutes() ?? 0)),
            'early_out_minutes' => (int) ($breakdown['early_out_minutes'] ?? ($entry->dtr?->getTotalEarlyOutMinutes() ?? 0)),
            'paid_leave' => (int) ($breakdown['paid_leave'] ?? 0),
            'leave_without_pay' => (int) ($breakdown['leave_without_pay'] ?? 0),
        ];
        $dailyRate = (float) ($breakdown['daily_rate'] ?? 0);
        $totalDailyRate = (float) ($breakdown['total_daily_rate'] ?? (($entry->days_present ?? 0) * $dailyRate));
        $earlyOutDeduction = (float) ($breakdown['early_out_deduction'] ?? 0);
        $leaveWithoutPayDays = (int) ($dtrStats['leave_without_pay'] ?? 0);
        $paidLeaveDays = (int) ($dtrStats['paid_leave'] ?? 0);
        $leaveWithoutPayDeduction = $leaveWithoutPayDays * $dailyRate;
        $cashAdvanceDeduction = (float) ($entry->cash_advance_deduction ?? ($breakdown['cash_advance_deduction'] ?? 0));
        $isApprovedPayroll = in_array($entry->payrollPeriod?->status, ['approved', 'completed'], true);
    @endphp
    <div class="container">
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <div class="payslip-card">
                    <!-- Header -->
                    <div class="payslip-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2>PAYSLIP</h2>
                                <div class="period-info">
                                    {{ $entry->payrollPeriod?->name ?? 'Payroll Period' }}
                                </div>
                            </div>
                            <div class="col-auto text-right">
                                <div class="period-info mb-2">Period</div>
                                <strong style="font-size: 16px;">
                                    {{ $entry->payrollPeriod?->start_date?->format('M d') ?? '--' }} - 
                                    {{ $entry->payrollPeriod?->end_date?->format('M d, Y') ?? '--' }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="payslip-body">
                        <div class="attendance-summary-grid">
                            <div class="attendance-summary-card">
                                <div class="label">Total Day Present</div>
                                <div class="value">{{ $dtrStats['days_present'] ?? 0 }}</div>
                            </div>
                            <div class="attendance-summary-card">
                                <div class="label">Total Day Absent</div>
                                <div class="value">{{ $dtrStats['days_absent'] ?? 0 }}</div>
                            </div>
                            <div class="attendance-summary-card">
                                <div class="label">Late Minutes</div>
                                <div class="value">{{ $dtrStats['late_minutes'] ?? 0 }}</div>
                            </div>
                            <div class="attendance-summary-card">
                                <div class="label">Early Out Minutes</div>
                                <div class="value">{{ $dtrStats['early_out_minutes'] ?? 0 }}</div>
                            </div>
                            <div class="attendance-summary-card">
                                <div class="label">Paid Leave</div>
                                <div class="value">{{ $dtrStats['paid_leave'] ?? 0 }}</div>
                            </div>
                            <div class="attendance-summary-card">
                                <div class="label">Leave Without Pay</div>
                                <div class="value">{{ $dtrStats['leave_without_pay'] ?? 0 }}</div>
                            </div>
                        </div>

                        <!-- Employee & Company Information -->
                        <div class="employee-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="section-title" style="color: #667eea;">Employee</div>
                                    <div class="info-row">
                                        <span class="info-label">Name:</span>
                                        <span class="info-value">{{ $entry->employeeProfile->first_name }} {{ $entry->employeeProfile->last_name }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Employee ID:</span>
                                        <span class="info-value">{{ $entry->employeeProfile->employee_number }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Position:</span>
                                        <span class="info-value">{{ $entry->employeeProfile->position ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="section-title" style="color: #667eea;">Company</div>
                                    <div class="info-row">
                                        <span class="info-label">Company:</span>
                                        <span class="info-value">MTCGS EMS</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Branch:</span>
                                        <span class="info-value">{{ $entry->branch?->branch_name ?? $entry->employeeProfile?->branch?->branch_name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value">
                                            <span class="status-badge status-{{ $entry->status }}">
                                                {{ $isApprovedPayroll ? ($entry->payrollPeriod?->admin_approval_stage === 'hr_fd' ? 'Approved HR/FD' : 'Finance Head Approved') : ucfirst($entry->payrollPeriod?->status ?? $entry->status) }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Earnings & Deductions -->
                        <div class="earnings-deductions">
                            <div class="row">
                                <div class="col-md-6 earnings-col">
                                    <div class="earnings-header">Earnings</div>
                                    <div class="item-row">
                                        <span class="item-label">Basic Pay</span>
                                        <span class="item-value">₱{{ number_format($entry->basic_pay, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Daily Rate</span>
                                        <span class="item-value">₱{{ number_format($dailyRate, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Total Daily Rate</span>
                                        <span class="item-value">₱{{ number_format($totalDailyRate, 2) }}</span>
                                    </div>
                                    @if($entry->overtime_pay > 0)
                                    <div class="item-row">
                                        <span class="item-label">Overtime ({{ number_format($entry->overtime_hours, 2) }} hrs)</span>
                                        <span class="item-value">₱{{ number_format($entry->overtime_pay, 2) }}</span>
                                    </div>
                                    @endif
                                    @if($entry->allowances > 0)
                                    <div class="item-row">
                                        <span class="item-label">Allowances</span>
                                        <span class="item-value">₱{{ number_format($entry->allowances, 2) }}</span>
                                    </div>
                                    @endif
                                    @if($entry->bonuses > 0)
                                    <div class="item-row">
                                        <span class="item-label">Bonuses</span>
                                        <span class="item-value">₱{{ number_format($entry->bonuses, 2) }}</span>
                                    </div>
                                    @endif
                                    <div class="total-row" style="color: #28a745; border-top-color: #28a745; border-bottom-color: #28a745;">
                                        <span>Gross Pay</span>
                                        <span>₱{{ number_format($entry->gross_pay, 2) }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6 deductions-col">
                                    <div class="deductions-header">Deductions</div>
                                    <div class="item-row">
                                        <span class="item-label">Late Deduction</span>
                                        <span class="item-value">₱{{ number_format($entry->late_deduction, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Absent Deduction</span>
                                        <span class="item-value">₱{{ number_format($entry->absent_deduction, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Early Out Deduction</span>
                                        <span class="item-value">₱{{ number_format($earlyOutDeduction, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Leave Deduction</span>
                                        <span class="item-value">₱{{ number_format($entry->leave_deduction ?? 0, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Leave Without Pay ({{ $leaveWithoutPayDays }} days)</span>
                                        <span class="item-value">₱{{ number_format($leaveWithoutPayDeduction, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">SSS</span>
                                        <span class="item-value">₱{{ number_format($entry->sss_contribution ?? 0, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">PhilHealth</span>
                                        <span class="item-value">₱{{ number_format($entry->philhealth_contribution ?? 0, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Pag-IBIG</span>
                                        <span class="item-value">₱{{ number_format($entry->pagibig_contribution ?? 0, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Withholding Tax</span>
                                        <span class="item-value">₱{{ number_format($entry->withholding_tax ?? 0, 2) }}</span>
                                    </div>
                                    <div class="item-row">
                                        <span class="item-label">Cash Advance Deduction</span>
                                        <span class="item-value">₱{{ number_format($cashAdvanceDeduction, 2) }}</span>
                                    </div>
                                    <div class="total-row" style="color: #dc3545; border-top-color: #dc3545; border-bottom-color: #dc3545;">
                                        <span>Total Deductions</span>
                                        <span>₱{{ number_format($entry->total_deductions, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Pay -->
                        <div class="net-pay-section">
                            <div class="label">Net Pay</div>
                            <div class="amount">₱{{ number_format($entry->net_pay, 2) }}</div>
                        </div>

                        <!-- Attendance Summary -->
                        @if($entry->dtr)
                        <div class="attendance-summary">
                            <div class="section-title">Attendance Summary</div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ $entry->days_present ?? 0 }}</div>
                                        <div class="label">Days Present</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ $entry->dtr->early_out_minutes ?? 0 }}</div>
                                        <div class="label">Early Out Minutes</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ $paidLeaveDays }}</div>
                                        <div class="label">Paid Leave</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ $leaveWithoutPayDays }}</div>
                                        <div class="label">Leave Without Pay</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ $entry->days_absent ?? 0 }}</div>
                                        <div class="label">Days Absent</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ number_format($entry->overtime_hours ?? 0, 1) }}</div>
                                        <div class="label">OT Hours</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-box">
                                        <div class="number">{{ $entry->dtr->late_minutes ?? 0 }}</div>
                                        <div class="label">Late Minutes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="payslip-footer">
                        <div class="footer-info">
                            Generated: <strong>{{ now()->format('M d, Y @ H:i A') }}</strong>
                        </div>
                        <div class="action-buttons">
                            @if(Auth::user()->role === 'admin' || Auth::user()->role === 'finance')
                            <a href="{{ route('admin.payroll.entries', ['periodId' => $entry->payroll_period_id]) }}" class="btn-custom btn-back">← Back to Payroll</a>
                            @else
                            <a href="{{ route('employee.payslips') }}" class="btn-custom btn-back">← Back to Payslips</a>
                            @endif
                            <button onclick="window.print()" class="btn-custom btn-print">🖨 Print Payslip</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
