@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">User Management</h1>

    @if(Auth::user()->admin_type === 'super_admin' && $pendingFinanceChanges->count())
        <div class="card border-warning mb-4">
            <div class="card-header bg-warning-subtle">
                <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>Pending Finance Officer Changes</h5>
                <small>Review edits submitted by Branch Heads before applying them.</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Finance Officer</th><th>Branch</th><th>Current Details</th><th>Requested Details</th><th>Actions</th></tr></thead>
                    <tbody>
                    @foreach($pendingFinanceChanges as $finance)
                        <tr>
                            <td>{{ $finance->first_name }} {{ $finance->last_name }}<br><small>{{ $finance->user->email ?? 'N/A' }}</small></td>
                            <td>{{ $finance->branch->branch_name ?? 'N/A' }}</td>
                            <td>{{ $finance->position }}<br>{{ $finance->user->email ?? 'N/A' }}</td>
                            <td>{{ $finance->pending_changes['first_name'] ?? '' }} {{ $finance->pending_changes['last_name'] ?? '' }}<br>{{ $finance->pending_changes['position'] ?? '' }}<br>{{ $finance->pending_changes['email'] ?? '' }}</td>
                            <td class="text-nowrap">
                                <form method="POST" action="{{ route('admin.user-finance-approve', $finance->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success" onclick="return confirm('Approve these Finance Officer changes?')"><i class="fas fa-check"></i> Approve</button></form>
                                <form method="POST" action="{{ route('admin.user-finance-reject', $finance->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-danger" onclick="return confirm('Reject these Finance Officer changes?')"><i class="fas fa-times"></i> Reject</button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body"><h5>Total Users</h5><h2>{{ $totalUsers ?? 0 }}</h2></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body"><h5>Active</h5><h2>{{ $activeCount ?? 0 }}</h2></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body"><h5>Inactive</h5><h2>{{ $inactiveCount ?? 0 }}</h2></div>
            </div>
        </div>
    </div>
    
    <ul class="nav nav-tabs tab-switcher mb-3">
        <li class="nav-item"><button class="nav-link {{ request('tab') === 'finance' ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#empTab">Employees ({{ count($employees) }})</button></li>
        <li class="nav-item"><button class="nav-link {{ request('tab') === 'finance' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#finTab">Finance Officers ({{ count($financeOfficers) }})</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#admTab">Administrators ({{ count($admins) }})</button></li>
    </ul>

    <style>
        .tab-switcher {
            display: inline-flex;
            width: auto;
            background: rgba(9, 30, 45, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 10px 10px 0 0;
            overflow: hidden;
            margin-bottom: 0;
        }

        .tab-switcher .nav-item {
            margin: 0;
        }

        .tab-switcher .nav-link {
            border: 0;
            border-radius: 0;
            color: #a9d2ff;
            background: transparent;
            padding: 0.9rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .tab-switcher .nav-link:hover {
            color: #ffffff;
        }

        .tab-switcher .nav-link.active {
            background: #f5f7fb;
            color: #0f172a;
            box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.08);
        }
    </style>
    
    <div class="tab-content mt-3">
        <div class="tab-pane fade {{ request('tab') === 'finance' ? '' : 'show active' }}" id="empTab">
            <div class="card"><div class="card-header">Employee List</div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Branch</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@foreach($employees as $emp)<tr><td>{{ $emp['name'] }}</td><td>{{ $emp['email'] }}</td><td>{{ $emp['position'] ?? 'N/A' }}</td><td>{{ $emp['branch'] ?? 'N/A' }}</td><td>@if($emp['is_active'])<span class="badge bg-success">Active</span>@else<span class="badge bg-danger">Inactive</span>@endif</td><td><a href="{{ route('admin.user-edit', ['role' => 'employee', 'id' => $emp['profile_id']]) }}" class="btn btn-sm btn-primary">Edit</a></td></tr>@endforeach</tbody></table></div></div>
        </div>
        <div class="tab-pane fade {{ request('tab') === 'finance' ? 'show active' : '' }}" id="finTab">
            <div class="card"><div class="card-header">Finance Officer List</div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@foreach($financeOfficers as $fin)<tr><td>{{ $fin['name'] }}</td><td>{{ $fin['email'] }}</td><td>{{ $fin['position'] ?? 'Finance Officer' }}</td><td>@if($fin['is_active'])<span class="badge bg-success">Active</span>@else<span class="badge bg-danger">Inactive</span>@endif</td><td><a href="{{ route('admin.user-edit', ['role' => 'finance', 'id' => $fin['profile_id']]) }}" class="btn btn-sm btn-primary">Edit</a></td></tr>@endforeach</tbody></table></div></div>
        </div>
        <div class="tab-pane fade" id="admTab">
            <div class="card"><div class="card-header">Administrator List</div>
            <div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@foreach($admins as $admin)<tr><td>{{ $admin['name'] }}</td><td>{{ $admin['email'] }}</td><td>{{ $admin['position'] ?? 'Administrator' }}</td><td>@if($admin['is_active'])<span class="badge bg-success">Active</span>@else<span class="badge bg-danger">Inactive</span>@endif</td><td><a href="{{ route('admin.user-edit', ['role' => 'admin', 'id' => $admin['profile_id']]) }}" class="btn btn-sm btn-primary">Edit</a></td></tr>@endforeach</tbody></table></div></div>
        </div>
    </div>
</div>
@endsection
