<?php $__env->startSection('title', 'Fingerprint Scanner'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1"><i class="fas fa-fingerprint text-primary me-2"></i> Fingerprint Scanner</h2>
                            <p class="mb-0 text-muted">Dedicated UI for Digital Persona U 4500 fingerprint scanning and registration.</p>
                        </div>
                        <div>
                            <span class="badge bg-info text-dark">Separate scanner screen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-microchip text-success me-2"></i> Scanner Control Panel</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="scanner-ring mx-auto mb-3">
                            <i class="fas fa-fingerprint fa-4x text-primary"></i>
                        </div>
                        <h4 id="scannerStatus" class="fw-bold">Device Disconnected</h4>
                        <p class="text-muted">Use the controls below to connect the Digital Persona U 4500 scanner and start fingerprint capture.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <button id="connectScannerBtn" type="button" class="btn btn-outline-primary w-100 py-3" onclick="connectScanner()">
                                <i class="fas fa-plug me-2"></i> Connect Device
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button id="startScanBtn" type="button" class="btn btn-primary w-100 py-3" onclick="startScan()" disabled>
                                <i class="fas fa-play me-2"></i> Start Scan
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button id="registerPrintBtn" type="button" class="btn btn-success w-100 py-3" onclick="registerFingerprint()" disabled>
                                <i class="fas fa-save me-2"></i> Register Print
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button id="cancelScanBtn" type="button" class="btn btn-outline-danger w-100 py-3" onclick="cancelScan()" disabled>
                                <i class="fas fa-times me-2"></i> Cancel Scan
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded shadow-sm">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Current Session</span>
                            <span id="sessionState" class="badge bg-secondary">Idle</span>
                        </div>
                        <div class="mb-2">
                            <strong>User:</strong> <span id="selectedUser">No user selected</span>
                        </div>
                        <div class="mb-2">
                            <strong>Branch:</strong> <span id="selectedBranch">N/A</span>
                        </div>
                        <div>
                            <strong>Last result:</strong> <span id="scanResult">Waiting for scanner</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle text-warning me-2"></i> Scanner Instructions</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Note:</strong> This screen is separate from biometric management and is designed for use with Digital Persona U 4500.
                    </div>
                    <ol class="ps-3">
                        <li>Connect the Digital Persona U 4500 device to the workstation.</li>
                        <li>Press <strong>Connect Device</strong> and wait for the scanner to initialize.</li>
                        <li>Select the employee or finance officer to register.</li>
                        <li>Press <strong>Start Scan</strong> and place the finger on the reader.</li>
                        <li>Confirm the captured fingerprint and press <strong>Register Print</strong>.</li>
                    </ol>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Selected personnel</label>
                        <select class="form-select" id="personnelSelect" onchange="updateSelectedPersonnel()">
                            <option value="">Choose a user</option>
                            <option value="employee|E123">Maria Santos — Employee</option>
                            <option value="finance|F102">Juan Dela Cruz — Finance Officer</option>
                            <option value="admin|A007">Anna Reyes — Branch Admin</option>
                        </select>
                    </div>

                    <div class="alert alert-secondary small mb-0">
                        <strong>Tip:</strong> Use this page only for scanner operations. Existing fingerprint registration workflows remain in Biometric Management.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scanner-ring {
    width: 200px;
    height: 200px;
    border: 3px dashed rgba(13, 110, 253, 0.25);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    animation: pulse-ring 2.5s infinite;
}

@keyframes pulse-ring {
    0% { transform: scale(1); opacity: 0.85; }
    50% { transform: scale(1.08); opacity: 0.55; }
    100% { transform: scale(1); opacity: 0.85; }
}
</style>

<script>
    let scannerConnected = false;
    let scanActive = false;

    function connectScanner() {
        scannerConnected = true;
        document.getElementById('scannerStatus').textContent = 'Device Connected';
        document.getElementById('scannerStatus').classList.remove('text-danger');
        document.getElementById('scannerStatus').classList.add('text-success');
        document.getElementById('startScanBtn').disabled = false;
        document.getElementById('registerPrintBtn').disabled = true;
        document.getElementById('cancelScanBtn').disabled = true;
        document.getElementById('sessionState').textContent = 'Connected';
        document.getElementById('scanResult').textContent = 'Ready to scan...';
    }

    function startScan() {
        if (!scannerConnected) return;
        scanActive = true;
        document.getElementById('sessionState').textContent = 'Scanning';
        document.getElementById('scanResult').textContent = 'Place finger on scanner...';
        document.getElementById('registerPrintBtn').disabled = true;
        document.getElementById('cancelScanBtn').disabled = false;

        setTimeout(() => {
            if (!scanActive) return;
            document.getElementById('scanResult').textContent = 'Fingerprint captured successfully';
            document.getElementById('registerPrintBtn').disabled = false;
        }, 2200);
    }

    function registerFingerprint() {
        if (!scanActive) return;
        document.getElementById('sessionState').textContent = 'Registering';
        document.getElementById('scanResult').textContent = 'Saving biometric template...';
        document.getElementById('registerPrintBtn').disabled = true;
        document.getElementById('cancelScanBtn').disabled = false;

        setTimeout(() => {
            document.getElementById('sessionState').textContent = 'Completed';
            document.getElementById('scanResult').textContent = 'Fingerprint registration successful';
            document.getElementById('cancelScanBtn').disabled = true;
            scanActive = false;
        }, 1800);
    }

    function cancelScan() {
        scanActive = false;
        document.getElementById('sessionState').textContent = 'Cancelled';
        document.getElementById('scanResult').textContent = 'Scan cancelled. Reconnect to start again.';
        document.getElementById('registerPrintBtn').disabled = true;
        document.getElementById('cancelScanBtn').disabled = true;
    }

    function updateSelectedPersonnel() {
        const select = document.getElementById('personnelSelect');
        const value = select.value;
        if (!value) {
            document.getElementById('selectedUser').textContent = 'No user selected';
            document.getElementById('selectedBranch').textContent = 'N/A';
            return;
        }
        const [type, code] = value.split('|');
        const name = select.options[select.selectedIndex].text;
        document.getElementById('selectedUser').textContent = name;
        document.getElementById('selectedBranch').textContent = type === 'employee' ? 'Employee Branch' : type === 'finance' ? 'Finance Branch' : 'Admin Branch';
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\biometric-scanner-original.blade.php ENDPATH**/ ?>