<?php $__env->startSection('title', 'Fingerprint Registration Demo'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-fingerprint text-primary"></i> Fingerprint Registration Demo
                    </h2>
                    <p class="text-muted mt-2">Interactive demo of the fingerprint registration process</p>
                </div>
                <div>
                    <a href="<?php echo e(route('admin.employees')); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Employees
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Instructions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> How It Works
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="fw-bold">1</span>
                                </div>
                                <h6 class="mt-2">Fill Employee Details</h6>
                                <p class="text-muted small">Admin fills out employee information in the creation form</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="fw-bold">2</span>
                                </div>
                                <h6 class="mt-2">Register Fingerprint</h6>
                                <p class="text-muted small">Employee places finger on scanner for registration</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="fw-bold">3</span>
                                </div>
                                <h6 class="mt-2">Create Account</h6>
                                <p class="text-muted small">Account is created with fingerprint data stored</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Interface -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-play-circle"></i> Interactive Demo
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Demo Form -->
                    <form id="demo-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="demo-first-name" value="Juan" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="demo-last-name" value="Dela Cruz" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="demo-email" value="juan.delacruz@example.com" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee Number</label>
                                <input type="text" class="form-control" id="demo-employee-number" value="EMP001" required>
                            </div>
                        </div>

                        <!-- Fingerprint Registration Section -->
                        <div class="card border-warning mt-4 mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-fingerprint"></i> Fingerprint Registration
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Demo Mode:</strong> This is a simulation. In real implementation, this would connect to an actual fingerprint scanner.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Fingerprint Status</label>
                                            <div class="d-flex align-items-center">
                                                <span id="demo-fingerprint-status" class="badge bg-danger me-2">
                                                    <i class="fas fa-times"></i> Not Registered
                                                </span>
                                                <button type="button" id="demo-register-fingerprint-btn" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-fingerprint"></i> Register Fingerprint
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Fingerprint Data</label>
                                            <textarea class="form-control" id="demo-fingerprint-data" rows="2" readonly placeholder="Fingerprint data will appear here..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Demo Scanner Interface -->
                                <div id="demo-fingerprint-scanner" class="d-none">
                                    <div class="border rounded p-3 bg-light">
                                        <h6>Demo Fingerprint Scanner</h6>
                                        <div class="text-center mb-3">
                                            <div id="demo-scanner-status" class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> Click "Start Demo Scan" to simulate fingerprint scanning
                                            </div>
                                        </div>
                                        <div class="text-center mb-3">
                                            <div class="bg-secondary rounded p-4 d-inline-block">
                                                <i class="fas fa-hand-paper fa-3x text-light"></i>
                                                <p class="mt-2 mb-0 text-light">Scanner Area</p>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="button" id="demo-start-scan-btn" class="btn btn-success me-2">
                                                <i class="fas fa-play"></i> Start Demo Scan
                                            </button>
                                            <button type="button" id="demo-stop-scan-btn" class="btn btn-danger" disabled>
                                                <i class="fas fa-stop"></i> Stop Scan
                                            </button>
                                        </div>
                                        <div class="mt-3">
                                            <div class="progress d-none" id="demo-scan-progress">
                                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" id="demo-reset-btn" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset Demo
                            </button>
                            <button type="button" id="demo-create-btn" class="btn btn-primary" disabled>
                                <i class="fas fa-save"></i> Create Employee (Demo)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Demo Results -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle"></i> Demo Results
                    </h5>
                </div>
                <div class="card-body">
                    <div id="demo-results" class="d-none">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-user-check"></i> Employee Created Successfully!</h6>
                            <div id="demo-employee-info"></div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-envelope"></i> Email Notification Sent</h6>
                            <p class="mb-0 small">Account credentials would be sent to: <span id="demo-email-sent"></span></p>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-fingerprint"></i> Fingerprint Registered</h6>
                            <p class="mb-0 small">Fingerprint data stored for attendance tracking</p>
                        </div>
                    </div>

                    <div id="demo-placeholder" class="text-center text-muted">
                        <i class="fas fa-play-circle fa-3x mb-3"></i>
                        <p>Complete the demo to see results</p>
                    </div>
                </div>
            </div>

            <!-- Technical Details -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-code"></i> Technical Details
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li><strong>Data Stored:</strong> Fingerprint template (base64)</li>
                        <li><strong>Database Field:</strong> fingerprint_template</li>
                        <li><strong>Status Field:</strong> is_fingerprint_registered</li>
                        <li><strong>Validation:</strong> Required for account creation</li>
                        <li><strong>Integration:</strong> Attendance API uses this data</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Demo Variables
    let demoIsScanning = false;
    let demoScanInterval;

    // DOM Elements
    const demoRegisterFingerprintBtn = document.getElementById('demo-register-fingerprint-btn');
    const demoFingerprintScanner = document.getElementById('demo-fingerprint-scanner');
    const demoFingerprintData = document.getElementById('demo-fingerprint-data');
    const demoFingerprintStatus = document.getElementById('demo-fingerprint-status');
    const demoStartScanBtn = document.getElementById('demo-start-scan-btn');
    const demoStopScanBtn = document.getElementById('demo-stop-scan-btn');
    const demoScannerStatus = document.getElementById('demo-scanner-status');
    const demoScanProgress = document.getElementById('demo-scan-progress');
    const demoProgressBar = demoScanProgress.querySelector('.progress-bar');
    const demoCreateBtn = document.getElementById('demo-create-btn');
    const demoResetBtn = document.getElementById('demo-reset-btn');
    const demoResults = document.getElementById('demo-results');
    const demoPlaceholder = document.getElementById('demo-placeholder');
    const demoEmployeeInfo = document.getElementById('demo-employee-info');
    const demoEmailSent = document.getElementById('demo-email-sent');

    // Register Fingerprint Button Click
    demoRegisterFingerprintBtn.addEventListener('click', function() {
        demoFingerprintScanner.classList.toggle('d-none');
        if (!demoFingerprintScanner.classList.contains('d-none')) {
            demoRegisterFingerprintBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Demo';
            demoRegisterFingerprintBtn.classList.remove('btn-outline-primary');
            demoRegisterFingerprintBtn.classList.add('btn-outline-danger');
        } else {
            demoRegisterFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
            demoRegisterFingerprintBtn.classList.remove('btn-outline-danger');
            demoRegisterFingerprintBtn.classList.add('btn-outline-primary');
            demoStopScanning();
        }
    });

    // Start Demo Scan Button Click
    demoStartScanBtn.addEventListener('click', function() {
        demoStartScanning();
    });

    // Stop Scan Button Click
    demoStopScanBtn.addEventListener('click', function() {
        demoStopScanning();
    });

    // Reset Demo Button Click
    demoResetBtn.addEventListener('click', function() {
        demoReset();
    });

    // Create Employee Demo Button Click
    demoCreateBtn.addEventListener('click', function() {
        demoCreateEmployee();
    });

    // Start Demo Fingerprint Scanning
    function demoStartScanning() {
        if (demoIsScanning) return;

        demoIsScanning = true;
        demoStartScanBtn.disabled = true;
        demoStopScanBtn.disabled = false;
        demoScanProgress.classList.remove('d-none');
        demoProgressBar.style.width = '0%';

        demoScannerStatus.className = 'alert alert-warning';
        demoScannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning fingerprint... Please place finger on scanner (demo).';

        let progress = 0;
        demoScanInterval = setInterval(() => {
            progress += 8;
            demoProgressBar.style.width = progress + '%';

            if (progress >= 100) {
                demoCompleteScanning();
            }
        }, 400);
    }

    // Stop Demo Scanning
    function demoStopScanning() {
        demoIsScanning = false;
        clearInterval(demoScanInterval);
        demoScanInterval = null;
        demoFingerprintData.value = '';
        demoFingerprintStatus.className = 'badge bg-danger';
        demoFingerprintStatus.innerHTML = '<i class="fas fa-times"></i> Not Registered';
        demoProgressBar.style.width = '0%';
        demoScanProgress.classList.add('d-none');
        demoStartScanBtn.disabled = false;
        demoStopScanBtn.disabled = true;
        demoCreateBtn.disabled = true;

        demoScannerStatus.className = 'alert alert-secondary';
        demoScannerStatus.innerHTML = '<i class="fas fa-stop"></i> Demo scanning cancelled and reset.';
    }

    // Complete Demo Scanning
    function demoCompleteScanning() {
        clearInterval(demoScanInterval);
        demoIsScanning = false;

        // Generate demo fingerprint data
        const timestamp = Date.now();
        const randomData = 'demo_fingerprint_' + timestamp + '_' + Math.random().toString(36).substring(2, 15);
        const fingerprintBase64 = btoa(randomData);

        // Set fingerprint data
        demoFingerprintData.value = fingerprintBase64.substring(0, 50) + '...'; // Truncate for display

        // Update status
        demoFingerprintStatus.className = 'badge bg-success';
        demoFingerprintStatus.innerHTML = '<i class="fas fa-check"></i> Registered';

        // Update scanner status
        demoScannerStatus.className = 'alert alert-success';
        demoScannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Fingerprint successfully registered!';

        // Enable create button
        demoCreateBtn.disabled = false;

        // Disable buttons
        demoStartScanBtn.disabled = true;
        demoStopScanBtn.disabled = true;

        // Hide scanner after success
        setTimeout(() => {
            demoFingerprintScanner.classList.add('d-none');
            demoRegisterFingerprintBtn.innerHTML = '<i class="fas fa-check"></i> Fingerprint Registered';
            demoRegisterFingerprintBtn.classList.remove('btn-outline-danger');
            demoRegisterFingerprintBtn.classList.add('btn-success');
            demoRegisterFingerprintBtn.disabled = true;
        }, 2000);
    }

    // Demo Create Employee
    function demoCreateEmployee() {
        const firstName = document.getElementById('demo-first-name').value;
        const lastName = document.getElementById('demo-last-name').value;
        const email = document.getElementById('demo-email').value;
        const employeeNumber = document.getElementById('demo-employee-number').value;

        // Show results
        demoPlaceholder.classList.add('d-none');
        demoResults.classList.remove('d-none');

        demoEmployeeInfo.innerHTML = `
            <strong>Name:</strong> ${firstName} ${lastName}<br>
            <strong>Employee Number:</strong> ${employeeNumber}<br>
            <strong>Email:</strong> ${email}<br>
            <strong>Fingerprint:</strong> <span class="badge bg-success">Registered</span>
        `;

        demoEmailSent.textContent = email;

        // Disable form
        demoCreateBtn.disabled = true;
        demoCreateBtn.innerHTML = '<i class="fas fa-check"></i> Employee Created (Demo)';

        // Show success message
        setTimeout(() => {
            alert('Demo completed! In real implementation, the employee account would be created with fingerprint data stored.');
        }, 500);
    }

    // Reset Demo
    function demoReset() {
        // Reset form
        document.getElementById('demo-form').reset();

        // Reset fingerprint section
        demoFingerprintData.value = '';
        demoFingerprintStatus.className = 'badge bg-danger';
        demoFingerprintStatus.innerHTML = '<i class="fas fa-times"></i> Not Registered';
        demoRegisterFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
        demoRegisterFingerprintBtn.classList.remove('btn-success', 'btn-outline-danger');
        demoRegisterFingerprintBtn.classList.add('btn-outline-primary');
        demoRegisterFingerprintBtn.disabled = false;

        // Hide scanner
        demoFingerprintScanner.classList.add('d-none');

        // Reset buttons
        demoStartScanBtn.disabled = false;
        demoStopScanBtn.disabled = true;
        demoCreateBtn.disabled = true;
        demoCreateBtn.innerHTML = '<i class="fas fa-save"></i> Create Employee (Demo)';

        // Reset results
        demoResults.classList.add('d-none');
        demoPlaceholder.classList.remove('d-none');

        // Stop any running scans
        demoStopScanning();
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\fingerprint-demo.blade.php ENDPATH**/ ?>