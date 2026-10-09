@extends('layouts.app')

@section('title', 'DTR / Attendance')

@section('content')
<style>
    .dtr-management-page .nav-tabs .nav-link {
        color: #334155 !important;
        background-color: #e2e8f0;
        border-color: #cbd5e1;
    }

    .dtr-management-page .nav-tabs .nav-link:hover,
    .dtr-management-page .nav-tabs .nav-link.active {
        color: #0f172a !important;
        background-color: #ffffff;
        border-color: #cbd5e1 #cbd5e1 #ffffff;
    }

    body.dark-mode .dtr-management-page .nav-tabs .nav-link {
        color: #e2e8f0 !important;
        background-color: #1e293b;
        border-color: #475569;
    }

    body.dark-mode .dtr-management-page .nav-tabs .nav-link:hover,
    body.dark-mode .dtr-management-page .nav-tabs .nav-link.active {
        color: #ffffff !important;
        background-color: #334155;
        border-color: #64748b #64748b #334155;
    }
</style>

<div class="container-fluid py-4 dtr-management-page" style="background-color: #0a1221; min-height: 100vh;">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #4f8fe9 0%, #3a73d8 100%); color: white;">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h2 class="mb-1 text-white">DTR / Attendance</h2>
                            <p class="text-white-50 mb-0">Review and manage attendance records and DTR updates for payroll and monitoring.</p>
                        </div>
                        <div class="text-end mt-2 mt-md-0">
                            <span class="badge bg-white text-primary py-2 px-3">Updated {{ now()->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #6a73ff, #4f8fe9);">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Total DTRs</p>
                            <h3 class="mb-0 text-white">{{ $totalDTRs }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #f7b731, #f39c12);">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Pending</p>
                            <h3 class="mb-0 text-warning">{{ $pendingCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #32c787, #28a745);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Approved</p>
                            <h3 class="mb-0 text-success">{{ $approvedDTRsCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #ff6b6b, #dc3545);">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Rejected</p>
                            <h3 class="mb-0 text-danger">{{ $rejectedDTRs }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: rgba(10,18,33,0.55); border: 1px solid rgba(255,255,255,0.08)!important;">
                <div class="card-header bg-transparent border-0 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 py-3">
                    <div>
                        <p class="text-white-50 mb-0">Browse pending and approved DTRs in one place.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark py-2 px-3">Pending {{ $pendingCount }}</span>
                        <span class="badge bg-success py-2 px-3">Approved {{ $approvedDTRsCount }}</span>
                        @if(Auth::user()->isBranchAdmin() || Auth::user()->isSuperAdmin())
                            <a href="{{ route('admin.dtr.export-submitted') }}" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel me-1"></i> Export DTR Excel
                            </a>
                            @if(Auth::user()->isBranchAdmin() && $pendingCount > 0)
                                <form method="POST" action="{{ route('admin.dtr.submit-all-to-hr') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Submit all submitted branch DTRs and the combined Excel report to HR?')">
                                        <i class="fas fa-paper-plane me-1"></i> Submit All to HR
                                    </button>
                                </form>
                            @endif
                            @if(Auth::user()->isSuperAdmin() && $pendingCount > 0)
                                <form method="POST" action="{{ route('admin.dtr.return-all-to-bh') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Return all pending DTRs to the Branch Heads for review?')">
                                        <i class="fas fa-undo me-1"></i> Return All to BH
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.dtr.submit-all-to-finance-head') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Submit all HR-reviewed DTRs and the combined Excel report to the Finance Head?')">
                                        <i class="fas fa-paper-plane me-1"></i> Submit All to FH
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="card-body p-3">
                    <ul class="nav nav-tabs mb-3" role="tablist" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-white" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-tab-pane" type="button" role="tab" aria-controls="pending-tab-pane" aria-selected="true">Pending DTRs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved-tab-pane" type="button" role="tab" aria-controls="approved-tab-pane" aria-selected="false">Approved DTRs</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel" aria-labelledby="pending-tab">
                            @if ($pendingDTRs->count() > 0)
                                @php
                                    $pendingGroups = Auth::user()->isSuperAdmin() ? $pendingDTRs->groupBy(fn($dtr) => $dtr->employeeProfile && $dtr->employeeProfile->branch ? $dtr->employeeProfile->branch->branch_name : 'Unassigned Branch') : [null => $pendingDTRs];
                                @endphp

                                @foreach ($pendingGroups as $branchName => $branchDTRs)
                                    <div class="mb-4">
                                        @if (Auth::user()->isSuperAdmin())
                                            <div class="fw-bold text-white mb-2 border-bottom pb-2">{{ $branchName }}</div>
                                        @endif
                                        <div class="table-responsive border rounded-3 bg-white shadow-sm">
                                            @php
                                                $firstDtr = $branchDTRs->first();
                                                $periodHeaders = [];
                                                if ($firstDtr) {
                                                    $current = $firstDtr->period_start->copy();
                                                    $end = $firstDtr->period_end;
                                                    while ($current <= $end) {
                                                        if (!in_array($current->dayOfWeek, [0, 6], true)) {
                                                            $periodHeaders[] = $current->copy();
                                                        }
                                                        $current->addDay();
                                                    }
                                                }
                                            @endphp

                                            <table class="table table-sm align-middle mb-0" style="border-collapse: collapse; border: 1px solid #c8ced6;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-uppercase small text-muted fw-semibold" style="min-width: 180px; border: 1px solid #c8ced6; background-color: #f8f9fa; padding: 0.85rem 0.75rem;">Employee</th>
                                                        @foreach ($periodHeaders as $headerDate)
                                                            <th class="text-uppercase small text-muted fw-semibold text-center" style="min-width: 76px; width: 90px; border: 1px solid #c8ced6; background-color: #f8f9fa; padding: 0.85rem 0.2rem; text-align: center; vertical-align: middle;">{{ $headerDate->format('m-d-Y') }}</th>
                                                        @endforeach
                                                        <th class="text-uppercase small text-muted fw-semibold text-center" style="border: 1px solid #c8ced6; background-color: #f8f9fa; padding: 0.85rem 0.75rem;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($branchDTRs as $dtr)
                                                        @php
                                                            $logsByDate = $dtr->attendanceLogs()->keyBy(function ($log) {
                                                                return $log->attendance_date->format('Y-m-d');
                                                            });

                                                            $approvedLeaves = \App\Models\LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
                                                                ->where('status', 'approved')
                                                                ->whereDate('start_date', '<=', $dtr->period_end)
                                                                ->whereDate('end_date', '>=', $dtr->period_start)
                                                                ->get();

                                                            $approvedLeaveByDate = [];
                                                            foreach ($approvedLeaves as $leave) {
                                                                $current = $leave->start_date->copy();
                                                                $end = $leave->end_date->copy();
                                                                while ($current <= $end) {
                                                                    $approvedLeaveByDate[$current->format('Y-m-d')] = $leave;
                                                                    $current->addDay();
                                                                }
                                                            }
                                                        @endphp
                                                        <tr>
                                                            <td class="text-wrap align-middle" style="min-width: 180px; border: 1px solid #c8ced6; padding: 0.8rem 0.75rem; min-height: 72px;">
                                                                <div class="fw-bold text-dark">{{ $dtr->employeeProfile->first_name }} {{ $dtr->employeeProfile->last_name }}</div>
                                                                <div class="small text-muted">{{ $dtr->employeeProfile->employee_number }}</div>
                                                            </td>
                                                            @foreach ($periodHeaders as $headerDate)
                                                                @php
                                                                    $dateStr = $headerDate->format('Y-m-d');
                                                                    $log = $logsByDate->get($dateStr);
                                                                    $approvedLeave = $approvedLeaveByDate[$dateStr] ?? null;
                                                                    $statusText = '';
                                                                    $statusClass = 'text-muted';
                                                                    $inTimeClass = 'small fw-bold text-dark';
                                                                    $outTimeClass = 'small fw-bold text-dark';

                                                                    if ($approvedLeave) {
                                                                        $statusText = (bool) $approvedLeave->is_absent ? 'Leave Without Pay' : 'Paid Leave';
                                                                        $statusClass = (bool) $approvedLeave->is_absent ? 'text-warning fw-bold' : 'text-info fw-bold';
                                                                    } elseif ($log) {
                                                                        $hasAnyAttendanceTime = (bool) ($log->am_in || $log->am_out || $log->pm_in || $log->pm_out);
                                                                        $statusText = $hasAnyAttendanceTime ? $log->getDtrStatus() : 'Pending';
                                                                        $shift = $log->employeeProfile?->shift;
                                                                        $scheduledStart = $shift?->start_time ?: '07:00:00';
                                                                        $scheduledEnd = $shift?->end_time ?: '17:00:00';

                                                                        if ($log->am_in) {
                                                                            $inTimeClass = $log->am_in->gt($log->am_in->copy()->setTimeFromTimeString($scheduledStart)) ? 'small fw-bold text-danger' : 'small fw-bold text-success';
                                                                        }

                                                                        if ($log->pm_out) {
                                                                            $outTimeClass = $log->pm_out->lt($log->pm_out->copy()->setTimeFromTimeString($scheduledEnd)) ? 'small fw-bold text-danger' : 'small fw-bold text-success';
                                                                        }

                                                                        if ($statusText === 'Half Day') {
                                                                            $statusClass = 'text-primary fw-bold';
                                                                        } elseif (in_array($statusText, ['Late', 'Early Out', 'Late / Early Out', 'Absent'], true)) {
                                                                            $statusClass = 'text-danger fw-bold';
                                                                        } elseif ($statusText === 'Present') {
                                                                            $statusClass = 'text-success fw-bold';
                                                                        }
                                                                    } else {
                                                                        $statusText = 'Pending';
                                                                        $statusClass = 'text-muted';
                                                                    }
                                                                @endphp
                                                                <td class="text-center align-middle" style="min-width: 76px; width: 90px; border: 1px solid #c8ced6; padding: 0.5rem 0.2rem; min-height: 72px; text-align: center; vertical-align: middle;">
                                                                    @if ($log)
                                                                        <div class="small fw-semibold text-dark">IN</div>
                                                                        <div class="{{ $inTimeClass }}">{{ $log->am_in ? $log->am_in->format('h:i A') : '--' }}</div>
                                                                        <div class="small fw-semibold text-dark mt-1">OUT</div>
                                                                        <div class="{{ $outTimeClass }}">{{ $log->pm_out ? $log->pm_out->format('h:i A') : '--' }}</div>
                                                                    @else
                                                                        <span class="{{ $statusClass }}">
                                                                            {{ $statusText }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                            <td class="text-center align-middle" style="border: 1px solid #c8ced6; padding: 0.8rem 0.75rem; min-height: 72px;">
                                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                                    <a href="{{ route('admin.dtr.show', $dtr->id) }}" class="btn btn-sm btn-outline-primary" title="Review">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    @if($canApprove)
                                                                        <form action="{{ route('admin.dtr.approve', $dtr->id) }}" method="POST" class="d-inline">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                                                <i class="fas fa-check"></i>
                                                                            </button>
                                                                        </form>
                                                                        <form action="{{ route('admin.dtr.reject', $dtr->id) }}" method="POST" class="d-inline">
                                                                            @csrf
                                                                            <input type="hidden" name="remarks" value="Rejected by administrator" />
                                                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this DTR and send back to employee?')">
                                                                                <i class="fas fa-times"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="d-flex justify-content-center mt-3">
                                    {{ $pendingDTRs->links() }}
                                </div>
                            @else
                                <div class="alert alert-info mb-0" style="background-color: rgba(23,162,184,0.2); border-color: #17a2b8; color: #b8daff;">
                                    <i class="fas fa-info-circle"></i>
                                    @if (Auth::user()->isBranchAdmin())
                                        No submitted DTRs have been received for this branch yet. Once employees submit their DTRs, they will appear here with the employee name.
                                    @elseif (Auth::user()->isSuperAdmin())
                                        No DTRs are waiting for HR review at the moment.
                                    @else
                                        No pending DTRs awaiting approval.
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Approved DTRs Tab -->
                        <div class="tab-pane fade" id="approved-tab-pane" role="tabpanel" aria-labelledby="approved-tab">
                            @if ($approvedDTRs->count() > 0)
                                @php
                                    $approvedGroups = $approvedDTRs->groupBy(fn($dtr) => $dtr->employeeProfile && $dtr->employeeProfile->branch ? $dtr->employeeProfile->branch->branch_name : 'Unassigned Branch');
                                @endphp

                                @foreach ($approvedGroups as $branchName => $branchDTRs)
                                    <div class="mb-4">
                                        <div class="fw-bold text-white mb-2 border-bottom pb-2">
                                            <i class="fas fa-building me-2 text-info"></i>{{ $branchName }}
                                        </div>
                                        @php
                                            $periodGroups = $branchDTRs->groupBy(fn($dtr) => $dtr->period_start->format('Y-m-d') . '|' . $dtr->period_end->format('Y-m-d'));
                                        @endphp
                                        @foreach ($periodGroups as $periodKey => $periodDTRs)
                                            @php $period = $periodDTRs->first(); @endphp
                                            <div class="mb-3">
                                                @php $periodPanelId = 'approved-period-' . md5($branchName . '-' . $periodKey); @endphp
                                                <button class="btn btn-link text-decoration-none text-start text-white-50 small fw-semibold p-0 mb-2 w-100"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $periodPanelId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $periodPanelId }}">
                                                    <i class="fas fa-calendar-alt me-2"></i>
                                                    Period: {{ $period->period_start->format('M d') }} - {{ $period->period_end->format('M d, Y') }}
                                                    <i class="fas fa-chevron-down ms-2"></i>
                                                    <span class="badge bg-secondary ms-2">{{ $periodDTRs->count() }} DTR{{ $periodDTRs->count() === 1 ? '' : 's' }}</span>
                                                </button>
                                                <div id="{{ $periodPanelId }}" class="collapse">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover table-sm align-middle mb-0" style="color: white;">
                                                            <thead style="background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.1);">
                                                                <tr>
                                                                    <th>Employee</th>
                                                                    <th>Period</th>
                                                                    <th class="text-center">Days Present</th>
                                                                    <th class="text-center">Total Hours</th>
                                                                    <th class="text-center">Overtime</th>
                                                                    <th class="text-center">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($periodDTRs as $dtr)
                                                                    <tr style="border-color: rgba(255,255,255,0.05);">
                                                                        <td class="text-wrap" style="min-width: 180px;">
                                                                            <strong>{{ $dtr->employeeProfile->first_name }} {{ $dtr->employeeProfile->last_name }}</strong>
                                                                            <div class="text-white-50 small">{{ $dtr->employeeProfile->employee_number }}</div>
                                                                        </td>
                                                                        <td class="text-wrap" style="min-width: 160px;">{{ $dtr->period_start->format('M d') }} - {{ $dtr->period_end->format('M d, Y') }}</td>
                                                                        <td class="text-center text-nowrap">{{ $dtr->getDaysPresent() }}/{{ $dtr->getWorkingDays() }}</td>
                                                                        <td class="text-center text-nowrap">{{ number_format($dtr->getTotalHoursWorked(), 1) }} hrs</td>
                                                                        <td class="text-center text-nowrap">
                                                                            @php $ot = $dtr->getTotalOvertimeHours(); @endphp
                                                                            @if ($ot > 0)
                                                                                <span class="badge bg-warning">{{ number_format($ot, 1) }} hrs</span>
                                                                            @else
                                                                                <span class="text-white-50">--</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                                                <a href="{{ route('admin.dtr.show', $dtr->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                                                                    <i class="fas fa-eye"></i>
                                                                                </a>
                                                                                @if(Auth::user()->isFinanceOfficer())
                                                                                    <a href="{{ route('finance.payroll-generation.ready-dtrs') }}" class="btn btn-sm btn-success" title="Generate Payroll">
                                                                                        <i class="fas fa-calculator"></i>
                                                                                    </a>
                                                                                @endif
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach

                                <div class="d-flex justify-content-center mt-3">
                                    {{ $approvedDTRs->links() }}
                                </div>
                            @else
                                <div class="alert alert-info mb-0" style="background-color: rgba(23,162,184,0.2); border-color: #17a2b8; color: #b8daff;">
                                    <i class="fas fa-info-circle"></i> No approved DTRs ready for payroll generation.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

