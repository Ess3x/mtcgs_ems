@extends('layouts.app')

@section('title', 'Branch Employees')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h2><i class="fas fa-users me-2"></i> Branch Employees</h2>
                    <p class="mb-0">Managing employees for <strong>{{ $branchName }}</strong> branch</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Employee List</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Employee #</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Fingerprint Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr>
                        <td>{{ $emp->employee_number }}</td>
                        <td>{{ $emp->first_name }} {{ $emp->last_name }}</td>
                        <td>{{ $emp->position ?? 'N/A' }}</td>
                        <td>
                            @if($emp->is_fingerprint_registered)
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Registered</span>
                            @else
                                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not Registered</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('finance.employee.attendance', $emp->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-calendar-alt"></i> Attendance
                            </a>
                            @if(!$emp->is_fingerprint_registered)
                                <button onclick="registerEmployeeFingerprint({{ $emp->id }}, '{{ $emp->first_name }} {{ $emp->last_name }}')" class="btn btn-sm btn-primary">
                                    <i class="fas fa-fingerprint"></i> Register
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No employees found in this branch</small></td>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function registerEmployeeFingerprint(employeeId, employeeName) {
    const fakeFingerprint = btoa('employee_fingerprint_' + Date.now());
    
    const response = await fetch('/api/biometric/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ employee_id: employeeId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for ${employeeName}`);
        location.reload();
    } else {
        alert('Error: ' + result.error);
    }
}
</script>
@endsection
