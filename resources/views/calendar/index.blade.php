@extends('layouts.app')

@section('title', 'Calendar Events')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>Calendar Events
                    </h2>
                    <p class="text-muted mb-0">Manage school activities and holidays</p>
                </div>
                @if(Auth::user()->role === 'admin')
                    <div class="d-flex gap-2">
                        <a href="{{ route('calendar.calendar') }}" class="btn btn-outline-primary">
                            <i class="fas fa-calendar me-2"></i>Calendar View
                        </a>
                        <a href="{{ route('admin.calendar.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Event
                        </a>
                    </div>
                @else
                    <a href="{{ route('calendar.calendar') }}" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>Calendar View
                    </a>
                @endif
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('calendar.index') }}" class="d-flex flex-wrap align-items-end gap-2 mb-3 p-3 bg-light rounded">
        <div>
            <label for="calendar-period" class="form-label mb-1">Show</label>
            <select id="calendar-period" name="period" class="form-select">
                <option value="" @selected(!request('period'))>All dates</option>
                <option value="yearly" @selected(request('period') === 'yearly')>Yearly</option>
                <option value="monthly" @selected(request('period') === 'monthly')>Monthly</option>
            </select>
        </div>
        <div>
            <label for="calendar-year" class="form-label mb-1">Year</label>
            <input id="calendar-year" type="number" name="year" class="form-control" min="2026" max="2100" value="{{ min(2100, max(2026, (int) request('year', now()->year))) }}">
        </div>
        <div id="calendar-month-field" @class(['d-none' => request('period') !== 'monthly'])>
            <label for="calendar-month" class="form-label mb-1">Month</label>
            <select id="calendar-month" name="month" class="form-select">
                @foreach(range(1, 12) as $monthNumber)
                    <option value="{{ $monthNumber }}" @selected((int) request('month', now()->month) === $monthNumber)>
                        {{ \Carbon\Carbon::create()->month($monthNumber)->format('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter me-1"></i>Filter
        </button>
        @if(request()->hasAny(['period', 'year', 'month']))
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </form>

    <!-- Events List -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    @if($events->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 calendar-events-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Created By</th>
                                        @if(Auth::user()->role === 'admin')
                                            <th class="text-center">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($events as $event)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-{{ $event->event_type === 'activity' ? 'primary' : ($event->event_type === 'suspension' ? 'danger' : 'warning') }} text-white me-3">
                                                    <i class="fas fa-{{ $event->event_type === 'activity' ? 'graduation-cap' : ($event->event_type === 'suspension' ? 'triangle-exclamation' : 'umbrella-beach') }}"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $event->title }}</h6>
                                                    @if($event->description)
                                                        <small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $event->event_type === 'activity' ? 'primary' : ($event->event_type === 'suspension' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($event->event_type) }}
                                            </span>
                                            @if($event->exists && $event->approval_status === 'pending')
                                                <span class="badge bg-secondary">Pending Approval</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $event->event_date->format('M d, Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $event->event_date->format('l') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($event->branch)
                                                <span class="badge bg-info">{{ $event->branch->branch_name }}</span>
                                            @else
                                                <span class="badge bg-secondary">All Branches</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($event->creator)->name ?? 'Philippine Holiday Calendar' }}</td>
                                        @if(Auth::user()->role === 'admin')
                                            <td class="text-center">
                                                @if($event->exists && $event->getKey())
                                                    <div class="btn-group calendar-event-actions" role="group">
                                                        @if(Auth::user()->isSuperAdmin() && in_array($event->event_type, ['holiday', 'suspension'], true) && $event->approval_status === 'pending')
                                                            <form method="POST" action="{{ route('admin.calendar.approve', $event) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                                            </form>
                                                        @endif
                                                        <a href="{{ route('admin.calendar.edit', $event) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" action="{{ route('admin.calendar.destroy', $event) }}" class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this event?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-muted">System holiday</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No calendar events found</h5>
                            <p class="text-muted mb-3">There are no scheduled activities or holidays at this time.</p>
                            @if(Auth::user()->role === 'admin')
                                <a href="{{ route('admin.calendar.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add First Event
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    body.dark-mode .calendar-events-table tbody tr,
    body.dark-mode .calendar-events-table tbody td {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    body.dark-mode .calendar-events-table.table-hover tbody tr:hover,
    body.dark-mode .calendar-events-table.table-hover tbody tr:hover td {
        background-color: #273449 !important;
    }

    body.dark-mode .calendar-event-actions .btn-outline-success {
        color: #ffffff;
        background-color: #15803d;
        border-color: #4ade80;
    }

    body.dark-mode .calendar-event-actions .btn-outline-success:hover,
    body.dark-mode .calendar-event-actions .btn-outline-success:focus-visible {
        color: #ffffff;
        background-color: #166534;
    }

    body.dark-mode .calendar-event-actions .btn-outline-primary {
        color: #ffffff;
        background-color: #1d4ed8;
        border-color: #60a5fa;
    }

    body.dark-mode .calendar-event-actions .btn-outline-primary:hover,
    body.dark-mode .calendar-event-actions .btn-outline-primary:focus-visible {
        color: #ffffff;
        background-color: #1e40af;
    }

    body.dark-mode .calendar-event-actions .btn-outline-danger {
        color: #ffffff;
        background-color: #b91c1c;
        border-color: #fb7185;
    }

    body.dark-mode .calendar-event-actions .btn-outline-danger:hover,
    body.dark-mode .calendar-event-actions .btn-outline-danger:focus-visible {
        color: #ffffff;
        background-color: #991b1b;
    }
</style>
<script>
    document.getElementById('calendar-period').addEventListener('change', function () {
        document.getElementById('calendar-month-field').classList.toggle('d-none', this.value !== 'monthly');
    });
</script>
@endsection