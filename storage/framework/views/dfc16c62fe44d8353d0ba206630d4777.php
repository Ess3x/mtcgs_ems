<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus"></i> Employee Creation Demo
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Demo Mode:</strong> This simulates the employee creation process with fingerprint registration.
                </div>

                <form id="employee-demo-form">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="demo-emp-first-name" value="Juan" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="demo-emp-last-name" value="Dela Cruz" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="demo-emp-email" value="juan.delacruz@mtcgs.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Number</label>
                            <input type="text" class="form-control" id="demo-emp-number" value="EMP001" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" id="demo-emp-position" value="Software Developer" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Branch</label>
                            <select class="form-control" id="demo-emp-branch" required>
                                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->branch_name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" id="demo-emp-role" required>
                                <option value="employee">Employee</option>
                                <option value="finance_officer">Finance Officer</option>
                                <option value="finance_head">Finance Head</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Basic Salary</label>
                            <input type="number" step="0.01" class="form-control" id="demo-emp-salary" value="25000.00">
                        </div>
                    </div>

                    <!-- Fingerprint Registration Section -->
                    <div class="card border-warning mt-4 mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-fingerprint"></i> Fingerprint Registration (Required)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fingerprint Status</label>
                                        <div class="d-flex align-items-center">
                                            <span id="demo-emp-fingerprint-status" class="badge bg-danger me-2">
                                                <i class="fas fa-times"></i> Not Registered
                                            </span>
                                            <button type="button" id="demo-emp-register-fingerprint-btn" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-fingerprint"></i> Register Fingerprint
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fingerprint Data</label>
                                        <textarea class="form-control" id="demo-emp-fingerprint-data" rows="2" readonly placeholder="Fingerprint data will appear here..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Demo Scanner Interface -->
                            <div id="demo-emp-fingerprint-scanner" class="d-none">
                                <div class="border rounded p-3 bg-light">
                                    <h6>Demo Fingerprint Scanner</h6>
                                    <div class="text-center mb-3">
                                        <div id="demo-emp-scanner-status" class="alert alert-info">
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
                                        <button type="button" id="demo-emp-start-scan-btn" class="btn btn-success me-2">
                                            <i class="fas fa-play"></i> Start Demo Scan
                                        </button>
                                        <button type="button" id="demo-emp-stop-scan-btn" class="btn btn-danger" disabled>
                                            <i class="fas fa-stop"></i> Stop Scan
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <div class="progress d-none" id="demo-emp-scan-progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" id="demo-emp-reset-btn" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Demo
                        </button>
                        <button type="button" id="demo-emp-create-btn" class="btn btn-primary" disabled>
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
                <div id="demo-emp-results" class="d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-user-check"></i> Employee Created Successfully!</h6>
                        <div id="demo-emp-employee-info"></div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-envelope"></i> Email Notification Sent</h6>
                        <p class="mb-0 small">Account credentials would be sent to: <span id="demo-emp-email-sent"></span></p>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-fingerprint"></i> Fingerprint Registered</h6>
                        <p class="mb-0 small">Fingerprint data stored for attendance tracking</p>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="demo-emp-next-btn" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-arrow-right"></i> Next: Try Attendance Demo
                        </button>
                    </div>
                </div>

                <div id="demo-emp-placeholder" class="text-center text-muted">
                    <i class="fas fa-play-circle fa-3x mb-3"></i>
                    <p>Complete the employee creation demo to see results</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Employee Demo Variables
let demoEmpIsScanning = false;
let demoEmpScanInterval;

