@extends('layouts.app')

@section('content')
<style>
    .leave-action-btn {
        width: 150px;
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        white-space: nowrap;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Leave Requests</h1>
    <a href="{{ route('leave.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> File Leave
    </a>
</div>

@if(in_array(Auth::user()->role, ['employee', 'finance_officer', 'finance_head'], true))
    @php
        $hasPendingLeave = ($leaves ?? collect())->contains(function ($leave) {
            return in_array($leave->status, ['pending', 'pending_system_admin'], true);
        });
    @endphp
    @if($hasPendingLeave)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Leave request submitted.</strong> Your leave is waiting for Branch Head and System Administrator approval. Leave credits will be deducted only after final approval by the System Administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves ?? [] as $leave)
                    <tr>
                        <td>{{ optional($leave->employeeProfile)->employee_number ?? 'N/A' }}</td>
                        <td>{{ optional($leave->employeeProfile)->full_name ?? 'N/A' }}</td>
                        <td>{{ optional($leave->employeeProfile)->position ?? 'N/A' }}</td>
                        <td>{{ ucfirst($leave->leave_type) }}</td>
                        <td>{{ $leave->start_date }} to {{ $leave->end_date }}</td>
                        <td>{{ $leave->total_days }}</td>
                        <td>{{ $leave->reason }}</td>
                        <td>
                            @if($leave->status == 'approved')
                                @if($leave->is_absent)
                                    <span class="badge bg-danger">Approved - Absent</span>
                                @else
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            @elseif($leave->status == 'pending_system_admin')
                                <span class="badge bg-info text-dark">Pending System Administrator Approval</span>
                            @elseif($leave->status == 'pending')
                                @if(optional($leave->employeeProfile)->user?->admin_type === 'branch_admin')
                                    <span class="badge bg-warning text-dark">Pending System Administrator Approval</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending Branch Head Approval</span>
                                @endif
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(Auth::user()->isAdmin())
                                @if($leave->status == 'pending' && Auth::user()->isBranchAdmin() && !Auth::user()->isSuperAdmin() && optional($leave->employeeProfile)->user?->admin_type !== 'branch_admin')
                                    <a href="{{ route('leave.approve', $leave->id) }}" class="btn btn-sm btn-success leave-action-btn" title="Approve leave request">
                                        <i class="fas fa-check"></i> Approve & Forward
                                    </a>
                                    <a href="{{ route('leave.reject', $leave->id) }}" class="btn btn-sm btn-danger leave-action-btn" title="Reject leave request">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                @elseif(Auth::user()->admin_type === 'super_admin' && in_array($leave->status, ['pending', 'pending_system_admin'], true))
                                    <a href="{{ route('leave.approve', $leave->id) }}" class="btn btn-sm btn-success leave-action-btn" title="Approve leave request">
                                        <i class="fas fa-check"></i> Final Approve
                                    </a>
                                    <a href="{{ route('leave.reject', $leave->id) }}" class="btn btn-sm btn-danger leave-action-btn" title="Reject leave request">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            @elseif(in_array(Auth::user()->role, ['employee', 'finance_officer', 'finance_head']) && $leave->status == 'rejected')
                                <a href="{{ route('leave.create') }}" class="btn btn-sm btn-outline-primary" title="File another leave request">
                                    <i class="fas fa-redo"></i> File Again
                                </a>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox"></i> No leave requests
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
