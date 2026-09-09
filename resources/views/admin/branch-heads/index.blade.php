@extends('layouts.app')

@section('title', 'Branch Admin Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Branch Admin Management</h3>
            <p class="text-muted mb-0">Manage branch-specific admin accounts and access control.</p>
        </div>
        <a href="{{ route('admin.branch-heads.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create Branch Admin
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Branch Admins</div>
                    <div class="fs-3 fw-bold">{{ $totalBranchHeads }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Branch Admin List</h5>
        </div>
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover table-sm" style="min-width: 900px;">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Employee #</th>
                        <th>Branch</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branchHeads as $head)
                    <tr>
                        <td>
                            <strong>{{ $head->first_name }} {{ $head->last_name }}</strong><br>
                            <small class="text-muted">{{ $head->position }}</small>
                        </td>
                        <td>{{ $head->user->email }}</td>
                        <td>{{ $head->employee_number }}</td>
                        <td><span class="badge bg-secondary">{{ $head->branch->branch_name ?? 'N/A' }}</span></td>
                        <td>{{ $head->contact_number ?? 'N/A' }}</td>
                        <td>
                            @if($head->user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.branch-heads.edit', $head->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('admin.branch-heads.destroy', $head->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Delete this branch head account?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4">No branch admins found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($branchHeads instanceof \Illuminate\Contracts\Pagination\Paginator || $branchHeads instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing <strong>{{ $branchHeads->count() }}</strong> result{{ $branchHeads->count() != 1 ? 's' : '' }}
                    </small>
                    <nav>
                        {{ $branchHeads->links('pagination::bootstrap-4') }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
