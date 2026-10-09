

<?php $__env->startSection('title', 'Device Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Device Details</h3>
            <p class="text-muted mb-0">View the registered device information and authorization status.</p>
        </div>
        <a href="<?php echo e(route('admin.devices.index')); ?>" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if(session('new_device_credential')): ?>
                <div class="alert alert-warning" role="alert">
                    <strong>Save this device credential now.</strong> It is shown only once. Use it when building the installer for this device.
                    <div class="input-group mt-2">
                        <input id="device-credential" class="form-control font-monospace" value="<?php echo e(session('new_device_credential')); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" data-copy-credential>Copy</button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <strong>Biometric API credential:</strong>
                    <span class="badge <?php echo e($device->api_secret ? 'bg-success' : 'bg-warning text-dark'); ?>">
                        <?php echo e($device->api_secret ? 'Provisioned' : 'Not provisioned'); ?>

                    </span>
                    <div class="small text-muted">The secret is not displayed again. Rotating it immediately disables the old installer credential.</div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm"
                    data-rotate-credential
                    data-url="<?php echo e(route('admin.devices.credential.rotate', $device)); ?>"
                    data-csrf="<?php echo e(csrf_token()); ?>">
                    <?php echo e($device->api_secret ? 'Rotate Credential' : 'Generate Credential'); ?>

                </button>
            </div>
            <div class="alert alert-warning d-none" role="alert" data-credential-result></div>

            <dl class="row mb-0">
                <dt class="col-sm-4">Device Name</dt>
                <dd class="col-sm-8"><?php echo e($device->device_name); ?></dd>

                <dt class="col-sm-4">Device MAC Address</dt>
                <dd class="col-sm-8"><code><?php echo e($device->formatted_mac_address); ?></code></dd>

                <dt class="col-sm-4">Scanner Serial Number</dt>
                <dd class="col-sm-8"><?php echo e($device->serial_number ?: 'N/A'); ?></dd>

                <dt class="col-sm-4">Device ID</dt>
                <dd class="col-sm-8"><?php echo e($device->laptop_mac_address ?: 'N/A'); ?></dd>

                <dt class="col-sm-4">Device Type</dt>
                <dd class="col-sm-8"><?php echo e(str_replace('_', ' ', ucfirst($device->device_type))); ?></dd>

                <dt class="col-sm-4">Branch</dt>
                <dd class="col-sm-8"><?php echo e($device->branch?->branch_name ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8"><?php echo e(ucfirst($device->status)); ?></dd>

                <dt class="col-sm-4">Location</dt>
                <dd class="col-sm-8"><?php echo e($device->location ?: 'N/A'); ?></dd>

                <dt class="col-sm-4">School Address</dt>
                <dd class="col-sm-8"><?php echo e($device->address ?: 'N/A'); ?></dd>

                <dt class="col-sm-4">School Coordinates</dt>
                <dd class="col-sm-8">
                    <?php echo e($device->latitude !== null && $device->longitude !== null ? "{$device->latitude}, {$device->longitude}" : 'N/A'); ?>

                </dd>

                <dt class="col-sm-4">Allowed Radius</dt>
                <dd class="col-sm-8"><?php echo e($device->radius_meters ? "{$device->radius_meters} meters" : 'N/A'); ?></dd>

                <dt class="col-sm-4">School Location Check</dt>
                <dd class="col-sm-8"><?php echo e($device->location_check_enabled ? 'Enabled' : 'Disabled'); ?></dd>

                <dt class="col-sm-4">Last Used</dt>
                <dd class="col-sm-8"><?php echo e($device->last_used_at?->format('M d, Y h:i A') ?? 'Never'); ?></dd>

                <dt class="col-sm-4">Notes</dt>
                <dd class="col-sm-8"><?php echo e($device->notes ?: 'N/A'); ?></dd>
            </dl>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?php echo e(route('admin.devices.edit', $device)); ?>" class="btn btn-primary">Edit Device</a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.querySelector('[data-rotate-credential]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    if (!confirm('Generate a new credential? The existing installer will stop working immediately.')) return;

    button.disabled = true;
    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': button.dataset.csrf,
            },
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Could not rotate device credential.');

        const result = document.querySelector('[data-credential-result]');
        result.classList.remove('d-none');
        result.replaceChildren();
        const notice = document.createElement('strong');
        notice.textContent = 'Copy and save this credential now.';
        result.append(notice, document.createTextNode(' It will not be shown again. Update this device\'s installer before using it.'));

        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group mt-2';
        const input = document.createElement('input');
        input.className = 'form-control font-monospace';
        input.readOnly = true;
        input.value = payload.credential;
        const copyButton = document.createElement('button');
        copyButton.className = 'btn btn-outline-secondary';
        copyButton.type = 'button';
        copyButton.textContent = 'Copy';
        copyButton.addEventListener('click', async () => {
            await navigator.clipboard.writeText(input.value);
        });
        inputGroup.append(input, copyButton);
        result.append(inputGroup);
        button.textContent = 'Credential Rotated';
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
});

document.querySelectorAll('[data-copy-credential]').forEach(button => {
    button.addEventListener('click', async () => {
        const input = button.parentElement.querySelector('input');
        await navigator.clipboard.writeText(input.value);
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/admin/devices/show.blade.php ENDPATH**/ ?>