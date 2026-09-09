@extends('layouts.app')

@section('title', 'Employee Attendance')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h2><i class="fas fa-calendar-alt me-2"></i> Attendance Record</h2>
                        <p class="mb-0">Employee: <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong> ({{ $employee->employee_number }})</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('finance.employee.attendance', ['id' => $employee->id, 'month' => $prevMonth]) }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="badge bg-white text-dark fs-6 py-2 px-3">
                            {{ $currentMonth->format('F Y') }}
                        </span>
                        <a href="{{ route('finance.employee.attendance', ['id' => $employee->id, 'month' => $nextMonth]) }}" class="btn btn-outline-light btn-sm">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
                        <div>
                            <h5 class="mb-1">Monthly Calendar</h5>
                            <p class="text-muted mb-0">Showing attendance for {{ $currentMonth->format('F Y') }}.</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success">Present</span>
                            <span class="badge bg-warning text-dark">Late</span>
                            <span class="badge bg-secondary">No Record</span>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $weekday)
                            <div class="calendar-cell calendar-header text-center text-uppercase text-muted">
                                {{ $weekday }}
                            </div>
                        @endforeach

                        @php
                            $startDate = $currentMonth->copy()->startOfWeek();
                            $endDate = $currentMonth->copy()->endOfMonth()->endOfWeek();
                        @endphp

                        @for($date = $startDate->copy(); $date->lte($endDate); $date->addDay())
                            @php
                                $dateKey = $date->format('Y-m-d');
                                $attendance = $attendanceByDate[$dateKey] ?? null;
                                $isCurrentMonth = $date->month === $currentMonth->month;
                                $statusClass = $attendance ? ($attendance->status === 'present' ? 'present' : 'late') : 'empty';
                            @endphp

                            <div class="calendar-cell calendar-day {{ $isCurrentMonth ? '' : 'calendar-outside' }} {{ $statusClass }}">
                                <div class="calendar-day-header d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">{{ $date->format('j') }}</span>
                                    @if(!$isCurrentMonth)
                                        <span class="text-muted small">{{ $date->format('M') }}</span>
                                    @endif
                                </div>

                                @if($attendance)
                                    <div class="text-truncate mb-2">
                                        <span class="badge bg-{{ $attendance->late_minutes > 0 ? 'warning text-dark' : 'success' }}">{{ ucfirst($attendance->status) }}</span>
                                    </div>
                                    <div class="calendar-time">
                                        <div><small class="text-muted">AM In</small><br>{{ $attendance->am_in ? date('h:i A', strtotime($attendance->am_in)) : '--' }}</div>
                                        <div><small class="text-muted">PM Out</small><br>{{ $attendance->pm_out ? date('h:i A', strtotime($attendance->pm_out)) : '--' }}</div>
                                    </div>
                                    <div class="text-muted small mt-2">
                                        Late: {{ $attendance->late_minutes > 0 ? $attendance->late_minutes . ' min' : '0 min' }}
                                    </div>
                                @else
                                    <div class="calendar-empty text-muted">
                                        No record
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Attendance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <h3 class="mb-0">{{ $attendanceLogs->count() }}</h3>
                            <p class="text-muted mb-0">Days Recorded</p>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <h3 class="mb-0">{{ $attendanceLogs->where('status','present')->count() }}</h3>
                            <p class="text-muted mb-0">Present</p>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <h3 class="mb-0">{{ $attendanceLogs->where('late_minutes','>',0)->count() }}</h3>
                            <p class="text-muted mb-0">Late Days</p>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-0">{{ number_format($attendanceLogs->sum('overtime_hours'), 2) }}</h3>
                            <p class="text-muted mb-0">OT Hours</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 1rem;
}
.calendar-cell {
    min-height: 130px;
    border: 1px solid #e9ecef;
    border-radius: 0.85rem;
    padding: 0.85rem;
    background: #ffffff;
}
.calendar-header {
    background: #f8f9fa;
    border-color: #e9ecef;
    font-size: 0.85rem;
}
.calendar-day {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.calendar-day:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}
.calendar-outside {
    background: #f8f9fa;
    color: #adb5bd;
}
.calendar-day.present {
    background: #e9f7ef;
}
.calendar-day.late {
    background: #fff4e6;
}
.calendar-day.empty {
    background: #f8f9fa;
}
.calendar-day-header {
    font-size: 0.95rem;
}
.calendar-time {
    display: grid;
    gap: 0.4rem;
    font-size: 0.85rem;
}
.calendar-empty {
    margin-top: 1rem;
    font-size: 0.9rem;
}
@media (max-width: 767px) {
    .calendar-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
@endsection
