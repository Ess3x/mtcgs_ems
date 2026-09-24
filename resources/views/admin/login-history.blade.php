@extends('layouts.app')

@section('title', 'Login History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-right-to-bracket text-primary me-2"></i>Login History</h2>
            <p class="text-muted mb-0">Review successful logins, failed attempts, and logouts.</p>
        </div>
        <span class="badge bg-dark">{{ $logs->total() }} records</span>
    </div>

    <form method="GET" class="card shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-2">
                <label for="event" class="form-label">Event</label>
                <select id="event" name="event" class="form-select">
                    <option value="">All events</option>
                    @foreach(['login', 'logout'] as $event)
                        <option value="{{ $event }}" @selected(request('event') === $event)>{{ ucfirst($event) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['success', 'failed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label">User</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label for="from" class="form-label">From</label><input id="from" type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
            <div class="col-md-2"><label for="to" class="form-label">To</label><input id="to" type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
            <div class="col-md-1 d-flex gap-2"><button class="btn btn-primary" type="submit" title="Filter"><i class="fas fa-filter"></i></button><a class="btn btn-outline-secondary" href="{{ route('admin.login-history') }}" title="Clear"><i class="fas fa-rotate-left"></i></a></div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Date and time</th><th>User</th><th>Event</th><th>Status</th><th>Email</th><th>IP address</th><th>Device</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $log->user?->name ?? 'Unknown user' }}</td>
                            <td>{{ ucfirst($log->event) }}</td>
                            <td><span class="badge bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">{{ ucfirst($log->status) }}</span></td>
                            <td>{{ $log->email ?: 'N/A' }}</td>
                            <td>{{ $log->ip_address ?: 'N/A' }}</td>
                            <td><small>{{ $log->user_agent ?: 'N/A' }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No login history found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
