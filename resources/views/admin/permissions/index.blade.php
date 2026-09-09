@extends('layouts.app')

@section('title', 'Manage Admin Permissions')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h2><i class="fas fa-lock-open me-2"></i> Manage Admin Permissions</h2>
                    <p class="mb-0">Grant or revoke ID verification authority to administrators</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Administrators
                    </h5>
                    <span class="badge bg-info">{{ $admins->count() }} Admin(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Admin Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Branch</th>
                                <th>Verify IDs</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($admins as $admin)
                                <tr>
                                    <td>
                                        <strong>{{ $admin->first_name }} {{ $admin->last_name }}</strong>
                                        @if($admin->user->admin_type === 'super_admin')
                                            <span class="badge bg-danger ms-2">Super Admin</span>
                                        @endif
                                    </td>
                                    <td>{{ $admin->user->email }}</td>
                                    <td>{{ $admin->position ?? 'N/A' }}</td>
                                    <td>{{ optional($admin->branch)->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($admin->can_verify_ids)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Authorized
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times"></i> Not Authorized
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($admin->user->admin_type !== 'super_admin')
                                            @if($admin->can_verify_ids)
                                                <form action="{{ route('admin.permissions.revoke', $admin->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to revoke ID verification permission?')">
                                                        <i class="fas fa-ban"></i> Revoke
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.permissions.grant', $admin->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to grant ID verification permission?')">
                                                        <i class="fas fa-check"></i> Grant
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted small">Super Admin</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No administrators found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
