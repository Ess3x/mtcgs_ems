@extends('layouts.app')

@section('title', 'View DTR')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('employee.dtr.index') }}" class="btn btn-outline-secondary btn-sm mb-3">
                        <i class="fas fa-arrow-left"></i> Back to DTR List
                    </a>
                    <h2 class="mb-2">DTR Details</h2>
                    <p class="text-muted">{{ $employeeProfile->first_name }} {{ $employeeProfile->last_name }} ({{ $employeeProfile->employee_number }})</p>
                </div>
                <div>
                    <div class="d-flex gap-2 mb-2 justify-content-end">
                        <a href="{{ route('employee.dtr.download', $dtr->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                        </a>
                        <a href="{{ route('employee.dtr.download-excel', $dtr->id) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i> Download Excel
                        </a>
                        @if ($previousDTR)
                            <a href="{{ route('employee.dtr.show', $previousDTR->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i> Previous DTR
                            </a>
                        @else
                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                <i class="fas fa-chevron-left"></i> Previous DTR
                            </button>
                        @endif
                        @if ($nextDTR)
                            <a href="{{ route('employee.dtr.show', $nextDTR->id) }}" class="btn btn-outline-primary btn-sm">
                                Next DTR <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                                Next DTR <i class="fas fa-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Info and Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Period</p>
                    <h6 class="mb-3">{{ $dtr->period_start->format('M d') }} - {{ $dtr->period_end->format('M d, Y') }}</h6>
                    <span class="badge bg-{{ $dtr->status === 'draft' ? 'warning' : ($dtr->status === 'submitted' ? 'info' : ($dtr->status === 'approved' ? 'success' : 'danger')) }} p-2">
                        {{ ucfirst($dtr->status) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Hours</p>
                    <h4 class="mb-0">{{ number_format($stats['total_hours'], 1) }} hrs</h4>
                    <small class="text-muted">of {{ $stats['working_days'] * 8 }} hrs expected</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Days Present</p>
                    <h4 class="mb-0">{{ $stats['days_present'] }}/{{ $stats['working_days'] }}</h4>
                    <small class="text-muted">{{ $stats['days_absent'] }} day(s) absent</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Late</p>
                    @if ($stats['total_late_minutes'] > 0)
                        <span class="badge bg-danger p-2">Late: {{ $stats['total_late_minutes'] }} min</span>
                    @else
                        <small class="text-muted">--</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Absent</p>
                    <h4 class="mb-0 text-danger">{{ $stats['days_absent'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Paid Leave</p>
                    <h4 class="mb-0 text-info">{{ $stats['total_paid_leave'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Total Leave Without Pay</p>
                    <h4 class="mb-0 text-warning">{{ $stats['total_leave_without_pay'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Early Out</p>
                    @if (($stats['total_early_out_minutes'] ?? 0) > 0)
                        <span class="badge bg-warning text-dark p-2">Early Out: {{ $stats['total_early_out_minutes'] }} min</span>
                    @else
                        <small class="text-muted">--</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @if ($dtr->status === 'draft')
        <div class="row mb-4">
            <div class="col-12">
                <form action="{{ route('employee.dtr.submit', $dtr->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to submit this DTR for approval?')">
                        <i class="fas fa-check"></i> Submit for Approval
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-signature me-2"></i>E-Signature for this DTR</h5>
            @if ($employeeProfile->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($employeeProfile->signature_path) || \App\Models\FinanceProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists() || \App\Models\AdminProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists())
                <span class="badge bg-success">Saved</span>
            @else
                <span class="badge bg-warning text-dark">Not saved</span>
            @endif
        </div>
        <div class="card-body text-center">
            @if ($employeeProfile->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($employeeProfile->signature_path) || \App\Models\FinanceProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists() || \App\Models\AdminProfile::where('employee_profile_id', $employeeProfile->id)->whereNotNull('signature_path')->exists())
                <div class="border rounded bg-white d-inline-block px-4 py-2">
                    <img src="{{ route('profile.signature', $employeeProfile->id) }}" alt="Employee e-signature" style="width: 280px; max-width: 100%; height: 90px; object-fit: contain;">
                </div>
                <p class="text-muted small mb-0 mt-2">This signature is included in the DTR PDF.</p>
            @else
                <p class="text-muted mb-2">No e-signature saved for this employee.</p>
                <a href="{{ route('profile') }}" class="btn btn-outline-primary btn-sm">Save E-Signature in Profile</a>
            @endif
        </div>
    </div>

    <!-- Daily Records Table -->
    <style>
        .dtr-table {
            width: 760px;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 0 auto;
            font-size: .95rem;
        }
        .dtr-table-scroll {
            max-height: 520px;
            overflow: auto;
        }
        .dtr-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .dtr-table th,
        .dtr-table td {
            border: 1px solid #222;
            min-height: 42px;
            padding: 10px 12px;
            vertical-align: middle;
        }
        .dtr-table th {
            background-color: #fff;
            color: #111;
            font-weight: 700;
            text-transform: uppercase;
        }
        .dtr-table td:first-child {
            font-weight: 600;
        }
        .dtr-table th,
        .dtr-table td,
        .dtr-table td small {
            font-weight: 700;
        }
        .dtr-table tbody td.status-empty {
            background-color: #fff59d !important;
            color: #111 !important;
        }
        .dtr-table tbody td.status-leave,
        .dtr-table.table-hover tbody tr:hover td.status-leave {
            background-color: #92EEFF !important;
            color: #111 !important;
        }
        .dtr-table tbody td.status-absent,
        .dtr-table.table-hover tbody tr:hover td.status-absent {
            background-color: #ff0000 !important;
            color: #111 !important;
        }
        .dtr-table tbody td.status-lwop,
        .dtr-table.table-hover tbody tr:hover td.status-lwop {
            background-color: #ff0000 !important;
            color: #111 !important;
        }
        body.dark-mode .dtr-table tbody td.status-empty,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.status-empty {
            background-color: #fff59d !important;
            color: #111 !important;
        }
        body.dark-mode .dtr-table tbody td.status-leave,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.status-leave {
            background-color: #92EEFF !important;
            color: #111 !important;
        }
        body.dark-mode .dtr-table tbody td.status-absent,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.status-absent {
            background-color: #ff0000 !important;
            color: #111 !important;
        }
        .dtr-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 0.8rem;
            font-weight: 800;
            line-height: 1;
            color: #111;
            border: 2px solid rgba(17, 17, 17, 0.2);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.25);
        }
        .dtr-status-present {
            background-color: #a3e635;
        }
        .dtr-status-late {
            background-color: #facc15;
            color: #111;
        }
        .dtr-status-absent {
            background-color: #ff0000;
            color: #fff;
        }
        .dtr-status-leave {
            background-color: #67e8f9;
        }
        .dtr-status-lwop {
            background-color: #ff0000;
            color: #B4E1EB;
        }
        .dtr-status-weekend {
            background-color: #d1d5db;
            color: #374151;
        }
        .dtr-status-holiday {
            background-color: #fef3c7;
            color: #92400e;
        }
        .dtr-status-half-day {
            background-color: #fed7aa;
            color: #9a3412;
        }
        .dtr-table .dtr-late {
            color: #ff0000;
        }
        body.dark-mode .dtr-table .dtr-late,
        body.dark-mode .dtr-table.table-hover tbody tr:hover td.dtr-late {
            color: #ff0000 !important;
        }
        @media (max-width: 576px) {
            .dtr-table {
                min-width: 620px;
            }
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Time Records</h5>
                </div>
                <div class="card-body">
                    @if ($daysInPeriod && count($daysInPeriod) > 0)
                        <div class="table-responsive dtr-table-scroll">
                            <table class="table dtr-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Date</th>
                                        <th class="text-center" style="width: 25%;">Time-In</th>
                                        <th class="text-center" style="width: 25%;">Time-Out</th>
                                        <th class="text-center" style="width: 25%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $leaveStatuses = ['leave', 'leave_paid', 'on leave'];
                                    @endphp
                                    @foreach ($daysInPeriod as $day)
                                        @php
                                            $isDraft = $dtr->status === 'draft';
                                            $logStatus = $day['log'] ? strtolower((string) $day['log']->status) : null;
                                            $isLWOP = $day['status'] === 'lwop';
                                            $isLeave = $day['status'] === 'leave';
                                            $isWeekend = $day['is_weekend'];
                                            $isHoliday = ($day['status'] ?? null) === 'holiday' || ($day['is_holiday'] ?? false);
                                            $attendanceStatus = $day['log'] ? $day['log']->getDtrStatus() : null;
                                            $isNoRecordYet = !$isWeekend && !$isHoliday && !$day['log'] && $dtr->status !== 'approved';
                                            $isPastMissedDay = !$isWeekend && !$isHoliday && !$day['log'] && $dtr->status === 'approved';
                                            $isAbsent = !$isWeekend && ($day['status'] === 'absent' || $isPastMissedDay);
                                            $isLate = $attendanceStatus && str_contains($attendanceStatus, 'Late');
                                            $isEarlyOutStatus = $attendanceStatus && str_contains($attendanceStatus, 'Early Out');
                                            $isHalfDay = $attendanceStatus === 'Half Day';
                                            $isLateTimeIn = $isLate || (int) ($day['late_minutes'] ?? 0) > 0;
                                            $rowClass = $isLWOP ? 'dtr-absent' : ($isLeave ? 'dtr-leave' : ($isHoliday && !$day['log'] ? 'dtr-status-holiday' : ($isAbsent ? 'dtr-absent' : ($isHalfDay ? 'dtr-status-half-day' : ($isLate || $isEarlyOutStatus ? 'dtr-late' : ($isWeekend ? 'dtr-empty' : ($isNoRecordYet ? 'dtr-empty' : '')))))));
                                            $displayStatus = $isLWOP ? 'LWOP' : ($isLeave ? 'Leave Paid' : ($isHoliday && !$day['log'] ? 'Holiday' : ($isAbsent ? 'Absent' : ($attendanceStatus ?: ($isWeekend ? 'WKD' : ($isNoRecordYet ? '' : 'Present'))))));
                                            $isEarlyOut = !empty($day['pm_out']) && $day['pm_out'] !== '--' && strtotime($day['pm_out']) < strtotime('17:00');
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>
                                                <strong>{{ $day['date']->format('M d, Y') }}</strong><br>
                                                <small class="text-muted">{{ $day['day_name'] }}</small>
                                            </td>
                                            <td class="text-center {{ $isLateTimeIn && !$isLeave && !$isLWOP ? 'dtr-late' : '' }}">{{ !$isLeave && !$isLWOP ? ($day['am_in'] ?? '') : '--' }}</td>
                                            <td class="text-center {{ $isEarlyOut && !$isLeave && !$isLWOP ? 'dtr-late' : '' }}">{{ !$isLeave && !$isLWOP ? ($day['pm_out'] ?? '') : '--' }}</td>
                                            <td class="text-center" style="
                                                @if ($isLWOP)
                                                    background-color: #ff0000; color: #B4E1EB; font-weight: 700;
                                                @elseif ($isLeave)
                                                    background-color: #92EEFF; color: #111; font-weight: 700;
                                                @elseif ($isHoliday && !$day['log'])
                                                    background-color: #fef3c7; color: #92400e; font-weight: 700;
                                                @elseif ($isHalfDay)
                                                    background-color: #fed7aa; color: #9a3412; font-weight: 700;
                                                @elseif ($isAbsent)
                                                    background-color: #ff0000; color: #111; font-weight: 700;
                                                @elseif ($isWeekend)
                                                    background-color: #d1d5db; color: #374151; font-weight: 700;
                                                @elseif ($isNoRecordYet)
                                                    background-color: transparent; color: transparent; font-weight: 700;
                                                @else
                                                    background-color: #005F02; color: #ffffff; font-weight: 700;
                                                @endif
                                            ">
                                                @if ($isLWOP)
                                                    LWOP
                                                @elseif ($isLeave)
                                                    Leave Paid
                                                @elseif ($isHoliday && !$day['log'])
                                                    Holiday
                                                @elseif ($isHalfDay)
                                                    Half Day
                                                @elseif ($isAbsent)
                                                    Absent
                                                @elseif ($isLate)
                                                    {{ $attendanceStatus }}
                                                @elseif ($isEarlyOutStatus)
                                                    Early Out
                                                @elseif ($isWeekend)
                                                    WKD
                                                @elseif ($isNoRecordYet)
                                                @else
                                                    Present
                                                @endif
                                                @php
                                                    $legacyApprovedWithoutCorrection = $day['log']
                                                        && $day['log']->override_status === 'approved'
                                                        && !$day['log']->corrected_time_in
                                                        && $day['log']->am_in
                                                        && $day['log']->am_in->format('H:i:s') > '07:00:00';
                                                @endphp
                                                @if ($day['log'] && (in_array($attendanceStatus, ['Late', 'Late / Early Out', 'Early Out', 'Half Day'], true) || $legacyApprovedWithoutCorrection) && (!in_array($day['log']->override_status, ['pending_branch', 'pending_system_admin', 'approved'], true) || $legacyApprovedWithoutCorrection))
                                                    <form method="POST" action="{{ route('employee.dtr.attendance.adjust-present', $day['log']->id) }}" class="mt-2">
                                                        @csrf
                                                        @if (in_array($attendanceStatus, ['Late', 'Late / Early Out', 'Half Day'], true) || $legacyApprovedWithoutCorrection)
                                                            <label class="small d-block mb-1">Correct time-in (optional)</label>
                                                            <input type="time" name="corrected_time_in" class="form-control form-control-sm mb-1" value="{{ $day['log']->am_in?->format('H:i') }}">
                                                        @endif
                                                        @if ($attendanceStatus === 'Half Day')
                                                            <label class="small d-block mb-1">Correct PM time-in (optional)</label>
                                                            <input type="time" name="corrected_pm_in" class="form-control form-control-sm mb-1" value="{{ $day['log']->pm_in?->format('H:i') }}">
                                                        @endif
                                                        @if (in_array($attendanceStatus, ['Early Out', 'Late / Early Out', 'Half Day'], true))
                                                            <label class="small d-block mb-1">Correct time-out (optional)</label>
                                                            <input type="time" name="corrected_time_out" class="form-control form-control-sm mb-1" value="{{ $day['log']->pm_out?->format('H:i') }}">
                                                        @endif
                                                        <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="Reason" required minlength="5">
                                                        <button type="submit" class="btn btn-sm btn-outline-dark">Request Attendance Correction</button>
                                                    </form>
                                                @elseif ($day['log'] && in_array($day['log']->override_status, ['pending_branch', 'pending_system_admin'], true))
                                                    <small class="d-block text-muted mt-1">Adjustment pending approval</small>
                                                @elseif ($day['log'] && $day['log']->override_status === 'approved')
                                                    <small class="d-block text-success mt-1">Adjusted to Present</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No attendance records for this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Remarks Section -->
    @if ($dtr->remarks)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Remarks</h6>
                    </div>
                    <div class="card-body">
                        {{ $dtr->remarks }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
