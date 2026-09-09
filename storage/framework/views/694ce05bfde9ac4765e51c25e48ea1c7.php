<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-fingerprint"></i> Fingerprint Registration Demo
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Demo Mode:</strong> This demonstrates the fingerprint registration process for existing employees.
                </div>

                <!-- Employee Selection -->
                <div class="mb-4">
                    <h6>Select Employee for Fingerprint Registration:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-control" id="demo-fp-employee-select">
                                <option value="">Choose an employee...</option>
                                <option value="EMP001">Juan Dela Cruz (EMP001)</option>
                                <option value="EMP002">Maria Santos (EMP002)</option>
                                <option value="EMP003">Pedro Reyes (EMP003)</option>
                                <option value="EMP004">Ana Garcia (EMP004)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center h-100">
                                <span id="demo-fp-employee-status" class="badge bg-secondary me-2">
                                    <i class="fas fa-question"></i> Select Employee
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fingerprint Scanner Interface -->
                <div id="demo-fp-scanner-section" class="d-none">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-hand-paper"></i> Fingerprint Scanner
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-center mb-3">
                                        <div class="bg-light border rounded p-4 d-inline-block">
                                            <i class="fas fa-hand-paper fa-4x text-secondary"></i>
                                            <p class="mt-2 mb-0">Place finger here</p>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button type="button" id="demo-fp-start-scan-btn" class="btn btn-success btn-lg">
                                            <i class="fas fa-play"></i> Start Scanning
                                        </button>
                                        <button type="button" id="demo-fp-stop-scan-btn" class="btn btn-danger btn-lg ms-2" disabled>
                                            <i class="fas fa-stop"></i> Stop
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Scan Status</label>
                                        <div id="demo-fp-scan-status" class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Ready to scan. Click "Start Scanning" to begin.
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Fingerprint Quality</label>
                                        <div class="progress">
                                            <div id="demo-fp-quality-bar" class="progress-bar bg-warning" style="width: 0%"></div>
                                        </div>
                                        <small class="text-muted" id="demo-fp-quality-text">Quality: 0%</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Fingerprint Data</label>
                                        <textarea class="form-control" id="demo-fp-data" rows="3" readonly placeholder="Fingerprint template will appear here..."></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Registration Status</label>
                                        <div id="demo-fp-reg-status" class="alert alert-secondary">
                                            <i class="fas fa-clock"></i> Waiting for fingerprint scan...
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" id="demo-fp-register-btn" class="btn btn-primary btn-lg" disabled>
                                    <i class="fas fa-save"></i> Register Fingerprint
                                </button>
                                <button type="button" id="demo-fp-reset-btn" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Results -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle"></i> Registration Results
                </h5>
            </div>
            <div class="card-body">
                <div id="demo-fp-results" class="d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-fingerprint"></i> Fingerprint Registered!</h6>
                        <div id="demo-fp-result-info"></div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-database"></i> Data Stored</h6>
                        <p class="mb-0 small">Fingerprint template saved to database for attendance verification</p>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-clock"></i> Ready for Attendance</h6>
                        <p class="mb-0 small">Employee can now use fingerprint for time-in/time-out</p>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="demo-fp-next-btn" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-arrow-right"></i> Next: Try Attendance Demo
                        </button>
                    </div>
                </div>

                <div id="demo-fp-placeholder" class="text-center text-muted">
                    <i class="fas fa-fingerprint fa-3x mb-3"></i>
                    <p>Select an employee and complete fingerprint registration to see results</p>
                </div>

                <!-- Technical Details -->
                <div class="mt-4">
                    <h6 class="text-muted">Technical Details:</h6>
                    <ul class="list-unstyled small">
                        <li><strong>Template Size:</strong> ~500 bytes</li>
                        <li><strong>Algorithm:</strong> Minutiae-based</li>
                        <li><strong>Storage:</strong> Base64 encoded</li>
                        <li><strong>Verification:</strong> 1:N matching</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fingerprint Demo Variables
