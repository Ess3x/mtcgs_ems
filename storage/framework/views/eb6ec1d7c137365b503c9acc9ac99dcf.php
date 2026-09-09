

<?php $__env->startSection('title', 'Edit Branch Admin Account'); ?>

<?php
    $branchHeadId = $branchHead?->id ?? request()->route('branchHead');
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Branch Head Account</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.branch-heads.update', ['branchHead' => $branchHeadId])); ?>" method="POST" class="needs-validation">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        
                        <div class="alert alert-secondary mb-3">
                            <strong>Email:</strong> <?php echo e(optional($branchHead->user)->email ?? 'No email assigned'); ?>

                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control <?php echo e($errors->has('first_name') ? 'is-invalid' : ''); ?>" 
                                    id="first_name" name="first_name" value="<?php echo e(old('first_name', $branchHead->first_name)); ?>" required>
                                <?php if($errors->has('first_name')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('first_name')); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control <?php echo e($errors->has('middle_name') ? 'is-invalid' : ''); ?>" 
                                    id="middle_name" name="middle_name" value="<?php echo e(old('middle_name', $branchHead->middle_name)); ?>">
                                <?php if($errors->has('middle_name')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('middle_name')); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control <?php echo e($errors->has('last_name') ? 'is-invalid' : ''); ?>" 
                                    id="last_name" name="last_name" value="<?php echo e(old('last_name', $branchHead->last_name)); ?>" required>
                                <?php if($errors->has('last_name')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('last_name')); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">Assigned Branch *</label>
                                <select class="form-control <?php echo e($errors->has('branch_id') ? 'is-invalid' : ''); ?>" 
                                    id="branch_id" name="branch_id" required>
                                    <option value="">-- Select Branch --</option>
                                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($branch->id); ?>" <?php echo e(old('branch_id', $branchHead->branch_id) == $branch->id ? 'selected' : ''); ?>>
                                            <?php echo e($branch->branch_name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php if($errors->has('branch_id')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('branch_id')); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control <?php echo e($errors->has('position') ? 'is-invalid' : ''); ?>" 
                                    id="position" name="position" value="<?php echo e(old('position', $branchHead->position)); ?>">
                                <?php if($errors->has('position')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('position')); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="basic_salary" class="form-label">Basic Pay *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0" class="form-control <?php echo e($errors->has('basic_salary') ? 'is-invalid' : ''); ?>"
                                        id="basic_salary" name="basic_salary" value="<?php echo e(old('basic_salary', $branchHead->basic_salary ?? 0)); ?>" required>
                                </div>
                                <?php if($errors->has('basic_salary')): ?>
                                    <div class="invalid-feedback d-block"><?php echo e($errors->first('basic_salary')); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="date_hired" class="form-label">Date Hired</label>
                                <input type="date" class="form-control <?php echo e($errors->has('date_hired') ? 'is-invalid' : ''); ?>" 
                                    id="date_hired" name="date_hired" value="<?php echo e(old('date_hired', optional($branchHead->date_hired)->format('Y-m-d'))); ?>">
                                <?php if($errors->has('date_hired')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('date_hired')); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control <?php echo e($errors->has('contact_number') ? 'is-invalid' : ''); ?>" 
                                    id="contact_number" name="contact_number" value="<?php echo e(old('contact_number', $branchHead->contact_number)); ?>">
                                <?php if($errors->has('contact_number')): ?>
                                    <div class="invalid-feedback"><?php echo e($errors->first('contact_number')); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control <?php echo e($errors->has('address') ? 'is-invalid' : ''); ?>" 
                                id="address" name="address" rows="3"><?php echo e(old('address', $branchHead->address)); ?></textarea>
                            <?php if($errors->has('address')): ?>
                                <div class="invalid-feedback"><?php echo e($errors->first('address')); ?></div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-fingerprint"></i> Fingerprint Registration
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Important:</strong> Fingerprint registration is required for attendance tracking. Please register the branch head's fingerprint now.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-label">Fingerprint Status</div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="fingerprint-status" class="badge <?php echo e($branchHead->is_fingerprint_registered ? 'bg-success' : 'bg-secondary'); ?> <?php echo e($branchHead->is_fingerprint_registered ? '' : 'd-none'); ?>">
                                                    <?php echo e($branchHead->is_fingerprint_registered ? 'Registered' : 'Not Registered'); ?>

                                                </span>
                                                <button type="button" id="register-fingerprint-btn" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-fingerprint"></i> <?php echo e($branchHead->is_fingerprint_registered ? 'Register Again' : 'Register Fingerprint'); ?>

                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fingerprint-data" class="form-label">Fingerprint Data</label>
                                            <textarea name="fingerprint_data" id="fingerprint-data" class="form-control" rows="3" readonly placeholder="Fingerprint data will appear here after scanning..."></textarea>
                                            <small class="text-muted">This field will be populated automatically after fingerprint scanning.</small>
                                        </div>
                                    </div>
                                </div>

                                <div id="fingerprint-scanner" class="d-none">
                                    <div class="border rounded p-3 bg-light">
                                        <h6>Fingerprint Scanner</h6>
                                        <div class="text-center mb-3">
                                            <div id="scanner-status" class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle"></i> Click "Start Scanning" to begin fingerprint registration
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="button" id="start-scan-btn" class="btn btn-success me-2">
                                                <i class="fas fa-play"></i> Start Scanning
                                            </button>
                                            <button type="button" id="stop-scan-btn" class="btn btn-danger" disabled>
                                                <i class="fas fa-stop"></i> Stop Scanning
                                            </button>
                                        </div>
                                        <div class="mt-3">
                                            <div class="progress d-none" id="scan-progress">
                                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <a href="<?php echo e(route('admin.branch-heads.index')); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let fingerprintCheckInterval = null;

    const registerFingerprintBtn = document.getElementById('register-fingerprint-btn');
    const fingerprintScanner = document.getElementById('fingerprint-scanner');
    const fingerprintData = document.getElementById('fingerprint-data');
    const fingerprintStatus = document.getElementById('fingerprint-status');
    const startScanBtn = document.getElementById('start-scan-btn');
    const stopScanBtn = document.getElementById('stop-scan-btn');
    const scannerStatus = document.getElementById('scanner-status');

    function startFingerprintPolling(employeeNumber) {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
        }

        const employeeNumberForPolling = String(employeeNumber || '').trim();

        fingerprintCheckInterval = setInterval(async function() {
            try {
                let url = '/api/fingerprint-temp?employee_number=' + encodeURIComponent(employeeNumberForPolling);
                const response = await fetch(url);

                if (!response.ok) {
                    return;
                }

                const result = await response.json();

                if (result.success && result.data && result.data.fingerprint_data) {
                    const incomingData = result.data.fingerprint_data;
                    const incomingEmpNumber = String(result.data.employee_number || '').trim();

                    if (!incomingData || incomingData.trim().length < 100) {
                        return;
                    }

                    if (incomingEmpNumber.toLowerCase() === employeeNumberForPolling.toLowerCase()) {
                        clearInterval(fingerprintCheckInterval);
                        fingerprintCheckInterval = null;
                        fingerprintData.value = incomingData;

                        if (fingerprintStatus) {
                            fingerprintStatus.classList.remove('d-none');
                            fingerprintStatus.className = 'badge bg-success';
                            fingerprintStatus.innerHTML = '<i class="fas fa-check-circle"></i> Registered ✓';
                        }

                        if (scannerStatus) {
                            scannerStatus.className = 'alert alert-success';
                            scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Fingerprint matched! Click "Register Again" to rescan if needed.';
                        }

                        if (registerFingerprintBtn) {
                            registerFingerprintBtn.disabled = false;
                            registerFingerprintBtn.innerHTML = '<i class="fas fa-redo"></i> Register Again';
                        }
                    }
                }
            } catch (error) {
                console.log('Polling for fingerprint data...', error);
            }
        }, 300);
    }

    function launchEnrollmentApp() {
        fingerprintData.value = '';

        if (fingerprintStatus) {
            fingerprintStatus.classList.add('d-none');
        }

        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = '<?php echo e(optional($branchHead->user)->email ?? ''); ?>';
        const employeeNumber = '<?php echo e($branchHead->employee_number ?? ''); ?>';
        const role = 'admin';
        const branchName = '<?php echo e(optional($branchHead->branch)->branch_name ?? ''); ?>';
        const position = document.getElementById('position').value.trim();

        if (!firstName || !lastName) {
            alert('Please fill in First Name and Last Name before launching fingerprint enrollment.');
            return;
        }

        const uri = 'mtcgs-enroll:/register' +
            '?first_name=' + encodeURIComponent(firstName) +
            '&last_name=' + encodeURIComponent(lastName) +
            '&employee_number=' + encodeURIComponent(employeeNumber) +
            '&email=' + encodeURIComponent(email) +
            '&role=' + encodeURIComponent(role) +
            '&branch=' + encodeURIComponent(branchName) +
            '&position=' + encodeURIComponent(position);

        if (scannerStatus) {
            scannerStatus.className = 'alert alert-info';
            scannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Launching fingerprint enrollment app...';
        }

        if (fingerprintScanner) {
            fingerprintScanner.classList.remove('d-none');
        }

        startFingerprintPolling(employeeNumber);

        try {
            window.location.href = uri;

            if (fingerprintStatus) {
                fingerprintStatus.className = 'badge bg-warning';
                fingerprintStatus.innerHTML = '<i class="fas fa-external-link-alt"></i> App Launched';
                fingerprintStatus.classList.remove('d-none');
            }

            if (registerFingerprintBtn) {
                registerFingerprintBtn.disabled = false;
                registerFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
            }

            if (scannerStatus) {
                scannerStatus.className = 'alert alert-info';
                scannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for fingerprint capture from desktop app...';
            }
        } catch (error) {
            console.error('Fingerprint launch error:', error);
            alert('Unable to launch the fingerprint enrollment app.');
        }
    }

    if (registerFingerprintBtn) {
        registerFingerprintBtn.addEventListener('click', function () {
            launchEnrollmentApp();
        });
    }

    if (startScanBtn) {
        startScanBtn.addEventListener('click', function () {
            launchEnrollmentApp();
        });
    }

    if (stopScanBtn) {
        stopScanBtn.addEventListener('click', function () {
            if (fingerprintCheckInterval) {
                clearInterval(fingerprintCheckInterval);
                fingerprintCheckInterval = null;
            }

            if (scannerStatus) {
                scannerStatus.className = 'alert alert-secondary';
                scannerStatus.innerHTML = '<i class="fas fa-stop"></i> Enrollment app launch cancelled.';
            }
        });
    }

    function resumeFingerprintPolling() {
        if (!fingerprintData.value.trim() && !fingerprintCheckInterval && !document.hidden) {
            const employeeNumber = '<?php echo e($branchHead->employee_number ?? ''); ?>';
            startFingerprintPolling(employeeNumber);
        }
    }

    window.addEventListener('focus', resumeFingerprintPolling);
    document.addEventListener('visibilitychange', resumeFingerprintPolling);

    window.addEventListener('beforeunload', function() {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
            fingerprintCheckInterval = null;
        }
    });

    document.querySelector('form')?.addEventListener('submit', function() {
        if (fingerprintCheckInterval) {
            clearInterval(fingerprintCheckInterval);
            fingerprintCheckInterval = null;
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\branch-heads\edit.blade.php ENDPATH**/ ?>