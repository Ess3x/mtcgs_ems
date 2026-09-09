<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i> Attendance Recording Demo
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Demo Mode:</strong> This simulates the attendance clock-in/clock-out process using fingerprint verification.
                </div>

                <!-- Attendance Terminal Simulation -->
                <div class="card border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-desktop"></i> Attendance Terminal
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center mb-3">
                                    <div class="bg-dark text-white rounded p-4 d-inline-block position-relative">
                                        <i class="fas fa-hand-paper fa-4x text-light"></i>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <div id="scanner-ring" class="rounded-circle border border-success" style="width: 120px; height: 120px; opacity: 0;"></div>
                                        </div>
                                        <p class="mt-2 mb-0 text-light">Fingerprint Scanner</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Terminal Status</label>
                                    <div id="terminal-status" class="alert alert-secondary">
                                        <i class="fas fa-power-off"></i> Terminal ready. Select employee and attendance type.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Select Employee</label>
                                    <select class="form-control" id="demo-att-employee-select">
                                        <option value="">Choose employee...</option>
                                        <option value="EMP001">Juan Dela Cruz (EMP001)</option>
                                        <option value="EMP002">Maria Santos (EMP002)</option>
                                        <option value="EMP003">Pedro Reyes (EMP003)</option>
                                        <option value="EMP004">Ana Garcia (EMP004)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Attendance Type</label>
                                    <select class="form-control" id="demo-att-type-select">
                                        <option value="am_in">AM Time-In</option>
                                        <option value="am_out">AM Time-Out (Lunch)</option>
                                        <option value="pm_in">PM Time-In</option>
                                        <option value="pm_out">PM Time-Out</option>
                                    </select>
                                </div>

                                <div class="text-center">
                                    <button type="button" id="demo-att-scan-btn" class="btn btn-success btn-lg" disabled>
                                        <i class="fas fa-fingerprint"></i> Scan Fingerprint
                                    </button>
                                    <button type="button" id="demo-att-reset-btn" class="btn btn-secondary btn-lg ms-2">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Selection -->
                <div class="mb-3">
                    <label class="form-label">Branch</label>
                    <select class="form-control" id="demo-att-branch-select">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Log -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Attendance Log
                </h5>
            </div>
            <div class="card-body">
                <div id="demo-att-log" class="mb-3">
                    <div class="text-center text-muted">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <p>No attendance records yet</p>
                    </div>
                </div>

                <div id="demo-att-results" class="d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Attendance Recorded!</h6>
                        <div id="demo-att-result-info"></div>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="demo-att-next-btn" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-arrow-right"></i> Next: Try Notifications
                        </button>
                    </div>
                </div>

                <!-- Today's Summary -->
                <div class="mt-4">
                    <h6 class="text-muted">Today's Summary:</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-success" id="demo-att-today-present">0</div>
                                <small class="text-muted">Present</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-warning" id="demo-att-today-late">0</div>
                                <small class="text-muted">Late</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Attendance Demo Variables
let demoAttScanning = false;
let demoAttScanTimeout;
let demoAttLog = [];
let demoAttTodayStats = { present: 0, late: 0 };

// DOM Elements
const demoAttEmployeeSelect = document.getElementById('demo-att-employee-select');
const demoAttTypeSelect = document.getElementById('demo-att-type-select');
const demoAttBranchSelect = document.getElementById('demo-att-branch-select');
const demoAttScanBtn = document.getElementById('demo-att-scan-btn');
const demoAttResetBtn = document.getElementById('demo-att-reset-btn');
const terminalStatus = document.getElementById('terminal-status');
const scannerRing = document.getElementById('scanner-ring');
const demoAttLogDiv = document.getElementById('demo-att-log');
const demoAttResults = document.getElementById('demo-att-results');
const demoAttResultInfo = document.getElementById('demo-att-result-info');
const demoAttNextBtn = document.getElementById('demo-att-next-btn');
const demoAttTodayPresent = document.getElementById('demo-att-today-present');
const demoAttTodayLate = document.getElementById('demo-att-today-late');

// Employee/Attendance Type Change
demoAttEmployeeSelect.addEventListener('change', updateScanButton);
demoAttTypeSelect.addEventListener('change', updateScanButton);

function updateScanButton() {
    const employee = demoAttEmployeeSelect.value;
    const attType = demoAttTypeSelect.value;
    demoAttScanBtn.disabled = !employee || !attType;
}

// Scan Button Click
demoAttScanBtn.addEventListener('click', function() {
    demoAttStartScan();
});

// Reset Button Click
demoAttResetBtn.addEventListener('click', function() {
    demoAttReset();
});

// Next Button Click
demoAttNextBtn.addEventListener('click', function() {
    const notificationTab = document.getElementById('notification-tab');
    notificationTab.click();
});

