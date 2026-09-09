@extends('layouts.app')

@section('title', 'Device Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Biometric Device Management</h3>
            <p class="text-muted mb-0">Authorized scanner and kiosk devices for attendance validation.</p>
        </div>
        <a href="{{ route('admin.devices.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Register Device
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Devices</div>
                    <div class="fs-3 fw-bold">{{ $devices->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Active</div>
                    <div class="fs-3 fw-bold text-success">{{ $devices->where('status', 'active')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Maintenance</div>
                    <div class="fs-3 fw-bold text-warning">{{ $devices->where('status', 'maintenance')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Inactive</div>
                    <div class="fs-3 fw-bold text-danger">{{ $devices->where('status', 'inactive')->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Registered Devices</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Device Name</th>
                            <th>MAC Address</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Last Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            <tr>
                                <td>{{ $device->device_name }}</td>
                                <td><code>{{ $device->formatted_mac_address }}</code></td>
                                <td>{{ str_replace('_', ' ', ucfirst($device->device_type)) }}</td>
                                <td>{{ $device->branch?->branch_name ?? 'N/A' }}</td>
                                <td>{{ $device->location ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $statusClass = [
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'maintenance' => 'warning',
                                        ][$device->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }} text-uppercase">{{ $device->status }}</span>
                                </td>
                                <td>{{ $device->last_used_at ? $device->last_used_at->format('M d, Y h:i A') : 'Never' }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.devices.edit', $device) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('admin.devices.destroy', $device) }}" method="POST" onsubmit="return confirm('Remove this device from the authorized list?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No devices registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
