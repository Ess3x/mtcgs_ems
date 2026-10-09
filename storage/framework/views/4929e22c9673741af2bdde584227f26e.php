

<?php $__env->startSection('title', 'Device Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Biometric Device Management</h3>
            <p class="text-muted mb-0">Authorized scanner and kiosk devices for attendance validation.</p>
        </div>
        <a href="<?php echo e(route('admin.devices.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Register Device
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Devices</div>
                    <div id="deviceTotalCount" class="fs-3 fw-bold"><?php echo e($devices->count()); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Active</div>
                    <div id="deviceActiveCount" class="fs-3 fw-bold text-success"><?php echo e($devices->where('status', 'active')->count()); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Maintenance</div>
                    <div id="deviceMaintenanceCount" class="fs-3 fw-bold text-warning"><?php echo e($devices->where('status', 'maintenance')->count()); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Inactive</div>
                    <div id="deviceInactiveCount" class="fs-3 fw-bold text-danger"><?php echo e($devices->where('status', 'inactive')->count()); ?></div>
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
                            <th>Device MAC Address</th>
                            <th>Scanner Serial</th>
                            <th>Device ID</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Last Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr data-device-id="<?php echo e($device->id); ?>">
                                <td><?php echo e($device->device_name); ?></td>
                                <td><code><?php echo e($device->formatted_mac_address); ?></code></td>
                                <td><code><?php echo e($device->serial_number ?? 'N/A'); ?></code></td>
                                <td><code><?php echo e($device->laptop_mac_address ?: 'N/A'); ?></code></td>
                                <td><?php echo e(str_replace('_', ' ', ucfirst($device->device_type))); ?></td>
                                <td><?php echo e($device->branch?->branch_name ?? 'N/A'); ?></td>
                                <td><?php echo e($device->location ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                        $statusClass = [
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'maintenance' => 'warning',
                                        ][$device->status] ?? 'secondary';
                                    ?>
                                    <select class="form-select form-select-sm device-status" data-status-url="<?php echo e(route('admin.devices.status', $device)); ?>" aria-label="Update status for <?php echo e($device->device_name); ?>">
                                        <?php $__currentLoopData = ['active' => 'Active', 'inactive' => 'Inactive', 'maintenance' => 'Maintenance']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($status); ?>" <?php echo e($device->status === $status ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </td>
                                <td class="device-last-used"><?php echo e($device->last_used_at ? $device->last_used_at->format('M d, Y h:i A') : 'Never'); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?php echo e(route('admin.devices.show', $device)); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="<?php echo e(route('admin.devices.edit', $device)); ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="<?php echo e(route('admin.devices.destroy', $device)); ?>" method="POST" data-delete-device>
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No devices registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const deviceStatusDataUrl = <?php echo json_encode(route('admin.devices.status-data'), 15, 512) ?>;
const statusLabels = { active: 'Active', inactive: 'Inactive', maintenance: 'Maintenance' };

function refreshDeviceStatusSummary(devices) {
    const counts = devices.reduce((summary, device) => {
        summary.total++;
        if (summary[device.status] !== undefined) summary[device.status]++;
        return summary;
    }, { total: 0, active: 0, maintenance: 0, inactive: 0 });
    document.getElementById('deviceTotalCount').textContent = counts.total;
    document.getElementById('deviceActiveCount').textContent = counts.active;
    document.getElementById('deviceMaintenanceCount').textContent = counts.maintenance;
    document.getElementById('deviceInactiveCount').textContent = counts.inactive;
}

async function pollDeviceStatuses() {
    try {
        const response = await fetch(deviceStatusDataUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) return;
        const payload = await response.json();
        const devices = Array.isArray(payload.devices) ? payload.devices : [];
        refreshDeviceStatusSummary(devices);
        devices.forEach((device) => {
            const row = document.querySelector(`[data-device-id="${device.id}"]`);
            if (!row) return;
            const select = row.querySelector('.device-status');
            if (select && select.value !== device.status && !select.disabled) {
                select.value = device.status;
                [...select.options].forEach((option) => { option.defaultSelected = option.value === device.status; });
            }
            const lastUsed = row.querySelector('.device-last-used');
            if (lastUsed) lastUsed.textContent = device.last_used_at || 'Never';
        });
    } catch (error) {
        console.debug('Device status refresh unavailable:', error);
    }
}

pollDeviceStatuses();
window.setInterval(pollDeviceStatuses, 5000);

document.querySelectorAll('.device-status').forEach((select) => select.addEventListener('change', async (event) => {
    const control = event.currentTarget;
    const previousStatus = [...control.options].find((option) => option.defaultSelected)?.value;
    control.disabled = true;
    try {
        const response = await fetch(control.dataset.statusUrl, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ status: control.value }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Unable to update device status.');
        [...control.options].forEach((option) => { option.defaultSelected = option.value === control.value; });
    } catch (error) {
        if (previousStatus) control.value = previousStatus;
        alert(error.message);
    } finally { control.disabled = false; }
}));

document.querySelectorAll('[data-delete-device]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!confirm('Remove this device from the authorized list?')) return;
    const response = await fetch(form.action, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
    const payload = await response.json();
    if (!response.ok) return alert(payload.message || 'Unable to remove device.');
    form.closest('tr').remove();
}));
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/admin/devices/index.blade.php ENDPATH**/ ?>