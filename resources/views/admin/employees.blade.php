@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Employee Management</h1>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.employees-archives') }}" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Archives
                    </a>
                    <a href="{{ route('admin.employee-create') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Employee
                    </a>
                </div>
            </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                @if(Auth::user()->admin_type === 'super_admin')
                    <div class="col-md-4">
                        <label for="employee-branch-filter" class="form-label">Filter by Branch</label>
                        <select id="employee-branch-filter" name="branch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $selectedBranch == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-4">
                        <div class="form-label">Branch</div>
                        <div class="form-control bg-light text-dark">
                            {{ Auth::user()->profile->branch->branch_name ?? 'N/A' }}
                        </div>
                    </div>
                @endif
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('admin.employees') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Employees</h6>
                    <h2 class="mb-0">{{ $totalEmployees ?? 0 }}</h2>
                    <small>Regular staff only</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Finance Officers</h6>
                    <h2 class="mb-0">{{ $totalFinance ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Administrators</h6>
                    <h2 class="mb-0">{{ $totalAdmins ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs employee-tabs mb-0">
        <li class="nav-item">
            <a href="{{ route('admin.employees') }}" class="nav-link {{ !($showFinance ?? false) ? 'active' : '' }}">
                Employee List
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.employees', ['finance' => 1]) }}" class="nav-link {{ $showFinance ?? false ? 'active' : '' }}">
                Finance Officer List
            </a>
        </li>
        @if(Auth::user()->isSuperAdmin())
            <li class="nav-item">
                <a href="{{ route('admin.branch-heads.index') }}" class="nav-link {{ request()->routeIs('admin.branch-heads.*') ? 'active' : '' }}">
                    Branch Admin List
                </a>
            </li>
        @endif
    </ul>

    <style>
        .employee-tabs {
            display: inline-flex;
            width: auto;
            background: transparent;
            border-bottom: 1px solid #dee2e6;
        }

        .employee-tabs .nav-item {
            margin: 0;
        }

        .employee-tabs .nav-link {
            border: 0;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            background: transparent;
            padding: 1rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .employee-tabs .nav-link:hover {
            color: #495057;
            border-bottom-color: #e9ecef;
        }

        .employee-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }

        /* Dark mode adjustments */
        :root[data-bs-theme="dark"] .employee-tabs {
            border-bottom-color: rgba(255, 255, 255, 0.18);
        }

        :root[data-bs-theme="dark"] .employee-tabs .nav-link {
            color: #a9d2ff;
        }

        :root[data-bs-theme="dark"] .employee-tabs .nav-link:hover {
            color: #ffffff;
            border-bottom-color: rgba(255, 255, 255, 0.2);
        }

        :root[data-bs-theme="dark"] .employee-tabs .nav-link.active {
            color: #7dd3fc;
            border-bottom-color: #7dd3fc;
        }
    </style>

    <div class="card">
        <div class="card-body p-0">
            <div class="px-4 pt-3 pb-2">
                @if(Auth::user()->isSuperAdmin())
                    <small class="text-muted">{{ ($showFinance ?? false) ? 'Showing finance officers' : 'Showing all staff' }}</small>
                @else
                    <small class="text-muted">{{ ($showFinance ?? false) ? 'Showing finance officers in your branch' : 'Showing regular employees only' }}</small>
                @endif
            </div>
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover table-sm" style="min-width: 1300px;">
                    <thead class="table-light">
                        <tr>
                            <th>{{ ($showFinance ?? false) ? 'Officer #' : 'Employee #' }}</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Branch</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Fingerprint</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                        <tr>
                            <td>{{ $emp->employee_number }}</td>
                            <td>
                                <strong>{{ $emp->first_name }} {{ $emp->last_name }}</strong><br>
                                <small class="text-muted">{{ $emp->user->email ?? 'N/A' }}</small>
                            </td>
                            <td>{{ $emp->position ?? 'N/A' }}</td>
                            <td><span class="badge bg-secondary">{{ data_get($emp, 'branch.branch_name') ?? data_get($emp, 'user.profile.branch.branch_name') ?? (($emp->role ?? '') === 'finance_head' ? 'No Branch Assigned' : 'N/A') }}</span></td>
                            <td>₱{{ number_format($emp->basic_salary ?? 0, 2) }}</td>
                            <td>
                                @if($emp->user && $emp->user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                                <small class="d-block text-muted">{{ $emp->status ?? 'New Hire' }}</small>
                            </td>
                            <td>
                                @php
                                    $hasFingerprint = false;

                                    if (is_object($emp) && method_exists($emp, 'hasRegisteredFingerprint')) {
                                        $hasFingerprint = (bool) $emp->hasRegisteredFingerprint();
                                    } else {
                                        $template = is_object($emp) ? trim((string) ($emp->fingerprint_template ?? '')) : '';
                                        $hasFingerprint = (bool) (($emp->is_fingerprint_registered ?? false) || (!empty($template) && strtolower($template) !== 'null'));
                                    }
                                @endphp
                                @if($hasFingerprint)
                                    <span class="badge bg-success">Registered</span>
                                @else
                                    <span class="badge bg-secondary">Not Registered</span>
                                @endif
                            </td>
                            <td>
                                @if(($emp->profile_type ?? null) === 'finance')
                                    <a href="{{ route('admin.user-edit', ['role' => 'finance', 'id' => $emp->id]) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                @elseif(($emp->profile_type ?? null) === 'admin')
                                    <a href="{{ route('admin.admin-edit', $emp->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @else
                                    <a href="{{ route('admin.employee-edit', $emp->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.employee-delete', $emp->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this employee?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4">No employees found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @if($employees instanceof \Illuminate\Contracts\Pagination\Paginator || $employees instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing <strong>{{ $employees->count() }}</strong> result{{ $employees->count() != 1 ? 's' : '' }} on page <strong>{{ $employees->currentPage() }}</strong>
                    </small>
                    <nav>
                        {{ $employees->links('pagination::bootstrap-4') }}
                    </nav>
                </div>
            </div>
        @endif
        </div>
    </div>
</div>
@endsection