// DOM Elements
const demoEmpRegisterFingerprintBtn = document.getElementById('demo-emp-register-fingerprint-btn');
const demoEmpFingerprintScanner = document.getElementById('demo-emp-fingerprint-scanner');
const demoEmpFingerprintData = document.getElementById('demo-emp-fingerprint-data');
const demoEmpFingerprintStatus = document.getElementById('demo-emp-fingerprint-status');
const demoEmpStartScanBtn = document.getElementById('demo-emp-start-scan-btn');
const demoEmpStopScanBtn = document.getElementById('demo-emp-stop-scan-btn');
const demoEmpScannerStatus = document.getElementById('demo-emp-scanner-status');
const demoEmpScanProgress = document.getElementById('demo-emp-scan-progress');
const demoEmpProgressBar = demoEmpScanProgress.querySelector('.progress-bar');
const demoEmpCreateBtn = document.getElementById('demo-emp-create-btn');
const demoEmpResetBtn = document.getElementById('demo-emp-reset-btn');
const demoEmpResults = document.getElementById('demo-emp-results');
const demoEmpPlaceholder = document.getElementById('demo-emp-placeholder');
const demoEmpEmployeeInfo = document.getElementById('demo-emp-employee-info');
const demoEmpEmailSent = document.getElementById('demo-emp-email-sent');
const demoEmpNextBtn = document.getElementById('demo-emp-next-btn');

// Register Fingerprint Button Click
demoEmpRegisterFingerprintBtn.addEventListener('click', function() {
    demoEmpFingerprintScanner.classList.toggle('d-none');
    if (!demoEmpFingerprintScanner.classList.contains('d-none')) {
        demoEmpRegisterFingerprintBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Demo';
        demoEmpRegisterFingerprintBtn.classList.remove('btn-outline-primary');
        demoEmpRegisterFingerprintBtn.classList.add('btn-outline-danger');
    } else {
        demoEmpRegisterFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
        demoEmpRegisterFingerprintBtn.classList.remove('btn-outline-danger');
        demoEmpRegisterFingerprintBtn.classList.add('btn-outline-primary');
        demoEmpStopScanning();
    }
});

// Start Demo Scan Button Click
demoEmpStartScanBtn.addEventListener('click', function() {
    demoEmpStartScanning();
});

// Stop Scan Button Click
demoEmpStopScanBtn.addEventListener('click', function() {
    demoEmpStopScanning();
});

// Reset Demo Button Click
demoEmpResetBtn.addEventListener('click', function() {
    demoEmpReset();
});

// Create Employee Demo Button Click
demoEmpCreateBtn.addEventListener('click', function() {
    demoEmpCreateEmployee();
});

// Next Button Click
demoEmpNextBtn.addEventListener('click', function() {
    // Switch to attendance tab
    const attendanceTab = document.getElementById('attendance-tab');
    attendanceTab.click();
});

// Start Demo Fingerprint Scanning
function demoEmpStartScanning() {
    if (demoEmpIsScanning) return;

    demoEmpIsScanning = true;
    demoEmpStartScanBtn.disabled = true;
    demoEmpStopScanBtn.disabled = false;
    demoEmpScanProgress.classList.remove('d-none');
    demoEmpProgressBar.style.width = '0%';

    demoEmpScannerStatus.className = 'alert alert-warning';
    demoEmpScannerStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning fingerprint... Please place finger on scanner (demo).';

    let progress = 0;
    demoEmpScanInterval = setInterval(() => {
        progress += 8;
        demoEmpProgressBar.style.width = progress + '%';

        if (progress >= 100) {
            demoEmpCompleteScanning();
        }
    }, 400);
}

// Stop Demo Scanning
function demoEmpStopScanning() {
    if (!demoEmpIsScanning) return;

    demoEmpIsScanning = false;
    clearInterval(demoEmpScanInterval);
    demoEmpStartScanBtn.disabled = false;
    demoEmpStopScanBtn.disabled = true;
    demoEmpScanProgress.classList.add('d-none');

    demoEmpScannerStatus.className = 'alert alert-secondary';
    demoEmpScannerStatus.innerHTML = '<i class="fas fa-stop"></i> Demo scanning stopped.';
}

