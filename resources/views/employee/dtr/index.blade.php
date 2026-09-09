@extends('layouts.app')

@section('title', 'Daily Time Record (DTR)')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Daily Time Record (DTR)</h2>
                    <p class="text-muted mt-2">{{ $employeeProfile->first_name }} {{ $employeeProfile->last_name }}</p>
                </div>
                <div>
                    <a href="{{ route('employee.dtr.summary') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Summary
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Period Card -->
    @if ($currentDTR)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Current Period</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Period</p>
                            <h6>{{ $currentDTR->period_start->format('M d') }} - {{ $currentDTR->period_end->format('M d, Y') }}</h6>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Status</p>
                            <span class="badge bg-{{ $currentDTR->status === 'draft' ? 'warning' : ($currentDTR->status === 'submitted' ? 'info' : ($currentDTR->status === 'approved' ? 'success' : 'danger')) }}">
                                {{ ucfirst($currentDTR->status) }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Days Present</p>
                            <h6>{{ $currentDTR->getDaysPresent() }} / {{ $currentDTR->getWorkingDays() }}</h6>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="{{ route('employee.dtr.show', $currentDTR->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info mb-4">
            No DTR has been generated for the current period yet.
        </div>
    @endif

    <!-- DTR History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">DTR History</h5>
                </div>
                <div class="card-body">
                    @if ($dtrs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th class="text-center">Days Present</th>
                                        <th class="text-center">Hours Worked</th>
                                        <th class="text-center">Overtime</th>
                                        <th class="text-center">Late (mins)</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dtrs as $dtr)
                                        <tr>
                                            <td>
                                                <strong>{{ $dtr->period_start->format('M d') }} - {{ $dtr->period_end->format('M d, Y') }}</strong>
                                            </td>
                                            <td class="text-center">
                                                {{ $dtr->getDaysPresent() }}/{{ $dtr->getWorkingDays() }}
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($dtr->getTotalHoursWorked(), 1) }} hrs
                                            </td>
                                            <td class="text-center">
                                                @php $overtime = $dtr->getTotalOvertimeHours(); @endphp
                                                @if ($overtime > 0)
                                                    <span class="badge bg-warning">{{ number_format($overtime, 1) }} hrs</span>
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php $late = $dtr->getTotalLateMinutes(); @endphp
                                                @if ($late > 0)
                                                    <span class="badge bg-danger">{{ $late }}</span>
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $dtr->status === 'draft' ? 'warning' : ($dtr->status === 'submitted' ? 'info' : ($dtr->status === 'approved' ? 'success' : 'danger')) }}">
                                                    {{ ucfirst($dtr->status) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('employee.dtr.show', $dtr->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $dtrs->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No DTR records found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