// Start Fingerprint Scan for Attendance
function demoAttStartScan() {
    if (demoAttScanning) return;

    demoAttScanning = true;
    demoAttScanBtn.disabled = true;

    terminalStatus.className = 'alert alert-warning';
    terminalStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning fingerprint... Please place finger on scanner.';

    // Animate scanner ring
    scannerRing.style.opacity = '1';
    scannerRing.style.animation = 'ping 1s infinite';

    // Simulate scan delay
    demoAttScanTimeout = setTimeout(() => {
        demoAttCompleteScan();
    }, 3000);
}

// Complete Attendance Scan
function demoAttCompleteScan() {
    demoAttScanning = false;
    clearTimeout(demoAttScanTimeout);

    scannerRing.style.opacity = '0';
    scannerRing.style.animation = 'none';

    const employeeSelect = demoAttEmployeeSelect;
    const employeeName = employeeSelect.options[employeeSelect.selectedIndex].text;
    const employeeNumber = employeeSelect.value;
    const attType = demoAttTypeSelect.value;
    const branchSelect = demoAttBranchSelect;
    const branchName = branchSelect.options[branchSelect.selectedIndex].text;

    // Simulate API call
    fetch('{{ route("admin.unified-demo.attendance") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            employee_name: employeeName,
            employee_number: employeeNumber,
            attendance_type: attType,
            branch_name: branchName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            demoAttRecordAttendance(data.data);
        } else {
            terminalStatus.className = 'alert alert-danger';
            terminalStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (data.message || 'Attendance recording failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Fallback for demo
        demoAttRecordAttendance({
            employee_name: employeeName,
            employee_number: employeeNumber,
            branch: branchName,
            attendance_type: attType,
            timestamp: new Date().toLocaleString()
        });
    });
}

// Record Attendance
function demoAttRecordAttendance(data) {
    terminalStatus.className = 'alert alert-success';
    terminalStatus.innerHTML = '<i class="fas fa-check-circle"></i> Attendance recorded successfully!';

    // Add to log
    const logEntry = {
        id: Date.now(),
        employee: data.employee_name,
        type: data.attendance_type.replace('_', ' ').toUpperCase(),
        time: data.timestamp,
        branch: data.branch
    };

    demoAttLog.unshift(logEntry);
    demoAttUpdateLog();

    // Update stats
    demoAttTodayStats.present++;
    if (data.attendance_type === 'am_in' && new Date().getHours() > 8) {
        demoAttTodayStats.late++;
    }
    demoAttUpdateStats();

    // Show results
    demoAttResults.classList.remove('d-none');
    demoAttResultInfo.innerHTML = `
        <strong>Employee:</strong> ${data.employee_name}<br>
        <strong>Type:</strong> ${data.attendance_type.replace('_', ' ').toUpperCase()}<br>
        <strong>Time:</strong> ${data.timestamp}<br>
        <strong>Branch:</strong> ${data.branch}<br>
        <strong>Status:</strong> <span class="badge bg-success">Verified</span>
    `;

    // Update demo status
    updateDemoStatus('attendance', 'completed');

    // Re-enable scan button after delay
    setTimeout(() => {
        demoAttScanBtn.disabled = false;
        terminalStatus.className = 'alert alert-info';
        terminalStatus.innerHTML = '<i class="fas fa-info-circle"></i> Ready for next scan.';
    }, 2000);
}

// Update Attendance Log Display
function demoAttUpdateLog() {
    if (demoAttLog.length === 0) return;

    let html = '<div class="list-group">';
    demoAttLog.slice(0, 5).forEach(entry => {
        html += `
            <div class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${entry.employee}</h6>
                    <small>${entry.time}</small>
                </div>
                <p class="mb-1">${entry.type} - ${entry.branch}</p>
            </div>
        `;
    });
    html += '</div>';

    demoAttLogDiv.innerHTML = html;
}

// Update Statistics
function demoAttUpdateStats() {
    demoAttTodayPresent.textContent = demoAttTodayStats.present;
    demoAttTodayLate.textContent = demoAttTodayStats.late;
}

// Reset Demo
function demoAttReset() {
    demoAttScanning = false;
    clearTimeout(demoAttScanTimeout);

    demoAttEmployeeSelect.value = '';
    demoAttTypeSelect.value = 'am_in';
    demoAttScanBtn.disabled = true;

    terminalStatus.className = 'alert alert-secondary';
    terminalStatus.innerHTML = '<i class="fas fa-power-off"></i> Terminal ready. Select employee and attendance type.';

    scannerRing.style.opacity = '0';
    scannerRing.style.animation = 'none';

    demoAttResults.classList.add('d-none');

    // Reset demo status
    updateDemoStatus('attendance', 'active');
}

// Initialize
updateScanButton();
</script>

<style>
@keyframes ping {
    0% { transform: scale(1); opacity: 1; }
    75%, 100% { transform: scale(1.2); opacity: 0; }
}
</style>