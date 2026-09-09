@extends('layouts.app')

@section('title', 'View User')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle"></i> 
                        {{ ucfirst($role) }} Details
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Employee Number:</div>
                        <div class="col-md-8">{{ $userData->employee_number }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Full Name:</div>
                        <div class="col-md-8">{{ $userData->first_name }} {{ $userData->last_name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8">{{ $userData->user->email ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Position:</div>
                        <div class="col-md-8">{{ $userData->position ?? 'N/A' }}</div>
                    </div>
                    
                    @if($role === 'employee')
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Branch:</div>
                            <div class="col-md-8">{{ $userData->branch->branch_name ?? 'N/A' }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Basic Salary:</div>
                            <div class="col-md-8">₱{{ number_format($userData->basic_salary ?? 0, 2) }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Fingerprint Registered:</div>
                            <div class="col-md-8">
                                @if($userData->is_fingerprint_registered)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if($role === 'finance')
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Can Process Payroll:</div>
                            <div class="col-md-8">
                                @if($userData->can_process_payroll)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if($role === 'admin')
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Admin Level:</div>
                            <div class="col-md-8">
                                @if($userData->admin_level == 'super_admin')
                                    <span class="badge bg-danger">Super Admin</span>
                                @else
                                    <span class="badge bg-info">Admin</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Status:</div>
                        <div class="col-md-8">
                            @if($userData->user && $userData->user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Date Hired:</div>
                        <div class="col-md-8">{{ date('F d, Y', strtotime($userData->date_hired)) }}</div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.user-management') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <a href="{{ route('admin.user-edit', ['role' => $role, 'id' => $userData->id]) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
