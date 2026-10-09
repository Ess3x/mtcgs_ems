@extends('layouts.app')

@section('title', 'Cash Charges Management')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Cash Charges Management</h2>
                <p class="text-muted mb-0">Set and update cash charge balances for active users.</p>
            </div>
        </div>

        @php
            $isMyChargesScope = request('scope') === 'my' || Auth::user()->role === 'employee';
            $isEmployeeRole = Auth::user()->role === 'employee';
            $isBranchAdminRole = Auth::user()->role === 'admin' && (Auth::user()->admin_type ?? '') === 'branch_admin';
            $canAddCharges = Auth::user()->role === 'admin' && Auth::user()->admin_type === 'super_admin'
                || Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin'
                || Auth::user()->role === 'branch_head'
                || Auth::user()->role === 'finance_head';
            $showManagementSections = !($isEmployeeRole);
            $showMyCharges = in_array(Auth::user()->role, ['employee', 'finance_officer', 'finance_head', 'branch_head'], true)
                || ($isBranchAdminRole && $isMyChargesScope);
        @endphp

        @if($showMyCharges)
            @php
                $profileForSummary = Auth::user()->getEmployeeProfile() ?? Auth::user()->getAdminProfile()?->employeeProfile ?? null;
                $balanceForSummary = $profileForSummary
                    ? \App\Models\LeaveBalance::firstOrCreate([
                        'employee_profile_id' => $profileForSummary->id,
                        'year' => now()->year,
                    ], [
                        'sick_leave_used' => 0,
                        'vacation_leave_used' => 0,
                        'emergency_leave_used' => 0,
                        'birthday_leave_used' => 0,
                        'cash_charge_used' => 0,
                        'maternity_leave_used' => 0,
                        'paternity_leave_used' => 0,
                    ])
                    : null;
                $pendingChargeRequest = $myCharges
                    ->whereIn('status', ['pending_branch_admin', 'pending_super_admin'])
                    ->sort(function ($left, $right) {
                        $leftCreated = $left->created_at?->timestamp ?? 0;
                        $rightCreated = $right->created_at?->timestamp ?? 0;

                        if ($leftCreated === $rightCreated) {
                            return ($right->id ?? 0) <=> ($left->id ?? 0);
                        }

                        return $rightCreated <=> $leftCreated;
                    })
                    ->first();
                $latestApprovedCharge = $myCharges
                    ->filter(function ($charge) {
                        return $charge->status === 'approved';
                    })
                    ->sort(function ($left, $right) {
                        $leftApproved = $left->approved_at?->timestamp ?? $left->created_at?->timestamp ?? 0;
                        $rightApproved = $right->approved_at?->timestamp ?? $right->created_at?->timestamp ?? 0;

                        if ($leftApproved === $rightApproved) {
                            return ($right->id ?? 0) <=> ($left->id ?? 0);
                        }

                        return $rightApproved <=> $leftApproved;
                    })
                    ->first();
                $baseChargeBalance = (float) ($balanceForSummary->cash_charge_total ?? 0);
                $branchAdminMyCharges = $myCharges
                    ->filter(fn ($charge) => in_array($charge->status, ['pending', 'pending_branch_admin', 'pending_super_admin', 'rejected'], true))
                    ->values();
                $approvedMyCharges = $myCharges
                    ->filter(function ($charge) {
                        return $charge->status === 'approved';
                    })
                    ->sort(function ($left, $right) {
                        $leftApproved = $left->approved_at?->timestamp ?? $left->created_at?->timestamp ?? 0;
                        $rightApproved = $right->approved_at?->timestamp ?? $right->created_at?->timestamp ?? 0;

                        return $rightApproved <=> $leftApproved ?: (($right->id ?? 0) <=> ($left->id ?? 0));
                    })
                    ->values();
                $totalChargeBalance = $latestApprovedCharge
                    ? (float) $latestApprovedCharge->amount
                    : ($baseChargeBalance > 0 ? $baseChargeBalance : ($pendingChargeRequest ? (float) $pendingChargeRequest->amount : 0));
                $installmentPerCutoff = $latestApprovedCharge && $latestApprovedCharge->installment_per_cutoff !== null
                    ? (float) $latestApprovedCharge->installment_per_cutoff
                    : ($baseChargeBalance > 0 ? $baseChargeBalance / 2 : ($pendingChargeRequest && $pendingChargeRequest->installment_per_cutoff !== null ? (float) $pendingChargeRequest->installment_per_cutoff : 0));
                $archivedMyCharges = $archivedMyCharges ?? collect();
            @endphp

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <strong>My Charges</strong>
                    <span class="badge bg-primary-subtle text-primary">{{ $myCharges->count() }} record(s)</span>
                </div>
                <div class="card-body pb-2">
                    <form method="POST" action="{{ $profileForSummary ? route('admin.cash-charges.request-update', $profileForSummary) : '#' }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-1">Total Charge Balance (Amount)</label>
                            <input type="number" step="0.01" min="0" max="50000" id="my-charge-total" name="cash_charge_total" class="form-control form-control-lg" value="{{ number_format($totalChargeBalance, 2, '.', '') }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Installment / Cut-off</label>
                            <input type="number" step="0.01" min="0" max="50000" id="my-charge-installment" name="installment_per_cutoff" class="form-control form-control-lg" value="{{ number_format($installmentPerCutoff, 2, '.', '') }}">
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            @if($profileForSummary)
                                <button type="submit" class="btn btn-primary w-100">Save</button>
                            @endif
                        </div>
                    </form>
                </div>
                @if($branchAdminMyCharges->isNotEmpty())
                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3">Applied by Branch Admin</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Installment Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($branchAdminMyCharges as $charge)
                                        @php
                                            $displayStatus = match ($charge->status) {
                                                'pending_branch_admin' => 'Pending Branch Admin',
                                                'pending_super_admin' => 'Pending Super Admin',
                                                'rejected' => 'Rejected',
                                                default => ucfirst(str_replace('_', ' ', $charge->status ?? 'pending')),
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $charge->employeeProfile?->first_name }} {{ $charge->employeeProfile?->last_name }}</div>
                                                <small class="text-muted">{{ $charge->employeeProfile?->employee_number }}</small>
                                            </td>
                                            <td>{{ $charge->employeeProfile?->branch?->branch_name ?? 'N/A' }}</td>
                                            <td>₱{{ number_format((float) $charge->amount, 2) }}</td>
                                            <td>₱{{ number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2) }}</td>
                                            <td>{{ $charge->reason }}</td>
                                            <td>
                                                <span class="badge bg-{{ $charge->status === 'approved' ? 'success' : ($charge->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                                    {{ $displayStatus }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($approvedMyCharges->isNotEmpty())
                    <div class="border-top pt-3 mt-3">
                        <h5 class="mb-3">Approved by Super Admin</h5>
                        <h6 class="text-muted mb-2">Installment Details</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Installment Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvedMyCharges as $charge)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $charge->employeeProfile?->first_name }} {{ $charge->employeeProfile?->last_name }}</div>
                                                <small class="text-muted">{{ $charge->employeeProfile?->employee_number }}</small>
                                            </td>
                                            <td>{{ $charge->employeeProfile?->branch?->branch_name ?? 'N/A' }}</td>
                                            <td>₱{{ number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2) }}</td>
                                            <td>{{ $charge->reason }}</td>
                                            <td>
                                                <span class="badge bg-success">Approved</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <h6 class="text-muted mb-2 mt-4">Amount Details</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvedMyCharges as $charge)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $charge->employeeProfile?->first_name }} {{ $charge->employeeProfile?->last_name }}</div>
                                                <small class="text-muted">{{ $charge->employeeProfile?->employee_number }}</small>
                                            </td>
                                            <td>{{ $charge->employeeProfile?->branch?->branch_name ?? 'N/A' }}</td>
                                            <td>₱{{ number_format((float) $charge->amount, 2) }}</td>
                                            <td>{{ $charge->reason }}</td>
                                            <td><span class="badge bg-success">Approved</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(Auth::user()->role === 'admin' && (Auth::user()->admin_type ?? '') === 'super_admin' && $archivedMyCharges->isNotEmpty())
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Archives</h5>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#my-charges-archives">
                                View Archives
                            </button>
                        </div>
                        <div class="collapse" id="my-charges-archives">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Branch</th>
                                            <th>Amount</th>
                                            <th>Installment Amount</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($archivedMyCharges as $charge)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $charge->employeeProfile?->first_name }} {{ $charge->employeeProfile?->last_name }}</div>
                                                    <small class="text-muted">{{ $charge->employeeProfile?->employee_number }}</small>
                                                </td>
                                                <td>{{ $charge->employeeProfile?->branch?->branch_name ?? 'N/A' }}</td>
                                                <td>₱{{ number_format((float) $charge->amount, 2) }}</td>
                                                <td>₱{{ number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2) }}</td>
                                                <td>{{ $charge->reason }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $charge->status === 'approved' ? 'success' : 'danger' }}">
                                                        {{ $charge->status === 'approved' ? 'Approved' : 'Rejected' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('admin.cash-charges.unarchive', $charge) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-success btn-sm">Un-Archived</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if($branchAdminMyCharges->isEmpty() && $approvedMyCharges->isEmpty() && $archivedMyCharges->isEmpty())
                    <div class="text-center text-muted py-4">No charges submitted under your account yet.</div>
                @endif
            </div>
        @endif

        @if($canAddCharges && $showManagementSections && !$isMyChargesScope)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <strong>Add Charges</strong>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#cash-charge-form-wrapper">
                        <i class="fas fa-plus me-1"></i> Add Charges
                    </button>
                </div>
                <div class="collapse" id="cash-charge-form-wrapper">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.cash-charges.store') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Select employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} - {{ $employee->branch?->branch_name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="50000" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" maxlength="1000" placeholder="Enter reason" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Submit Charge Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($showManagementSections && !$isMyChargesScope)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <strong>Cash Charge Requests</strong>
                    <span class="badge bg-primary-subtle text-primary">{{ $cashCharges->count() }} requests</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Amount</th>
                                <th>Installment Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                @if(Auth::user()->role === 'admin' && in_array(Auth::user()->admin_type ?? '', ['branch_admin', 'super_admin'], true))
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashCharges as $charge)
                                @php
                                    $displayStatus = match ($charge->status) {
                                        'pending' => 'Pending Branch Admin',
                                        'pending_branch_admin' => 'Pending Branch Admin',
                                        'pending_super_admin' => 'Pending Super Admin',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        default => ucfirst(str_replace('_', ' ', $charge->status ?? 'pending')),
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $charge->employeeProfile?->first_name }} {{ $charge->employeeProfile?->last_name }}</div>
                                        <small class="text-muted">{{ $charge->employeeProfile?->employee_number }}</small>
                                    </td>
                                    <td>{{ $charge->employeeProfile?->branch?->branch_name ?? 'N/A' }}</td>
                                    <td>₱{{ number_format((float) $charge->amount, 2) }}</td>
                                    <td>₱{{ number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2) }}</td>
                                    <td>{{ $charge->reason }}</td>
                                    <td>
                                        <span class="badge bg-{{ $charge->status === 'approved' ? 'success' : ($charge->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                            {{ $displayStatus }}
                                        </span>
                                    </td>
                                    @if(Auth::user()->role === 'admin' && in_array(Auth::user()->admin_type ?? '', ['branch_admin', 'super_admin'], true))
                                        <td>
                                            @php
                                                $isReviewableByCurrentAdmin = match (Auth::user()->admin_type ?? '') {
                                                    'branch_admin' => in_array($charge->status, ['pending', 'pending_branch_admin'], true),
                                                    'super_admin' => $charge->status === 'pending_super_admin',
                                                    default => false,
                                                };
                                            @endphp
                                            @if($charge->status === 'archived')
                                                <span class="text-muted small">Archived</span>
                                            @elseif($isReviewableByCurrentAdmin)
                                                <div class="d-flex gap-2 align-items-center">
                                                    <form method="POST" action="{{ route('admin.cash-charges.approve', $charge) }}" class="d-flex gap-2 align-items-center">
                                                        @csrf
                                                        <input type="hidden" name="decision" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" name="decision" value="reject">Reject</button>
                                                    </form>
                                                </div>
                                            @else
                                                <div class="d-flex flex-column gap-2 align-items-start">
                                                    <span class="text-muted small">{{ $charge->approver ? 'Reviewed by ' . $charge->approver->name : 'Processed' }}</span>
                                                    @if(Auth::user()->admin_type === 'super_admin' && $charge->status !== 'archived')
                                                        <form method="POST" action="{{ route('admin.cash-charges.archive', $charge) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm">Delete</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ (Auth::user()->role === 'admin' && in_array(Auth::user()->admin_type ?? '', ['branch_admin', 'super_admin'], true)) ? 7 : 6 }}" class="text-center text-muted py-4">No cash charge requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(Auth::user()->role === 'admin' && (Auth::user()->admin_type ?? '') === 'super_admin')
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <strong>Archives</strong>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#cash-charge-archives-list">
                            View Archives
                        </button>
                    </div>
                    <div class="collapse" id="cash-charge-archives-list">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Installment Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($archivedCashCharges as $charge)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $charge->employeeProfile?->first_name }} {{ $charge->employeeProfile?->last_name }}</div>
                                                <small class="text-muted">{{ $charge->employeeProfile?->employee_number }}</small>
                                            </td>
                                            <td>{{ $charge->employeeProfile?->branch?->branch_name ?? 'N/A' }}</td>
                                            <td>₱{{ number_format((float) $charge->amount, 2) }}</td>
                                            <td>₱{{ number_format((float) ($charge->installment_per_cutoff ?? ((float) $charge->amount / 2)), 2) }}</td>
                                            <td>{{ $charge->reason }}</td>
                                            <td>
                                                <span class="badge bg-{{ $charge->status === 'approved' ? 'success' : 'danger' }}">
                                                    {{ $charge->status === 'approved' ? 'Approved' : 'Rejected' }}
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.cash-charges.unarchive', $charge) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success btn-sm">Un-Archived</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No archived cash charge requests found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif

    </div>

@endsection
