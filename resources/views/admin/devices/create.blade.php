@extends('layouts.app')

@section('title', 'Register Device')

@section('content')
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Register New Device</h3>
            <p class="text-muted mb-0">Add a biometric scanner, kiosk, or workstation to the approved attendance devices list.</p>
        </div>
        <a href="{{ route('admin.devices.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.devices.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="device_name" class="form-label">Device Name</label>
                        <input type="text" name="device_name" id="device_name" class="form-control" value="{{ old('device_name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="mac_address" class="form-label">MAC Address</label>
                        <input type="text" name="mac_address" id="mac_address" class="form-control" value="{{ old('mac_address') }}" placeholder="00:1A:2B:3C:4D:5E" required>
                    </div>

                    <div class="col-md-4">
                        <label for="device_type" class="form-label">Device Type</label>
                        <select name="device_type" id="device_type" class="form-select" required>
                            <option value="">Select</option>
                            <option value="biometric_scanner" {{ old('device_type') === 'biometric_scanner' ? 'selected' : '' }}>Biometric Scanner</option>
                            <option value="kiosk" {{ old('device_type') === 'kiosk' ? 'selected' : '' }}>Kiosk</option>
                            <option value="computer" {{ old('device_type') === 'computer' ? 'selected' : '' }}>Computer</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">No branch assigned</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" placeholder="Ground Floor, Enrollment Office, etc.">
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Optional notes or hardware notes">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.devices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Device</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
