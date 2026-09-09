@extends('layouts.app')

@section('title', 'Branch Admin Dashboard')

@section('content')
<div class="container-fluid dashboard-shell p-0">
    <!-- Header Hero -->
    <div class="card dashboard-hero mb-4" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%);">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold welcome-title">Welcome, Branch Administrator!</h2>
                    <p class="mt-2 mb-0 opacity-90">
                        Managing: <strong>{{ $branchName ?? 'N/A' }}</strong> Branch
                    </p>
                </div>
                <div class="text-end">
                    <div class="display-6 fw-bold mb-0">{{ now()->format('M d') }}</div>
                    <div class="opacity-90">{{ now()->format('l') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Total Employees</div>
                            <div class="fw-bold" style="font-size: 1.75rem;">{{ $totalEmployees ?? 0 }}</div>
                            <small class="text-muted">Active workforce</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(79,70,229,0.12); color: #4f46e5;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Present Today</div>
                            <div class="fw-bold text-success" style="font-size: 1.75rem;">{{ $presentToday ?? 0 }}</div>
                            <small class="text-muted">Across all roles</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(40,167,69,0.12); color: #28a745;">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Pending Leaves</div>
                            <div class="fw-bold text-warning" style="font-size: 1.75rem;">{{ $pendingLeaves ?? 0 }}</div>
                            <small class="text-muted">Awaiting review</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fas fa-clock text-primary"></i>
            <span>Recent Attendance ({{ $branchName ?? '' }} Branch)</span>
        </div>
        <div class="table-responsive" style="border: 0; border-radius: 0 0 14px 14px;">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>AM In</th>
                        <th>AM Out</th>
                        <th>PM In</th>
                        <th>PM Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAttendance ?? [] as $att)
                    <tr>
                        <td>{{ $att['date'] }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar bg-primary">{{ substr($att['employee'], 0, 1) }}</div>
                                <div>
                                    <div class="fw-semibold">{{ $att['employee'] }}</div>
                                    <small class="text-muted">{{ $att['employee_number'] }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $att['am_in'] }}</td>
                        <td>{{ $att['am_out'] }}</td>
                        <td>{{ $att['pm_in'] }}</td>
                        <td>{{ $att['pm_out'] }}</td>
                        <td>
                            <span class="badge bg-{{ $att['status'] == 'present' ? 'success' : ($att['status'] == 'late' ? 'warning text-dark' : 'secondary') }}">
                                {{ ucfirst($att['status']) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No records</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
