@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit text-warning"></i> Edit {{ ($employee->is_admin_profile ?? false) ? 'Admin Account' : 'Employee' }}
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ ($employee->is_admin_profile ?? false) ? route('admin.admin-update', $employee->id) : route('admin.employee-update', $employee->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first-name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="first-name" name="first_name" class="form-control" value="{{ $employee->first_name }}" autocomplete="given-name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last-name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="last-name" name="last_name" class="form-control" value="{{ $employee->last_name }}" autocomplete="family-name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" id="position" name="position" class="form-control" value="{{ $employee->position }}" autocomplete="organization-title">
                            </div>
                            <div class="col-md-6 mb-3" id="branch-field-wrapper">
                                @if(Auth::user()->admin_type === 'super_admin')
                                    <label for="branch-select" class="form-label">Branch</label>
                                    <select id="branch-select" name="branch_id" class="form-control">
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ $employee->branch_id == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->branch_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    @php $branch = $branches->first(); @endphp
                                    <span class="form-label d-block text-light">Branch</span>
                                    <input type="hidden" id="branch-hidden" name="branch_id" value="{{ $branch->id }}">
                                    <div class="form-control bg-light text-dark">{{ $branch->branch_name ?? 'N/A' }}</div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3" id="basic-salary-field-wrapper">
                                <label for="basic-salary" class="form-label">Basic Salary</label>
                                <input type="number" id="basic-salary" step="0.01" name="basic_salary" class="form-control" value="{{ $employee->basic_salary }}" autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                @if(($employee->is_admin_profile ?? false))
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" id="role" class="form-control" value="{{ ucfirst($employee->user->role ?? 'admin') }}" readonly>
                                    <input type="hidden" name="role" value="{{ $employee->user->role ?? 'admin' }}">
                                @else
                                    <label for="role" class="form-label">Role</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="employee" {{ $employee->user->role === 'employee' ? 'selected' : '' }}>
                                            Employee
                                        </option>
                                        <option value="finance_officer" {{ $employee->user->role === 'finance_officer' ? 'selected' : '' }}>
                                            Finance Officer
                                        </option>
                                        @if(Auth::user()->admin_type === 'super_admin' || blank(Auth::user()->admin_type))
                                            <option value="finance_head" {{ $employee->user->role === 'finance_head' ? 'selected' : '' }}>
                                                Finance Head
                                            </option>
                                        @endif
                                    </select>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date-hired" class="form-label">Date Hired <span class="text-danger">*</span></label>
                                <input type="date" id="date-hired" name="date_hired" class="form-control" value="{{ old('date_hired', optional($employee->date_hired)->format('Y-m-d')) }}" autocomplete="off" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-select" required>
                                    @php
                                        $visibleStatus = old('status', $employee->pending_status ?? $employee->status ?? 'New Hire');
                                    @endphp
                                    <option value="New Hire" {{ $visibleStatus === 'New Hire' ? 'selected' : '' }}>New Hire</option>
                                    <option value="Regular" {{ $visibleStatus === 'Regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="1-2 Years in Service" {{ $visibleStatus === '1-2 Years in Service' ? 'selected' : '' }}>1-2 Years in Service</option>
                                    <option value="3+ Years of Service" {{ $visibleStatus === '3+ Years of Service' ? 'selected' : '' }}>3+ Years of Service</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" id="is-active" name="is_active" class="form-check-input" autocomplete="off" {{ ($employee->user && $employee->user->is_active) ? 'checked' : '' }}>
                            <label for="is-active" class="form-check-label">Active</label>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Password cannot be changed here. Use "Change Password" feature in profile.
                        </div>
                        
                        <hr>
                        
                        <!-- Fingerprint Registration Section -->
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-fingerprint"></i> Fingerprint Registration
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Important:</strong> Fingerprint registration is required for attendance tracking. Please register the employee's fingerprint now.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-label">Fingerprint Status</div>
                                            <div class="d-flex align-items-center">
                                                <span id="fingerprint-status" class="d-none"></span>
                                                <button type="button" id="register-fingerprint-btn" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-fingerprint"></i> Register Fingerprint
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fingerprint-data" class="form-label">Fingerprint Data</label>
                                            <textarea name="fingerprint_data" id="fingerprint-data" class="form-control" rows="3" readonly placeholder="Fingerprint data will appear here after scanning..."></textarea>
                                            <small class="text-muted">This field will be populated automatically after fingerprint scanning.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden fingerprint scanner interface -->
                                <div id="fingerprint-scanner" class="d-none">
                                    <div class="border rounded p-3 bg-light">
                                        <h6>Fingerprint Scanner</h6>
                                        <div class="text-center mb-3">
                                            <div id="scanner-status" class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> Click "Start Scanning" to begin fingerprint registration
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="button" id="start-scan-btn" class="btn btn-success me-2">
                                                <i class="fas fa-play"></i> Start Scanning
                                            </button>
                                            <button type="button" id="stop-scan-btn" class="btn btn-danger" disabled>
                                                <i class="fas fa-stop"></i> Stop Scanning
                                            </button>
                                        </div>
                                        <div class="mt-3">
                                            <div class="progress d-none" id="scan-progress">
                                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.employees') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Employee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateFinanceHeadRoleFields() {
        const roleSelect = document.getElementById('role');
        const branchFieldWrapper = document.getElementById('branch-field-wrapper');
        const basicSalaryFieldWrapper = document.getElementById('basic-salary-field-wrapper');
        const branchSelect = document.getElementById('branch-select');
        const isFinanceHead = roleSelect && roleSelect.value === 'finance_head';

        if (branchFieldWrapper) {
            branchFieldWrapper.style.display = isFinanceHead ? 'none' : '';
        }

        if (basicSalaryFieldWrapper) {
            basicSalaryFieldWrapper.style.display = isFinanceHead ? 'none' : '';
        }

        if (branchSelect) {
            branchSelect.required = !isFinanceHead;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        if (roleSelect) {
            roleSelect.addEventListener('change', updateFinanceHeadRoleFields);
        }
        updateFinanceHeadRoleFields();
    });

    // Fingerprint Registration Variables
    let isScanning = false;
    let scanInterval;
    let fingerprintCheckInterval = null;

    // DOM Elements
    const registerFingerprintBtn = document.getElementById('register-fingerprint-btn');
    const fingerprintScanner = document.getElementById('fingerprint-scanner');
    const fingerprintData = document.getElementById('fingerprint-data');
    const fingerprintStatus = document.getElementById('fingerprint-status');
    const startScanBtn = document.getElementById('start-scan-btn');
    const stopScanBtn = document.getElementById('stop-scan-btn');
    const scannerStatus = document.getElementById('scanner-status');
    const scanProgress = document.getElementById('scan-progress');
    const progressBar = scanProgress.querySelector('.progress-bar');

    // Launch the desktop enrollment app using a custom URI scheme.
    function launchEnrollmentApp() {
        fingerprintData.value = '';
        fingerprintStatus.classList.add('d-none');

        const firstName = document.querySelector('input[name="first_name"]').value.trim();
        const lastName = document.querySelector('input[name="last_name"]').value.trim();
        const employeeNumber = '{{ $employee->employee_number }}'; // Get from existing employee
        const email = '{{ $employee->user->email ?? "" }}';
        const role = '{{ $employee->user->role ?? "" }}';
        const position = document.querySelector('input[name="position"]').value.trim();
        
        const branchSelect = document.querySelector('select[name="branch_id"]');
        const fallbackBranchName = '{{ $employee->branch->branch_name ?? ($branches->first()->branch_name ?? '') }}';
        const branchName = branchSelect
            ? (branchSelect.options[branchSelect.selectedIndex]?.text || fallbackBranchName)
            : fallbackBranchName;

        if (!firstName || !lastName) {
            alert('Please fill in First Name and Last Name before launching fingerprint enrollment.');
            return;
        }

        const uri = 'mtcgs-enroll:/register' +
            '?first_name=' + encodeURIComponent(firstName) +
            '&last_name=' + encodeURIComponent(lastName) +
            '&employee_number=' + encodeURIComponent(employeeNumber) +
            '&email=' + encodeURIComponent(email) +
            '&role=' + encodeURIComponent(role) +
            '&branch=' + encodeURIComponent(branchName) +
            '&position=' + encodeURIComponent(position);

        scannerStatus.className = 'alert alert-info';
        scannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Launching fingerprint enrollment app...';
        fingerprintScanner.classList.remove('d-none');
        startFingerprintPolling(employeeNumber);

        try {
            window.location.href = uri;
            fingerprintStatus.className = 'badge bg-warning';
            fingerprintStatus.innerHTML = '<i class="fas fa-external-link-alt"></i> App Launched';
            registerFingerprintBtn.disabled = false;
            registerFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
            registerFingerprintBtn.classList.remove('btn-success');
            registerFingerprintBtn.classList.add('btn-outline-primary');
            scannerStatus.className = 'alert alert-info';
            scannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for fingerprint capture from desktop app...';
        } catch (error) {
            scannerStatus.className = 'alert alert-danger';
            scannerStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Could not launch the enrollment app. Please make sure the custom URI handler is installed.';
        }
    }

    registerFingerprintBtn.addEventListener('click', function() {
        launchEnrollmentApp();
    });

    startScanBtn.addEventListener('click', function() {
        launchEnrollmentApp();
    });

    stopScanBtn.addEventListener('click', function() {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
            fingerprintCheckInterval = null;
        }
        scannerStatus.className = 'alert alert-secondary';
        scannerStatus.innerHTML = '<i class="fas fa-stop"></i> Enrollment app launch cancelled.';
    });

    // Poll only after the desktop enrollment app has been launched.
    function startFingerprintPolling(employeeNumber) {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
        }

        const employeeNumberForPolling = String(employeeNumber || '').trim();

        fingerprintCheckInterval = setInterval(async function() {
        try {
            let url = '/api/fingerprint-temp?employee_number=' + encodeURIComponent(employeeNumberForPolling);

            const response = await fetch(url);
            if (!response.ok) {
                return;
            }

            const result = await response.json();
            console.log('Fingerprint poll result:', result);

            // Only update UI if we have actual fingerprint data
            if (result.success && result.data && result.data.fingerprint_data) {
                const incomingData = result.data.fingerprint_data;
                const incomingEmpNumber = String(result.data.employee_number || '').trim();
                
                // Validate that fingerprint data is substantial
                if (!incomingData || incomingData.trim().length < 100) {
                    console.log('⏳ Fingerprint data incomplete, waiting for full capture...');
                    return;
                }

                // Show the status badge
                fingerprintStatus.classList.remove('d-none');

                if (incomingEmpNumber.toLowerCase() === employeeNumber.toLowerCase()) {
                    // Perfect match
                    clearInterval(fingerprintCheckInterval);
                    fingerprintCheckInterval = null;
                    fingerprintData.value = incomingData;
                    console.log('✓ Fingerprint data matched! Displaying in textarea.');
                    
                    fingerprintStatus.className = 'badge bg-success me-2';
                    fingerprintStatus.innerHTML = '<i class="fas fa-check-circle"></i> Registered ✓';
                    registerFingerprintBtn.disabled = false;
                    registerFingerprintBtn.innerHTML = '<i class="fas fa-redo"></i> Register Again';
                    registerFingerprintBtn.classList.remove('btn-success');
                    registerFingerprintBtn.classList.add('btn-outline-warning');
                    scannerStatus.className = 'alert alert-success';
                    scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Fingerprint matched! Click "Register Again" to rescan if needed.';
                } else {
                    // Mismatch
                    fingerprintStatus.className = 'badge bg-warning me-2';
                    fingerprintStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Employee ID Mismatch';
                    scannerStatus.className = 'alert alert-warning';
                    scannerStatus.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>Fingerprint is for ${incomingEmpNumber}</strong> but this employee is <strong>${employeeNumberForPolling}</strong>.`;
                    registerFingerprintBtn.disabled = false;
                    registerFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Scan Again';
                }
            }
        } catch (error) {
            console.log('Polling for fingerprint data...', error);
        }
        }, 300);
    }

    // Resume polling when the desktop app closes and the browser becomes active again.
    function resumeFingerprintPolling() {
        if (!fingerprintData.value.trim() && !fingerprintCheckInterval && !document.hidden) {
            startFingerprintPolling(String('{{ $employee->employee_number }}').trim());
        }
    }

    window.addEventListener('focus', resumeFingerprintPolling);
    document.addEventListener('visibilitychange', resumeFingerprintPolling);

    // Stop polling when leaving the page
    window.addEventListener('beforeunload', function() {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
            fingerprintCheckInterval = null;
        }
    });

    // Clear interval when form is submitted
    document.querySelector('form').addEventListener('submit', function() {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
            fingerprintCheckInterval = null;
        }
    });
</script>
@endsection
