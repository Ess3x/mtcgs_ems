@extends('layouts.app')

@section('title', 'ID Verification')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h2><i class="fas fa-id-card me-2"></i> ID Verification</h2>
                    <p class="mb-0">Review and verify employee registrations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- SIMPLE BUTTON TABS - GARANTISADONG HIWALAY -->
    <div class="mb-3">
        <a href="?tab=pending" class="btn {{ request('tab') == 'rejected' || request('tab') == 'deactivated' ? 'btn-secondary' : 'btn-primary' }}">
            <i class="fas fa-clock"></i> Pending ({{ $pendingUsers->count() }})
        </a>
        <a href="?tab=rejected" class="btn {{ request('tab') == 'rejected' ? 'btn-primary' : 'btn-secondary' }}">
            <i class="fas fa-times-circle"></i> Rejected ({{ $rejectedUsers->count() }})
        </a>
        <a href="?tab=deactivated" class="btn {{ request('tab') == 'deactivated' ? 'btn-primary' : 'btn-secondary' }}">
            <i class="fas fa-ban"></i> Deactivated ({{ $deactivatedUsers->count() }})
        </a>
        @if(Auth::user()->admin_type === 'super_admin')
            <a href="?tab=status_changes" class="btn {{ request('tab') == 'status_changes' ? 'btn-primary' : 'btn-secondary' }}">
                <i class="fas fa-user-edit"></i> Status Changes ({{ $pendingStatusChanges->count() }})
            </a>
        @endif
    </div>

    @if(request('tab') == 'status_changes' && Auth::user()->admin_type === 'super_admin')
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-user-edit text-warning me-2"></i>Pending Employee Status Changes</h5>
            <small class="text-muted">Review changes submitted by branch heads before applying them.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>Date</th><th>Employee</th><th>Email</th><th>Branch</th><th>Current Status</th><th>Requested Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($pendingStatusChanges as $employee)
                    <tr>
                        <td>{{ optional($employee->status_change_requested_at)->format('Y-m-d H:i') }}</td>
                        <td><strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong></td>
                        <td>{{ $employee->user->email ?? 'N/A' }}</td>
                        <td>{{ $employee->branch->branch_name ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ $employee->status ?? 'New Hire' }}</span></td>
                        <td><span class="badge bg-warning text-dark">{{ $employee->pending_status }}</span></td>
                        <td class="text-nowrap">
                            <form action="{{ route('admin.verify.status-change.approve', $employee->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success" onclick="return confirm('Approve this status change?')">Approve</button>
                            </form>
                            <form action="{{ route('admin.verify.status-change.reject', $employee->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="reason" value="Rejected by System Administrator">
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Reject this status change?')">Reject</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5">No pending status changes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- PENDING TABLE (visible pag walang tab o pending ang napili) -->
    @if(!request('tab') || request('tab') == 'pending')
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-clock text-warning me-2"></i>Pending Verifications</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Name</th><th>Email</th><th>Employee #</th><th>Role</th><th>ID</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingUsers as $user)
                    <tr>
                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</small></td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ optional($user->profile)->employee_number ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($user->role) }}</span></td>
                        <td>
                            @if($user->id_document_path)
                                <a href="{{ route('admin.verify.document', $user->id) }}" target="_blank" class="btn btn-sm btn-info me-1">View ID</a>
                            @else
                                <span class="text-muted">No ID</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#viewModal{{ $user->id }}">View</button>
                            <form action="{{ route('admin.verify.approve', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success me-1" onclick="return confirm('Approve?')">Approve</button>
                            </form>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $user->id }}">Reject</button>
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5">No pending verifications</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- REJECTED TABLE (visible pag rejected ang napili) -->
    @if(request('tab') == 'rejected')
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-times-circle text-danger me-2"></i>Rejected Registrations</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Name</th><th>Email</th><th>Employee #</th><th>Role</th><th>Reason</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rejectedUsers as $user)
                    <tr>
                        <td>{{ $user->updated_at->format('Y-m-d H:i') }}</small></td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ optional($user->profile)->employee_number ?? 'N/A' }}</td>
                        <td><span class="badge bg-danger">{{ ucfirst($user->role) }}</span></td>
                        <td>{{ $user->rejection_reason ?? 'No reason' }}</small></td>
                        <td>
                            <form action="{{ route('admin.verify.approve', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success" onclick="return confirm('Approve?')">Approve</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5">No rejected registrations</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- DEACTIVATED TABLE (visible pag deactivated ang napili) -->
    @if(request('tab') == 'deactivated')
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-ban text-secondary me-2"></i>Deactivated Accounts</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>Email</th><th>Role</th><th>Deactivated Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deactivatedUsers as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($user->role) }}</span></td>
                        <td>{{ $user->updated_at->format('Y-m-d H:i') }}</small></td>
                        <td>
                            <form action="{{ route('admin.verify.reactivate', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success" onclick="return confirm('Reactivate?')">Reactivate</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                        <td><td colspan="5" class="text-center py-5">No deactivated accounts</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Reject Modals -->
@foreach($pendingUsers as $user)
<div class="modal fade" id="viewModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verification details: {{ $user->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row gx-4 gy-3 align-items-start">
                    <div class="col-lg-6">
                        <div class="card border-secondary mb-3 h-100">
                            <div class="card-header bg-light"><strong>User Details</strong></div>
                            <div class="card-body p-3">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="width:30%;" class="text-end pe-3">Name</th>
                                            <td>{{ $user->name }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Email</th>
                                            <td>{{ $user->email }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Employee #</th>
                                            <td>{{ optional($user->profile)->employee_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Role</th>
                                            <td>{{ ucfirst($user->role) }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Position</th>
                                            <td>{{ optional($user->profile)->position ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Branch</th>
                                            <td>{{ optional(optional($user->profile)->branch)->branch_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-end pe-3">Submitted at</th>
                                            <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                        @if(optional($user->profile)->contact_number)
                                        <tr>
                                            <th class="text-end pe-3">Contact</th>
                                            <td>{{ $user->profile->contact_number }}</td>
                                        </tr>
                                        @endif
                                        @if(optional($user->profile)->address)
                                        <tr>
                                            <th class="text-end pe-3 align-top">Address</th>
                                            <td>{{ $user->profile->address }}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-secondary mb-3">
                            <div class="card-header bg-light"><strong>ID Document</strong></div>
                            <div class="card-body text-center">
                                @if($user->id_document_path)
                                    @php
                                        $documentUrl = route('admin.verify.document', $user->id);
                                        $extension = strtolower(pathinfo($user->id_document_path, PATHINFO_EXTENSION));
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                    @endphp
                                    @if(in_array($extension, $imageExtensions))
                                        <img src="{{ $documentUrl }}" alt="ID Document" class="img-fluid rounded" style="max-height:420px; width:auto;" />
                                    @else
                                        <p class="text-muted">ID file is not a supported image preview.</p>
                                        <a href="{{ $documentUrl }}" target="_blank" class="btn btn-sm btn-info">Open ID Document</a>
                                    @endif
                                @else
                                    <p class="text-muted">No ID document uploaded.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form action="{{ route('admin.verify.approve', $user->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.verify.reject', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject: {{ $user->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason for rejection:</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Enter reason..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