let demoFpIsScanning = false;
let demoFpScanInterval;
let demoFpQuality = 0;

// DOM Elements
const demoFpEmployeeSelect = document.getElementById('demo-fp-employee-select');
const demoFpEmployeeStatus = document.getElementById('demo-fp-employee-status');
const demoFpScannerSection = document.getElementById('demo-fp-scanner-section');
const demoFpStartScanBtn = document.getElementById('demo-fp-start-scan-btn');
const demoFpStopScanBtn = document.getElementById('demo-fp-stop-scan-btn');
const demoFpScanStatus = document.getElementById('demo-fp-scan-status');
const demoFpQualityBar = document.getElementById('demo-fp-quality-bar');
const demoFpQualityText = document.getElementById('demo-fp-quality-text');
const demoFpData = document.getElementById('demo-fp-data');
const demoFpRegStatus = document.getElementById('demo-fp-reg-status');
const demoFpRegisterBtn = document.getElementById('demo-fp-register-btn');
const demoFpResetBtn = document.getElementById('demo-fp-reset-btn');
const demoFpResults = document.getElementById('demo-fp-results');
const demoFpPlaceholder = document.getElementById('demo-fp-placeholder');
const demoFpResultInfo = document.getElementById('demo-fp-result-info');
const demoFpNextBtn = document.getElementById('demo-fp-next-btn');

// Employee Selection Change
demoFpEmployeeSelect.addEventListener('change', function() {
    const selectedValue = this.value;
    if (selectedValue) {
        demoFpEmployeeStatus.className = 'badge bg-info';
        demoFpEmployeeStatus.innerHTML = '<i class="fas fa-user"></i> Selected';
        demoFpScannerSection.classList.remove('d-none');
        demoFpStartScanBtn.disabled = false;
    } else {
        demoFpEmployeeStatus.className = 'badge bg-secondary';
        demoFpEmployeeStatus.innerHTML = '<i class="fas fa-question"></i> Select Employee';
        demoFpScannerSection.classList.add('d-none');
        demoFpStartScanBtn.disabled = true;
    }
});

// Start Scan Button Click
demoFpStartScanBtn.addEventListener('click', function() {
    demoFpStartScanning();
});

// Stop Scan Button Click
demoFpStopScanBtn.addEventListener('click', function() {
    demoFpStopScanning();
});

// Register Button Click
demoFpRegisterBtn.addEventListener('click', function() {
    demoFpRegisterFingerprint();
});

// Reset Button Click
demoFpResetBtn.addEventListener('click', function() {
    demoFpReset();
});

// Next Button Click
demoFpNextBtn.addEventListener('click', function() {
    const attendanceTab = document.getElementById('attendance-tab');
    attendanceTab.click();
});

// Start Fingerprint Scanning
function demoFpStartScanning() {
    if (demoFpIsScanning) return;

    demoFpIsScanning = true;
    demoFpStartScanBtn.disabled = true;
    demoFpStopScanBtn.disabled = false;
    demoFpQuality = 0;

    demoFpScanStatus.className = 'alert alert-warning';
    demoFpScanStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning fingerprint... Place finger on scanner.';

    demoFpScanInterval = setInterval(() => {
        demoFpQuality += Math.random() * 15;
        if (demoFpQuality > 100) demoFpQuality = 100;

        demoFpQualityBar.style.width = demoFpQuality + '%';
        demoFpQualityText.textContent = 'Quality: ' + Math.round(demoFpQuality) + '%';

        if (demoFpQuality >= 80) {
            demoFpCompleteScanning();
        }
    }, 300);
}

// Stop Scanning
function demoFpStopScanning() {
    demoFpIsScanning = false;
    clearInterval(demoFpScanInterval);
    demoFpScanInterval = null;
    demoFpStartScanBtn.disabled = false;
    demoFpStopScanBtn.disabled = true;
    demoFpRegisterBtn.disabled = true;
    demoFpQuality = 0;
    demoFpQualityBar.style.width = '0%';
    demoFpQualityText.textContent = 'Quality: 0%';
    demoFpData.value = '';
    demoFpRegStatus.className = 'alert alert-secondary';
    demoFpRegStatus.innerHTML = '<i class="fas fa-clock"></i> Waiting for fingerprint scan...';

    demoFpScanStatus.className = 'alert alert-secondary';
    demoFpScanStatus.innerHTML = '<i class="fas fa-stop"></i> Scanning cancelled and reset.';
}

