@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #4f8fe9 0%, #3a73d8 100%); color: white;">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h2 class="mb-1 text-white">Attendance Management</h2>
                            <p class="text-white-50 mb-0">Review and approve attendance adjustment requests submitted by employees.</p>
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
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #f7b731, #f39c12);">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Pending Branch Review</p>
                            <h3 class="mb-0">{{ $pendingBranchCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #6a73ff, #4f8fe9);">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Pending HR Review</p>
                            <h3 class="mb-0">{{ $pendingSystemAdminCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-4 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;background: linear-gradient(135deg, #32c787, #28a745);">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <div>
                            <p class="text-uppercase text-muted mb-1 small">Total Pending Requests</p>
                            <h3 class="mb-0">{{ $pendingAdjustments->total() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 py-3">
            <h5 class="mb-0">Attendance Adjustment Requests</h5>
        </div>
        <div class="card-body p-0">
            @if ($pendingAdjustments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Current Time</th>
                                <th>Requested Correction</th>
                                <th>Review Stage</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingAdjustments as $log)
                                <tr>
                                    <td>
                                        <strong>{{ $log->employeeProfile?->first_name }} {{ $log->employeeProfile?->last_name }}</strong>
                                        <div class="text-muted small">{{ $log->employeeProfile?->employee_number }}</div>
                                    </td>
                                    <td>{{ $log->attendance_date->format('M d, Y') }}</td>
                                    <td>
                                        <div class="small text-muted">Time In: {{ $log->am_in ? $log->am_in->format('h:i A') : '--' }}</div>
                                        <div class="small text-muted">Time Out: {{ $log->pm_out ? $log->pm_out->format('h:i A') : '--' }}</div>
                                    </td>
                                    <td>
                                        <div class="small text-muted">Corrected In: {{ $log->corrected_time_in ? $log->corrected_time_in->format('h:i A') : '--' }}</div>
                                        <div class="small text-muted">Corrected PM In: {{ $log->corrected_pm_in ? $log->corrected_pm_in->format('h:i A') : '--' }}</div>
                                        <div class="small text-muted">Corrected Out: {{ $log->corrected_time_out ? $log->corrected_time_out->format('h:i A') : '--' }}</div>
                                    </td>
                                    <td>
                                        @if ($log->override_status === 'pending_branch')
                                            <span class="badge bg-warning text-dark">Pending Branch Review</span>
                                        @else
                                            <span class="badge bg-info text-dark">Pending HR Review</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (Auth::user()->isBranchAdmin() && $log->override_status === 'pending_branch')
                                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                <form method="POST" action="{{ route('admin.dtr.attendance.approve-adjustment', $log->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.dtr.attendance.reject-adjustment', $log->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                </form>
                                            </div>
                                        @elseif (Auth::user()->isSuperAdmin() && in_array($log->override_status, ['pending_branch', 'pending_system_admin'], true))
                                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                <form method="POST" action="{{ route('admin.dtr.attendance.approve-adjustment', $log->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success" type="submit">{{ $log->override_status === 'pending_system_admin' ? 'Final Approve' : 'Approve' }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.dtr.attendance.reject-adjustment', $log->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted small">Awaiting review</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3 pb-3">
                    {{ $pendingAdjustments->links() }}
                </div>
            @else
                <div class="alert alert-info m-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>No attendance adjustment requests are currently pending.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
