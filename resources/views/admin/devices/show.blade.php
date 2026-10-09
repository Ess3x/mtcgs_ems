@extends('layouts.app')

@section('title', 'Device Details')

@section('content')
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Device Details</h3>
            <p class="text-muted mb-0">View the registered device information and authorization status.</p>
        </div>
        <a href="{{ route('admin.devices.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Device Name</dt>
                <dd class="col-sm-8">{{ $device->device_name }}</dd>

                <dt class="col-sm-4">Device MAC Address</dt>
                <dd class="col-sm-8"><code>{{ $device->formatted_mac_address }}</code></dd>

                <dt class="col-sm-4">Scanner Serial Number</dt>
                <dd class="col-sm-8">{{ $device->serial_number ?: 'N/A' }}</dd>

                <dt class="col-sm-4">Device ID</dt>
                <dd class="col-sm-8">{{ $device->laptop_mac_address ?: 'N/A' }}</dd>

                <dt class="col-sm-4">Device Type</dt>
                <dd class="col-sm-8">{{ str_replace('_', ' ', ucfirst($device->device_type)) }}</dd>

                <dt class="col-sm-4">Branch</dt>
                <dd class="col-sm-8">{{ $device->branch?->branch_name ?? 'N/A' }}</dd>

                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">{{ ucfirst($device->status) }}</dd>

                <dt class="col-sm-4">Location</dt>
                <dd class="col-sm-8">{{ $device->location ?: 'N/A' }}</dd>

                <dt class="col-sm-4">School Address</dt>
                <dd class="col-sm-8">{{ $device->address ?: 'N/A' }}</dd>

                <dt class="col-sm-4">School Coordinates</dt>
                <dd class="col-sm-8">
                    {{ $device->latitude !== null && $device->longitude !== null ? "{$device->latitude}, {$device->longitude}" : 'N/A' }}
                </dd>

                <dt class="col-sm-4">Allowed Radius</dt>
                <dd class="col-sm-8">{{ $device->radius_meters ? "{$device->radius_meters} meters" : 'N/A' }}</dd>

                <dt class="col-sm-4">School Location Check</dt>
                <dd class="col-sm-8">{{ $device->location_check_enabled ? 'Enabled' : 'Disabled' }}</dd>

                <dt class="col-sm-4">Last Used</dt>
                <dd class="col-sm-8">{{ $device->last_used_at?->format('M d, Y h:i A') ?? 'Never' }}</dd>

                <dt class="col-sm-4">Notes</dt>
                <dd class="col-sm-8">{{ $device->notes ?: 'N/A' }}</dd>
            </dl>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.devices.edit', $device) }}" class="btn btn-primary">Edit Device</a>
            </div>
        </div>
    </div>
</div>
@endsection
