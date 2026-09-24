@extends('layouts.app')

@section('title', 'Cash Advance')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Cash Advance</h1>
            <p class="text-muted mb-0">Employee Apply → FO Review → BH Review → HR Approval → FH Final Approval</p>
        </div>
    </div>

    @if($profile)
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Apply for Cash Advance</h5></div>
            <div class="card-body">
                <p class="text-muted">Active employees may apply up to <strong>₱1,000</strong>. New hires are marked for additional BH and FH review.</p>
                <form method="POST" action="{{ route('cash-advances.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Requested Amount</label>
                        <input type="number" name="requested_amount" min="1" max="{{ $availableCashAdvanceBalance }}" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Available Remaining Limit</label>
                        <input type="text" class="form-control" value="₱{{ number_format($availableCashAdvanceBalance, 2) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Total Active Cash Advances</label>
                        <input type="text" class="form-control" value="₱{{ number_format($totalCashAdvances, 2) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" maxlength="2000" class="form-control" required>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Submit Application</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Cash Advance Applications</h5></div>
        <div class="table-responsive">
            @php
                $runningCashAdvanceBalance = 1000;
                $cashAdvanceRowBalances = [];
                foreach ($applications->getCollection()->sortBy('created_at') as $listedApplication) {
                    if (in_array($listedApplication->status, ['approved', 'deducting'], true)) {
                        $listedAmount = (float) ($listedApplication->approved_amount ?: $listedApplication->requested_amount);
                        $runningCashAdvanceBalance = max(0, $runningCashAdvanceBalance - $listedAmount);
                        $cashAdvanceRowBalances[$listedApplication->id] = $runningCashAdvanceBalance;
                    } elseif (in_array($listedApplication->status, ['pending_fo', 'pending_bh', 'pending_hr', 'pending_fh'], true)) {
                        $cashAdvanceRowBalances[$listedApplication->id] = $cashAdvanceAvailableByEmployee[$listedApplication->employee_profile_id] ?? 1000;
                    } else {
                        $cashAdvanceRowBalances[$listedApplication->id] = 0;
                    }
                }
            @endphp
            <table class="table table-hover mb-0">
                <thead><tr><th>Employee</th><th>Amount</th><th>Remaining After This Advance</th><th>Purpose</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>{{ $application->employeeProfile->first_name }} {{ $application->employeeProfile->last_name }}<br><small class="text-muted">{{ $application->employeeProfile->employee_number }}</small></td>
                        <td>₱{{ number_format($application->approved_amount ?: $application->requested_amount, 2) }}</td>
                        @php
                            $cashAdvanceAmount = (float) ($application->approved_amount ?: $application->requested_amount);
                            $cashAdvanceRemaining = $cashAdvanceRowBalances[$application->id] ?? 0;
                        @endphp
                        <td>₱{{ number_format($cashAdvanceRemaining, 2) }}</td>
                        <td>{{ $application->purpose }}</td>
                        <td><span class="badge bg-{{ in_array($application->status, ['approved'], true) ? 'success' : (str_contains($application->status, 'pending') ? 'warning text-dark' : 'secondary') }}">{{ str_replace('_', ' ', ucfirst($application->status)) }}</span></td>
                        <td>
                            @php
                                $reviewRoute = match($application->status) {
                                    'pending_fo' => auth()->user()->role === 'finance_officer' ? route('cash-advances.fo-review', $application) : null,
                                    'pending_bh' => auth()->user()->isBranchAdmin() ? route('cash-advances.bh-review', $application) : null,
                                    'pending_hr' => auth()->user()->isSuperAdmin() ? route('cash-advances.hr-review', $application) : null,
                                    'pending_fh' => auth()->user()->isFinanceHead() ? route('cash-advances.fh-review', $application) : null,
                                    default => null,
                                };
                            @endphp
                            @if($reviewRoute)
                                <form method="POST" action="{{ $reviewRoute }}" class="d-flex gap-1 flex-wrap">
                                    @csrf
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason / note">
                                    <button name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                                    <button name="decision" value="reject" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            @else
                                <span class="text-muted small">{{ $application->rejection_reason ?: 'Waiting for next approver' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No cash advance applications for this account or review stage.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $applications->links() }}</div>
    </div>
</div>
@endsection
