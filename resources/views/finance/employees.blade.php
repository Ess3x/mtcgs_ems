@extends('layouts.app')

@section('title', 'Branch Employees')

@section('content')
<style>
    body.dark-mode .finance-employees-page .card.bg-info {
        background-color: #9132a8 !important;
        color: #fff !important;
    }

    body.dark-mode .finance-employees-page .card-header.bg-white,
    body.dark-mode .finance-employees-page .table thead.table-light th,
    body.dark-mode .finance-employees-page .table tbody tr.table-light > th {
        background-color: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }

    body.dark-mode .finance-employees-page .table tbody tr.table-secondary > th {
        background-color: #334155 !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }

    body.dark-mode .finance-employees-page .table tbody tr:not(.table-light):not(.table-secondary),
    body.dark-mode .finance-employees-page .table tbody tr:not(.table-light):not(.table-secondary) td {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border-color: #334155 !important;
    }

    body.dark-mode .finance-employees-page .table.table-hover tbody tr:not(.table-light):not(.table-secondary):hover,
    body.dark-mode .finance-employees-page .table.table-hover tbody tr:not(.table-light):not(.table-secondary):hover td {
        background-color: #273449 !important;
    }

    body.dark-mode .finance-employees-page a,
    body.dark-mode .finance-employees-page .text-primary {
        color: #FFD166 !important;
    }
</style>

<div class="container-fluid finance-employees-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h2><i class="fas fa-users me-2"></i> Branch Employees</h2>
                    <p class="mb-0">
                        Managing employees for <strong>{{ $branchName }}</strong> branch
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
                <h5 class="mb-0">All Users</h5>
        </div>
        @php
            $employeeGroups = [[
                'name' => $branchName,
                'users' => $users->values(),
            ]];
        @endphp
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Employee #</th>
                        <th>Profile</th>
                        <th>Role</th>
                        <th>Position</th>
                        <th>Fingerprint Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeeGroups as $group)
                        @if($group['users']->isNotEmpty())
                            <tr class="table-light">
                                <th colspan="5" class="text-primary">
                                    <i class="fas fa-building me-2"></i>{{ $group['name'] }}
                                    <span class="badge bg-primary ms-2">{{ $group['users']->count() }}</span>
                                </th>
                            </tr>
                            @php
                                $roleGroups = collect([
                                    'Branch Admin' => $group['users']->filter(fn ($user) => $user->role === 'admin' && $user->admin_type === 'branch_admin'),
                                    'Finance Officer' => $group['users']->filter(fn ($user) => in_array($user->role, ['finance_officer', 'finance_head'], true)),
                                    'Employees' => $group['users']->filter(fn ($user) => !in_array($user->role, ['admin', 'finance_officer', 'finance_head'], true)),
                                ])->filter(fn ($roleUsers) => $roleUsers->isNotEmpty());
                            @endphp
                            @foreach($roleGroups as $roleName => $roleUsers)
                                <tr class="table-secondary">
                                    <th colspan="5" class="text-dark ps-4">
                                        <i class="fas fa-users me-2"></i>{{ $roleName }}
                                        <span class="badge bg-secondary ms-2">{{ $roleUsers->count() }}</span>
                                    </th>
                                </tr>
                                @foreach($roleUsers as $user)
                                    @php
                                        $profile = $user->profile;
                                        $profileType = $profile ? strtolower(class_basename($profile)) : null;
                                        $profileName = $profile ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) : $user->name;
                                        $profileInitials = collect(explode(' ', $profileName))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                                        $profilePhoto = $profile?->profile_photo;
                                        $hasProfilePhoto = $profilePhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($profilePhoto);
                                        $profilePhotoUrl = $hasProfilePhoto ? asset('storage/' . ltrim($profilePhoto, '/')) : null;
                                        $canOpenProfile = (bool) $profile;
                                    @endphp
                                    <tr>
                                        <td>{{ $profile?->employee_number ?? 'USER-' . $user->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($profilePhotoUrl)
                                                    <img src="{{ $profilePhotoUrl . '?v=' . $profile->updated_at?->timestamp }}" alt="{{ $profileName }}" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: .85rem;">{{ $profileInitials ?: '?' }}</div>
                                                @endif
                                                @if($canOpenProfile)
                                                    <a href="{{ route('finance.employee.profile', [strtolower(class_basename($profile)), $profile->id]) }}" class="fw-semibold text-decoration-none">{{ $profileName }}</a>
                                                @else
                                                    <span class="fw-semibold">{{ $profileName }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ str_replace('_', ' ', ucfirst($user->role ?? 'user')) }}</td>
                                        <td>{{ $profile?->position ?? 'N/A' }}</td>
                                        <td>
                                            @if($profile?->is_fingerprint_registered)
                                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Registered</span>
                                            @else
                                                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not Registered</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    @endforeach
                    @if($users->isEmpty())
                        <tr><td colspan="5" class="text-center">No active users found in {{ $branchName }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