// Complete Demo Scanning
function demoEmpCompleteScanning() {
    clearInterval(demoEmpScanInterval);
    demoEmpIsScanning = false;

    // Generate demo fingerprint data
    const timestamp = Date.now();
    const randomData = 'demo_fingerprint_' + timestamp + '_' + Math.random().toString(36).substring(2, 15);
    const fingerprintBase64 = btoa(randomData);

    // Set fingerprint data
    demoEmpFingerprintData.value = fingerprintBase64.substring(0, 50) + '...';

    // Update status
    demoEmpFingerprintStatus.className = 'badge bg-success';
    demoEmpFingerprintStatus.innerHTML = '<i class="fas fa-check"></i> Registered';

    // Update scanner status
    demoEmpScannerStatus.className = 'alert alert-success';
    demoEmpScannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Fingerprint successfully registered!';

    // Enable create button
    demoEmpCreateBtn.disabled = false;

    // Disable buttons
    demoEmpStartScanBtn.disabled = true;
    demoEmpStopScanBtn.disabled = true;

    // Hide scanner after success
    setTimeout(() => {
        demoEmpFingerprintScanner.classList.add('d-none');
        demoEmpRegisterFingerprintBtn.innerHTML = '<i class="fas fa-check"></i> Fingerprint Registered';
        demoEmpRegisterFingerprintBtn.classList.remove('btn-outline-danger');
        demoEmpRegisterFingerprintBtn.classList.add('btn-success');
        demoEmpRegisterFingerprintBtn.disabled = true;
    }, 2000);
}

// Demo Create Employee
function demoEmpCreateEmployee() {
    const firstName = document.getElementById('demo-emp-first-name').value;
    const lastName = document.getElementById('demo-emp-last-name').value;
    const email = document.getElementById('demo-emp-email').value;
    const employeeNumber = document.getElementById('demo-emp-number').value;
    const position = document.getElementById('demo-emp-position').value;
    const branchSelect = document.getElementById('demo-emp-branch');
    const branchName = branchSelect.options[branchSelect.selectedIndex].text;

    // Show results
    demoEmpPlaceholder.classList.add('d-none');
    demoEmpResults.classList.remove('d-none');

    demoEmpEmployeeInfo.innerHTML = `
        <strong>Name:</strong> ${firstName} ${lastName}<br>
        <strong>Employee Number:</strong> ${employeeNumber}<br>
        <strong>Position:</strong> ${position}<br>
        <strong>Branch:</strong> ${branchName}<br>
        <strong>Email:</strong> ${email}<br>
        <strong>Fingerprint:</strong> <span class="badge bg-success">Registered</span>
    `;

    demoEmpEmailSent.textContent = email;

    // Disable form
    demoEmpCreateBtn.disabled = true;
    demoEmpCreateBtn.innerHTML = '<i class="fas fa-check"></i> Employee Created (Demo)';

    // Update demo status
    updateDemoStatus('employee', 'completed');
    updateDemoStatus('fingerprint', 'completed');

    // Show success message
    setTimeout(() => {
        alert('Employee creation demo completed! Fingerprint has been registered.');
    }, 500);
}

// Reset Demo
function demoEmpReset() {
    // Reset form
    document.getElementById('demo-emp-form').reset();

    // Reset fingerprint section
    demoEmpFingerprintData.value = '';
    demoEmpFingerprintStatus.className = 'badge bg-danger';
    demoEmpFingerprintStatus.innerHTML = '<i class="fas fa-times"></i> Not Registered';
    demoEmpRegisterFingerprintBtn.innerHTML = '<i class="fas fa-fingerprint"></i> Register Fingerprint';
    demoEmpRegisterFingerprintBtn.classList.remove('btn-success', 'btn-outline-danger');
    demoEmpRegisterFingerprintBtn.classList.add('btn-outline-primary');
    demoEmpRegisterFingerprintBtn.disabled = false;

    // Hide scanner
    demoEmpFingerprintScanner.classList.add('d-none');

    // Reset buttons
    demoEmpStartScanBtn.disabled = false;
    demoEmpStopScanBtn.disabled = true;
    demoEmpCreateBtn.disabled = true;
    demoEmpCreateBtn.innerHTML = '<i class="fas fa-save"></i> Create Employee (Demo)';

    // Reset results
    demoEmpResults.classList.add('d-none');
    demoEmpPlaceholder.classList.remove('d-none');

    // Stop any running scans
    demoEmpStopScanning();

    // Reset demo status
    updateDemoStatus('employee', 'active');
    updateDemoStatus('fingerprint', 'pending');
}
</script><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\demo\employee-creation.blade.php ENDPATH**/ ?>