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
            <form action="{{ route('admin.devices.store') }}" method="POST" data-device-form>
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="device_name" class="form-label">Device Name</label>
                        <input type="text" name="device_name" id="device_name" class="form-control" value="{{ old('device_name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="mac_address" class="form-label">Device MAC Address</label>
                        <input type="text" name="mac_address" id="mac_address" class="form-control" value="{{ old('mac_address') }}" placeholder="00:1A:2B:3C:4D:5E" required>
                    </div>

                    <div class="col-md-6">
                        <label for="serial_number" class="form-label">Scanner Serial Number</label>
                        <input type="text" name="serial_number" id="serial_number" class="form-control" value="{{ old('serial_number') }}" placeholder="S/N from fingerprint scanner" required>
                    </div>

                    <div class="col-md-6">
                        <label for="laptop_mac_address" class="form-label">Device ID</label>
                        <input type="text" name="laptop_mac_address" id="laptop_mac_address" class="form-control" value="{{ old('laptop_mac_address') }}" placeholder="Optional device ID">
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
                        <input type="text" name="location" id="location" class="form-control" value="{{ old('location', 'School') }}" placeholder="School">
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label">School Address</label>
                        <input type="text" name="address" id="address" class="form-control" value="{{ old('address', 'San Nicolas, Iriga City, Camarines Sur') }}" placeholder="School address">
                    </div>

                    <div class="col-md-4">
                        <label for="latitude" class="form-label">School Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" id="latitude" class="form-control" value="{{ old('latitude', '13.433153') }}" placeholder="13.433153">
                    </div>

                    <div class="col-md-4">
                        <label for="longitude" class="form-label">School Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" id="longitude" class="form-control" value="{{ old('longitude', '123.411049') }}" placeholder="123.411049">
                    </div>

                    <div class="col-md-4">
                        <label for="radius_meters" class="form-label">Allowed Radius (meters)</label>
                        <input type="number" name="radius_meters" id="radius_meters" class="form-control" value="{{ old('radius_meters', 100) }}" min="1" max="10000">
                    </div>

                    <div class="col-12 form-check ms-2 mt-2">
                        <input type="hidden" name="location_check_enabled" value="0">
                        <input type="checkbox" name="location_check_enabled" id="location_check_enabled" class="form-check-input" value="1" {{ old('location_check_enabled', true) ? 'checked' : '' }}>
                        <label for="location_check_enabled" class="form-check-label">Enable school location check</label>
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

@push('scripts')
<script>
document.querySelector('[data-device-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    try {
        const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: new FormData(form) });
        const payload = await response.json();
        if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Unable to register device.');
        alert(payload.message);
        window.location.href = '{{ route('admin.devices.index') }}';
    } catch (error) { alert(error.message); } finally { submitButton.disabled = false; }
});
document.querySelector('#branch_id')?.addEventListener('change', async (event) => {
    if (!event.target.value) return;
    const response = await fetch('{{ url('/admin/devices/branches') }}/' + event.target.value, { headers: { Accept: 'application/json' } });
    const payload = await response.json();
    if (payload.success && payload.branch.address) document.querySelector('#address').value = payload.branch.address;
});
</script>
@endpush
