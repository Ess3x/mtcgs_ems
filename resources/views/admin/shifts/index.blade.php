@extends('layouts.app')

@section('title', 'Shifts and Schedules')

@section('content')
<div class="container-fluid">
    <div class="mb-4"><h2 class="fw-bold mb-1"><i class="fas fa-calendar-days text-primary me-2"></i>Shifts & Schedules</h2><p class="text-muted mb-0">Create work schedules and assign them to active employees.</p></div>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card" id="create-shift"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Create Shift</h5><span class="badge bg-primary">Add more options</span></div><div class="card-body">
                <form method="POST" action="{{ route('admin.shifts.store') }}">@csrf
                    <div class="mb-3"><label for="shift-name" class="form-label">Shift Name</label><input id="shift-name" name="name" class="form-control" placeholder="Morning Shift" required></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label for="class-code" class="form-label">Class Code</label><input id="class-code" name="class_code" class="form-control" placeholder="IT 1211"></div><div class="col-6"><label for="room" class="form-label">Room</label><input id="room" name="room" class="form-control" placeholder="Room 4"></div></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label for="shift-start" class="form-label">Start</label><input id="shift-start" name="start_time" type="time" class="form-control" value="07:00" required></div><div class="col-6"><label for="shift-end" class="form-label">End</label><input id="shift-end" name="end_time" type="time" class="form-control" value="17:00" required></div></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label for="break-start" class="form-label">Break Start</label><input id="break-start" name="break_start" type="time" class="form-control" value="12:00"></div><div class="col-6"><label for="break-end" class="form-label">Break End</label><input id="break-end" name="break_end" type="time" class="form-control" value="13:00"></div></div>
                    <fieldset class="mb-3"><legend class="form-label fs-6">Working Days</legend><div class="d-flex flex-wrap gap-3">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<label class="form-check"><input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $day }}" @checked($day !== 'Sat' && $day !== 'Sun')>{{ $day }}</label>@endforeach</div></fieldset>
                    <button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Create Shift</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-7"><div class="card"><div class="card-header"><h5 class="mb-0">Available Shifts</h5></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Class Code</th><th>Room</th><th>Schedule</th><th>Employees</th><th>Action</th></tr></thead><tbody>@forelse($shifts as $shift)<tr><td><strong>{{ $shift->name }}</strong></td><td>{{ $shift->class_code ?: '--' }}</td><td>{{ $shift->room ?: '--' }}</td><td>{{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }}<small class="d-block text-muted">{{ $shift->working_days }}</small></td><td>{{ $shift->assigned_employees_count }}</td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editShift{{ $shift->id }}" title="Edit shift"><i class="fas fa-pen"></i></button> <form class="d-inline" method="POST" action="{{ route('admin.shifts.destroy', $shift) }}" onsubmit="return confirm('Delete this shift?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete shift"><i class="fas fa-trash"></i></button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No shifts created.</td></tr>@endforelse</tbody></table></div></div></div>
    </div>
    <div class="card mt-4"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Employee Assignments</h5><a href="#create-shift" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>Add New Shift</a></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Employee</th><th>Branch</th><th>Assigned Shifts</th><th>Save</th></tr></thead><tbody>@foreach($employees as $employee)<tr><td>{{ $employee->full_name }}<small class="d-block text-muted">{{ $employee->employee_number }}</small></td><td>{{ $employee->branch->branch_name ?? 'N/A' }}</td><td><form id="assignment-{{ $employee->id }}" method="POST" action="{{ route('admin.shifts.assign', $employee) }}">@csrf<div class="d-flex flex-column gap-2">@foreach($shifts as $shift)<label class="form-check"><input class="form-check-input" type="checkbox" name="shift_ids[]" value="{{ $shift->id }}" @checked($employee->shifts->contains('id', $shift->id))><span class="form-check-label">{{ $shift->name }}{{ $shift->class_code ? ' | ' . $shift->class_code : '' }}{{ $shift->room ? ' | ' . $shift->room : '' }} <small class="text-muted">({{ substr($shift->start_time, 0, 5) }}-{{ substr($shift->end_time, 0, 5) }})</small></span></label>@endforeach</div></form></td><td><button form="assignment-{{ $employee->id }}" class="btn btn-sm btn-primary">Save</button></td></tr>@endforeach</tbody></table></div></div>
    @foreach($shifts as $shift)
        <div class="modal fade" id="editShift{{ $shift->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Edit Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.shifts.update', $shift) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Shift Name</label><input name="name" class="form-control" value="{{ $shift->name }}" required></div>
                                <div class="col-md-3"><label class="form-label">Class Code</label><input name="class_code" class="form-control" value="{{ $shift->class_code }}"></div>
                                <div class="col-md-3"><label class="form-label">Room</label><input name="room" class="form-control" value="{{ $shift->room }}"></div>
                                <div class="col-md-6"><label class="form-label">Start</label><input name="start_time" type="time" class="form-control" value="{{ substr($shift->start_time, 0, 5) }}" required></div>
                                <div class="col-md-6"><label class="form-label">End</label><input name="end_time" type="time" class="form-control" value="{{ substr($shift->end_time, 0, 5) }}" required></div>
                                <div class="col-md-6"><label class="form-label">Break Start</label><input name="break_start" type="time" class="form-control" value="{{ $shift->break_start ? substr($shift->break_start, 0, 5) : '' }}"></div>
                                <div class="col-md-6"><label class="form-label">Break End</label><input name="break_end" type="time" class="form-control" value="{{ $shift->break_end ? substr($shift->break_end, 0, 5) : '' }}"></div>
                                <div class="col-12"><label class="form-label">Working Days</label><div class="d-flex flex-wrap gap-3">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<label class="form-check"><input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $day }}" @checked(in_array($day, $shift->working_days_list, true))>{{ $day }}</label>@endforeach</div></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
