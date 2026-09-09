@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus text-primary"></i> Add New Employee
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.employee-store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" name="employee_number" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" name="position" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3" id="branch-field-wrapper">
                                <label class="form-label">Branch</label>
                                @if(Auth::user()->admin_type === 'super_admin')
                                    <select name="branch_id" class="form-control" id="branch-select">
                                        <option value="">-- Select Branch --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" data-name="{{ $branch->branch_name }}">{{ $branch->branch_name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    @php $branch = $branches->first(); @endphp
                                    <input type="hidden" name="branch_id" value="{{ $branch->id ?? '' }}">
                                    <input type="hidden" id="branch-name" value="{{ $branch->branch_name ?? '' }}">
                                    <div class="form-control bg-light text-dark"><strong>{{ $branch->branch_name ?? 'No Branch Assigned' }}</strong></div>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date Hired <span class="text-danger">*</span></label>
                                <input type="date" name="date_hired" class="form-control" value="{{ old('date_hired') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="New Hire" {{ old('status') === 'New Hire' ? 'selected' : '' }}>New Hire</option>
                                    <option value="Regular" {{ old('status') === 'Regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="1-2 Years in Service" {{ old('status') === '1-2 Years in Service' ? 'selected' : '' }}>1-2 Years in Service</option>
                                    <option value="3+ Years of Service" {{ old('status') === '3+ Years of Service' ? 'selected' : '' }}>3+ Years of Service</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control" required id="role-select">
                                    <option value="employee">Employee</option>
                                    <option value="finance_officer">Finance Officer</option>
                                    @if(Auth::user()->admin_type === 'super_admin' || blank(Auth::user()->admin_type))
                                        <option value="finance_head">Finance Head</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="basic-salary-field-wrapper">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" step="0.01" name="basic_salary" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Pending Approval:</strong> This employee will be submitted to the System Administrator for approval. Login credentials and account activation will only happen after approval.
                        </div>

                        <div class="mb-3 form-check d-none">
                            <input type="checkbox" name="is_active" class="form-check-input" hidden>
                            <label class="form-check-label text-muted">Hidden approval flag</label>
                        </div>

                        <hr>
                        
                    <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.employees') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <div>
                                <a href="{{ route('admin.fingerprint-demo') }}" class="btn btn-info me-2" target="_blank">
                                    <i class="fas fa-play-circle"></i> View Demo
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Employee
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role-select');
        const branchFieldWrapper = document.getElementById('branch-field-wrapper');
        const basicSalaryFieldWrapper = document.getElementById('basic-salary-field-wrapper');
        const branchSelect = document.getElementById('branch-select');

        function updateFinanceHeadFields() {
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

        if (roleSelect) {
            roleSelect.addEventListener('change', updateFinanceHeadFields);
        }

        updateFinanceHeadFields();
    });
</script>

@endsection