// Complete Scanning
function demoFpCompleteScanning() {
    clearInterval(demoFpScanInterval);
    demoFpIsScanning = false;

    demoFpStartScanBtn.disabled = true;
    demoFpStopScanBtn.disabled = true;

    demoFpScanStatus.className = 'alert alert-success';
    demoFpScanStatus.innerHTML = '<i class="fas fa-check-circle"></i> Fingerprint captured successfully!';

    demoFpRegStatus.className = 'alert alert-info';
    demoFpRegStatus.innerHTML = '<i class="fas fa-check"></i> Ready for registration. Click "Register Fingerprint" to save.';

    // Generate fingerprint data
    const timestamp = Date.now();
    const randomData = 'fp_template_' + timestamp + '_' + Math.random().toString(36).substring(2, 15);
    demoFpData.value = btoa(randomData).substring(0, 100) + '...';

    demoFpRegisterBtn.disabled = false;
}

// Register Fingerprint
function demoFpRegisterFingerprint() {
    const employeeSelect = document.getElementById('demo-fp-employee-select');
    const employeeName = employeeSelect.options[employeeSelect.selectedIndex].text;
    const employeeNumber = employeeSelect.value;

    demoFpRegStatus.className = 'alert alert-success';
    demoFpRegStatus.innerHTML = '<i class="fas fa-check-circle"></i> Fingerprint registered successfully!';

    demoFpRegisterBtn.disabled = true;
    demoFpRegisterBtn.innerHTML = '<i class="fas fa-check"></i> Registered';

    // Show results
    demoFpPlaceholder.classList.add('d-none');
    demoFpResults.classList.remove('d-none');

    demoFpResultInfo.innerHTML = `
        <strong>Employee:</strong> ${employeeName}<br>
        <strong>Registration Time:</strong> ${new Date().toLocaleString()}<br>
        <strong>Template ID:</strong> FP_${Date.now()}<br>
        <strong>Status:</strong> <span class="badge bg-success">Active</span>
    `;

    // Update demo status
    updateDemoStatus('fingerprint', 'completed');

    setTimeout(() => {
        alert('Fingerprint registration completed! The employee can now use fingerprint for attendance.');
    }, 500);
}

// Reset Demo
function demoFpReset() {
    demoFpEmployeeSelect.value = '';
    demoFpEmployeeStatus.className = 'badge bg-secondary';
    demoFpEmployeeStatus.innerHTML = '<i class="fas fa-question"></i> Select Employee';
    demoFpScannerSection.classList.add('d-none');

    demoFpStartScanBtn.disabled = true;
    demoFpStopScanBtn.disabled = true;
    demoFpRegisterBtn.disabled = true;
    demoFpRegisterBtn.innerHTML = '<i class="fas fa-save"></i> Register Fingerprint';

    demoFpScanStatus.className = 'alert alert-info';
    demoFpScanStatus.innerHTML = '<i class="fas fa-info-circle"></i> Ready to scan. Click "Start Scanning" to begin.';

    demoFpQualityBar.style.width = '0%';
    demoFpQualityText.textContent = 'Quality: 0%';
    demoFpData.value = '';

    demoFpRegStatus.className = 'alert alert-secondary';
    demoFpRegStatus.innerHTML = '<i class="fas fa-clock"></i> Waiting for fingerprint scan...';

    demoFpResults.classList.add('d-none');
    demoFpPlaceholder.classList.remove('d-none');

    demoFpStopScanning();

    // Reset demo status
    updateDemoStatus('fingerprint', 'active');
}
</script><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\demo\fingerprint-registration.blade.php ENDPATH**/ ?>