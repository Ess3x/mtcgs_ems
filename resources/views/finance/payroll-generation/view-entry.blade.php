@extends('layouts.app')

@section('title', 'Payroll Entry Review')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('finance.payroll-generation.ready-dtrs') }}" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h2 class="mb-2">Payroll Entry Review & Adjustment</h2>
            <p class="text-muted">{{ $payrollEntry->employeeProfile->first_name }} {{ $payrollEntry->employeeProfile->last_name }}</p>
        </div>
    </div>

    <!-- Payroll Breakdown Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <p class="mb-1 small">Gross Pay</p>
                    <h4 class="mb-0">₱ {{ number_format($payrollEntry->gross_pay, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <p class="mb-1 small">Total Deductions</p>
                    <h4 class="mb-0">₱ {{ number_format($payrollEntry->total_deductions + $payrollEntry->getTotalAdditionalDeductions(), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <p class="mb-1 small">Net Pay</p>
                    <h4 class="mb-0">₱ {{ number_format($payrollEntry->getAdjustedNetPay(), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="mb-1 small">Status</p>
                    <span class="badge bg-{{ $payrollEntry->status === 'draft' ? 'warning' : 'success' }} p-2">
                        {{ ucfirst($payrollEntry->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Detailed Breakdown -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payroll Breakdown</h5>
                </div>
                <div class="card-body">
                    @if ($breakdown)
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>DTR Period</strong></td>
                                    <td class="text-end">{{ $breakdown['dtr_period'] ?? '--' }}</td>
                                </tr>
                                <tr>
                                    <td>Daily Rate</td>
                                    <td class="text-end">₱ {{ $breakdown['daily_rate'] ?? '--' }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td><strong>Days Worked</strong></td>
                                    <td class="text-end"><strong>{{ $breakdown['days_worked'] ?? 0 }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Basic Pay ({{ $breakdown['days_worked'] ?? 0 }} days)</td>
                                    <td class="text-end">₱ {{ $breakdown['basic_pay'] ?? '0.00' }}</td>
                                </tr>
                                <tr class="table-warning">
                                    <td><strong>Overtime Hours</strong></td>
                                    <td class="text-end"><strong>{{ $breakdown['overtime_hours'] ?? '0.00' }} hrs</strong></td>
                                </tr>
                                <tr>
                                    <td>Overtime Pay (25% premium)</td>
                                    <td class="text-end">₱ {{ $breakdown['overtime_pay'] ?? '0.00' }}</td>
                                </tr>

                                <tr class="table-danger">
                                    <td><strong>Deductions</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Late Minutes</td>
                                    <td class="text-end">-₱ {{ $breakdown['late_deduction'] ?? '0.00' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Absent Days ({{ $breakdown['days_absent'] ?? 0 }})</td>
                                    <td class="text-end">-₱ {{ $breakdown['absent_deduction'] ?? '0.00' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">Approved Leaves ({{ $breakdown['approved_leaves'] ?? 0 }})</td>
                                    <td class="text-end">+₱ {{ $breakdown['leave_deduction'] ?? '0.00' }}</td>
                                </tr>

                                <tr class="table-light">
                                    <td><strong>Gross Pay</strong></td>
                                    <td class="text-end"><strong>₱ {{ $breakdown['gross_pay'] ?? '0.00' }}</strong></td>
                                </tr>

                                <tr class="table-secondary">
                                    <td><strong>MANDATORY DEDUCTIONS</strong></td>
                                    <td class="text-muted small">(Based on salary)</td>
                                </tr>
                                <tr class="table-light">
                                    <td style="padding-left: 20px;">
                                        <i class="fas fa-landmark text-info me-1"></i>SSS Contribution
                                    </td>
                                    <td class="text-end">-₱ {{ $breakdown['sss_deduction'] ?? '0.00' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">
                                        <i class="fas fa-heart text-danger me-1"></i>PhilHealth
                                    </td>
                                    <td class="text-end">-₱ {{ $breakdown['philhealth_deduction'] ?? '0.00' }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td style="padding-left: 20px;">
                                        <i class="fas fa-home text-warning me-1"></i>Pag-IBIG
                                    </td>
                                    <td class="text-end">-₱ {{ $breakdown['pagibig_deduction'] ?? '0.00' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 20px;">
                                        <i class="fas fa-file-invoice-dollar text-success me-1"></i>Withholding Tax (BIR)
                                    </td>
                                    <td class="text-end">-₱ {{ $breakdown['withholding_tax'] ?? '0.00' }}</td>
                                </tr>

                                <tr class="table-danger">
                                    <td><strong>Total Mandatory Deductions</strong></td>
                                    <td class="text-end"><strong>-₱ {{ $breakdown['total_deductions'] ?? '0.00' }}</strong></td>
                                </tr>

                                <tr class="table-success" style="font-size: 16px;">
                                    <td><strong>NET PAY (Before Optional)</strong></td>
                                    <td class="text-end"><strong>₱ {{ $breakdown['net_pay'] ?? '0.00' }}</strong></td>
                                </tr>

                                @if($payrollEntry->getTotalAdditionalDeductions() > 0)
                                    <tr class="table-info">
                                        <td><strong>Additional/Optional Deductions</strong></td>
                                        <td class="text-end"><strong>-₱ {{ number_format($payrollEntry->getTotalAdditionalDeductions(), 2) }}</strong></td>
                                    </tr>
                                    <tr class="table-success" style="font-size: 16px; background-color: #d4edda !important;">
                                        <td><strong>FINAL NET PAY</strong></td>
                                        <td class="text-end"><strong>₱ {{ number_format($payrollEntry->getAdjustedNetPay(), 2) }}</strong></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-info">No breakdown data available</div>
                    @endif
                </div>
            </div>

            <!-- Additional Deductions Display -->
            @if($payrollEntry->getTotalAdditionalDeductions() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle text-info me-2"></i>Optional/Additional Deductions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payrollEntry->additionalDeductions as $deduction)
                                        <tr>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $deduction->deduction_type)) }}</span>
                                            </td>
                                            <td>{{ $deduction->description ?? '--' }}</td>
                                            <td class="text-end">-₱ {{ number_format($deduction->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-light fw-bold">
                                        <td colspan="2">Total Additional Deductions</td>
                                        <td class="text-end">-₱ {{ number_format($payrollEntry->getTotalAdditionalDeductions(), 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Adjustment Form (if draft) -->
            @if ($payrollEntry->status === 'draft')
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Adjust Deductions</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('finance.payroll-generation.update-entry', $payrollEntry->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <h6 class="mb-3">Adjust Deductions</h6>

                            <div class="mb-3">
                                <label for="sss_contribution" class="form-label">SSS</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="sss_contribution" name="sss_contribution" 
                                           step="0.01" value="{{ $payrollEntry->sss_contribution }}" readonly>
                                </div>
                                <small class="text-muted">Mandatory - Auto-calculated</small>
                            </div>

                            <div class="mb-3">
                                <label for="philhealth_contribution" class="form-label">PhilHealth</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="philhealth_contribution" name="philhealth_contribution" 
                                           step="0.01" value="{{ $payrollEntry->philhealth_contribution }}" readonly>
                                </div>
                                <small class="text-muted">Mandatory - Auto-calculated</small>
                            </div>

                            <div class="mb-3">
                                <label for="pagibig_contribution" class="form-label">Pag-IBIG</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="pagibig_contribution" name="pagibig_contribution" 
                                           step="0.01" value="{{ $payrollEntry->pagibig_contribution }}" readonly>
                                </div>
                                <small class="text-muted">Mandatory - Auto-calculated</small>
                            </div>

                            <div class="mb-3">
                                <label for="withholding_tax" class="form-label">Withholding Tax</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="withholding_tax" name="withholding_tax" 
                                           step="0.01" value="{{ $payrollEntry->withholding_tax }}" readonly>
                                </div>
                                <small class="text-muted">Mandatory - Auto-calculated</small>
                            </div>

                            <hr>

                            <h6 class="mb-3 text-info">
                                <i class="fas fa-plus-circle me-1"></i>Optional Deductions
                            </h6>

                            <div id="additionalDeductionsContainer">
                                @forelse($payrollEntry->additionalDeductions as $index => $deduction)
                                    <div class="input-group mb-2 additional-deduction-row" data-index="{{ $index }}">
                                        <select name="deduction_type[]" class="form-select form-select-sm" required>
                                            <option value="">Select Type</option>
                                            <option value="additional_pagibig" {{ $deduction->deduction_type == 'additional_pagibig' ? 'selected' : '' }}>Additional Pag-IBIG</option>
                                            <option value="loan" {{ $deduction->deduction_type == 'loan' ? 'selected' : '' }}>Loan Deduction</option>
                                            <option value="insurance" {{ $deduction->deduction_type == 'insurance' ? 'selected' : '' }}>Insurance</option>
                                            <option value="custom" {{ $deduction->deduction_type == 'custom' ? 'selected' : '' }}>Custom</option>
                                        </select>
                                        <input type="text" name="deduction_description[]" class="form-control form-control-sm" placeholder="Description" value="{{ $deduction->description }}" />
                                        <div class="input-group-text">₱</div>
                                        <input type="number" name="deduction_amount[]" class="form-control form-control-sm" placeholder="0.00" step="0.01" value="{{ $deduction->amount }}" />
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-deduction">Remove</button>
                                    </div>
                                @empty
                                    <!-- Empty state - will have template -->
                                @endforelse
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-info" id="addDeductionBtn">
                                    <i class="fas fa-plus me-1"></i>Add Optional Deduction
                                </button>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update & Recalculate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mt-3">
                    <div class="card-body">
                        <form action="{{ route('finance.payroll-generation.finalize-entry', $payrollEntry->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Finalize this payroll entry?')">
                                <i class="fas fa-check-circle"></i> Finalize Entry
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Entry is locked and cannot be modified.
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add new optional deduction row
    document.getElementById('addDeductionBtn').addEventListener('click', function() {
        const container = document.getElementById('additionalDeductionsContainer');
        const rowCount = container.querySelectorAll('.additional-deduction-row').length;
        
        const newRow = document.createElement('div');
        newRow.className = 'input-group mb-2 additional-deduction-row';
        newRow.innerHTML = `
            <select name="new_deduction_type[]" class="form-select form-select-sm" required>
                <option value="">Select Type</option>
                <option value="additional_pagibig">Additional Pag-IBIG</option>
                <option value="loan">Loan Deduction</option>
                <option value="insurance">Insurance</option>
                <option value="custom">Custom</option>
            </select>
            <input type="text" name="new_deduction_description[]" class="form-control form-control-sm" placeholder="Description" />
            <div class="input-group-text">₱</div>
            <input type="number" name="new_deduction_amount[]" class="form-control form-control-sm" placeholder="0.00" step="0.01" />
            <button type="button" class="btn btn-sm btn-outline-danger remove-deduction">Remove</button>
        `;
        
        container.appendChild(newRow);
        setupRemoveButtons();
    });
    
    function setupRemoveButtons() {
        document.querySelectorAll('.remove-deduction').forEach(btn => {
            btn.removeEventListener('click', removeDeductionRow);
            btn.addEventListener('click', removeDeductionRow);
        });
    }
    
    function removeDeductionRow(e) {
        e.preventDefault();
        e.target.closest('.additional-deduction-row').remove();
    }
    
    setupRemoveButtons();
});
</script>
@endsection
