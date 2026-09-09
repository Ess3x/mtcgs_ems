@extends('layouts.app')

@section('title', 'Edit Device')

@section('content')
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Edit Device</h3>
            <p class="text-muted mb-0">Update the biometric device record and authorization status.</p>
        </div>
        <a href="{{ route('admin.devices.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.devices.update', $device) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="device_name" class="form-label">Device Name</label>
                        <input type="text" name="device_name" id="device_name" class="form-control" value="{{ old('device_name', $device->device_name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="mac_address" class="form-label">MAC Address</label>
                        <input type="text" name="mac_address" id="mac_address" class="form-control" value="{{ $device->formatted_mac_address }}" readonly aria-readonly="true" tabindex="-1">
                        <small class="text-muted">MAC address is locked and cannot be changed after registration.</small>
                    </div>

                    <div class="col-md-4">
                        <label for="device_type" class="form-label">Device Type</label>
                        <select name="device_type" id="device_type" class="form-select" required>
                            <option value="biometric_scanner" {{ old('device_type', $device->device_type) === 'biometric_scanner' ? 'selected' : '' }}>Biometric Scanner</option>
                            <option value="kiosk" {{ old('device_type', $device->device_type) === 'kiosk' ? 'selected' : '' }}>Kiosk</option>
                            <option value="computer" {{ old('device_type', $device->device_type) === 'computer' ? 'selected' : '' }}>Computer</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">No branch assigned</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $device->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active" {{ old('status', $device->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $device->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="maintenance" {{ old('status', $device->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" name="location" id="location" class="form-control" value="{{ old('location', $device->location) }}" placeholder="Ground Floor, Enrollment Office, etc.">
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Optional notes or hardware notes">{{ old('notes', $device->notes) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.devices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Device</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
