<?php $__env->startSection('title', 'Fingerprint Scanner - Time-In/Time-Out'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <!-- Left Side: Fingerprint Scanner Control -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                <div class="card-body p-4">
                    <!-- Fingerprint Icon & Status -->
                    <div class="text-center mb-4">
                        <div class="scanner-ring mx-auto mb-3">
                            <i class="fas fa-fingerprint fa-5x"></i>
                        </div>
                        <h4 id="scannerStatus" class="fw-bold">READY TO CAPTURE</h4>
                        <p class="text-white-50 mb-0">Place your finger on the scanner</p>
                    </div>

                    <!-- F2 Button for Login/Logout -->
                    <div class="mb-4">
                        <button id="timeClockBtn" class="btn btn-warning btn-lg w-100 fw-bold py-3" onclick="performTimeClockWithSimulation()">
                            <i class="fas fa-keyboard me-2"></i> F2 - TIME-IN / TIME-OUT
                        </button>
                    </div>

                    <!-- Employee Info Card -->
                    <div id="employeeInfoCard" class="card bg-white text-dark" style="display: none;">
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <i class="fas fa-user me-2"></i>
                                <span id="currentEmployeeName" class="fw-bold"></span>
                            </div>
                            <small id="currentEmployeePosition" class="text-muted"></small>
                            <div class="mt-2">
                                <span class="badge bg-success" id="actionBadge"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Clear Logs Button -->
                    <div class="mt-3">
                        <button class="btn btn-outline-light btn-sm w-100 mb-2" onclick="clearLogs()">
                            <i class="fas fa-trash me-2"></i> CLEAR LOGS
                        </button>
                        <button class="btn btn-outline-light btn-sm w-100" onclick="loadSampleData()">
                            <i class="fas fa-upload me-2"></i> LOAD SAMPLE
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: DTR Display -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-white py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1" id="dtrMonth">MAY. 2026 DTR</h4>
                            <p class="text-muted mb-0">Daily Time Record - Real-time Updates</p>
                        </div>
                        <div>
                            <input type="text" id="employeeSearchInput" class="form-control form-control-sm" placeholder="Search employee..." style="width: 200px;">
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- DTR Table Header -->
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th class="fw-bold text-dark">DATE</th>
                                    <th class="fw-bold text-dark">TIME-IN</th>
                                    <th class="fw-bold text-dark">OUT</th>
                                    <th class="fw-bold text-dark">IN</th>
                                    <th class="fw-bold text-dark">TIME-OUT</th>
                                    <th class="fw-bold text-dark">TOTAL</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <!-- DTR Info Banner -->
                    <div class="alert alert-info m-3 mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="dtrInfoText">Waiting DTR records. Press scan when employee is ready.</span>
                    </div>

                    <!-- Employee List -->
                    <div class="ps-3 pe-3">
                        <h6 class="fw-bold text-dark mb-3" style="font-size: 0.95rem;">
                            <i class="fas fa-users me-2"></i> IN/OUT EMPLOYEE(s): <span id="employeeCountBadge" class="badge bg-primary">0</span>
                        </h6>

                        <!-- Employee Letter Buttons -->
                        <div class="mb-3 pb-3" id="letterButtonsContainer">
                            <!-- Letter buttons will be generated here -->
                        </div>

                        <!-- Employees List -->
                        <div id="employeesList" class="border-top pt-3">
                            <!-- Employee items will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .scanner-ring {
        width: 150px;
        height: 150px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        animation: pulse-ring 2.5s infinite;
    }

    @keyframes pulse-ring {
        0% {
            transform: scale(1);
            opacity: 0.8;
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
            box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
        }
        100% {
            transform: scale(1);
            opacity: 0.8;
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
        }
    }

    .employee-item {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .employee-item:hover {
        background-color: #f8f9fa;
    }

    .employee-item.active {
        background-color: #e3f2fd;
        border-left: 4px solid #0d6efd;
    }

    .letter-btn {
        width: 36px;
        height: 36px;
        padding: 0;
        margin-right: 8px;
        margin-bottom: 8px;
        font-size: 0.85rem;
        font-weight: bold;
    }

    .letter-btn.active {
        background-color: #ffc107;
        color: #000;
    }

    #dtrMonth {
        color: #f0ad4e;
        font-weight: bold;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<script>
    let currentEmployeeData = null;
    let employeesData = [];

    // Initialize page
    document.addEventListener('DOMContentLoaded', function () {
        loadDTRData();
        setupKeyboardListener();
    });

    // Listen for F2 key to trigger time clock
    function setupKeyboardListener() {
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F2' || e.key === 'f2' || e.code === 'F2') {
                e.preventDefault();
                performTimeClockWithSimulation();
            }
        });
    }

    // Simulate fingerprint capture and process time-in/out
    async function performTimeClockWithSimulation() {
        document.getElementById('scannerStatus').textContent = 'SCANNING...';
        document.getElementById('timeClockBtn').disabled = true;

        // Simulate fingerprint capture delay
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Simulated fingerprint template (in real scenario, this comes from the device)
        const simulatedFingerprintData = generateSimulatedFingerprintData();

        try {
            const response = await fetch('/api/biometric/time-clock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    fingerprint_data: simulatedFingerprintData
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Show success
                document.getElementById('scannerStatus').textContent = 'SUCCESS!';
                document.getElementById('scannerStatus').classList.remove('text-danger');
                document.getElementById('scannerStatus').classList.add('text-success');

                // Show employee info
                displayEmployeeInfo(data.employee_name, data.action);

                // Reload DTR data
                await loadDTRData();

                // Reset after 3 seconds
                setTimeout(() => {
                    document.getElementById('scannerStatus').textContent = 'READY TO CAPTURE';
                    document.getElementById('scannerStatus').classList.remove('text-success');
                    document.getElementById('employeeInfoCard').style.display = 'none';
                    document.getElementById('timeClockBtn').disabled = false;
                }, 3000);
            } else {
                document.getElementById('scannerStatus').textContent = 'NOT RECOGNIZED';
                document.getElementById('scannerStatus').classList.add('text-danger');
                document.getElementById('timeClockBtn').disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('scannerStatus').textContent = 'ERROR';
            document.getElementById('timeClockBtn').disabled = false;
        }
    }

    // Generate simulated fingerprint data (in real app, this comes from biometric device)
    function generateSimulatedFingerprintData() {
        // This would normally come from the Digital Persona fingerprint scanner
        // For demo purposes, we'll use a sample template
        return 'FP_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }

    // Display employee info after time-in/out
    function displayEmployeeInfo(employeeName, action) {
        const card = document.getElementById('employeeInfoCard');
        document.getElementById('currentEmployeeName').textContent = employeeName;
        document.getElementById('actionBadge').textContent = action;
        document.getElementById('actionBadge').className = action === 'TIME-IN' ? 'badge bg-success' : 'badge bg-danger';
        card.style.display = 'block';
    }

    // Load DTR data from API
    async function loadDTRData() {
        try {
            const response = await fetch('/api/biometric/dtr-today');
            const data = await response.json();

            if (data.success) {
                const records = data.records || [];
                updateDTRDisplay(records);
                loadRecentEmployees();
            }
        } catch (error) {
            console.error('Error loading DTR:', error);
        }
    }

    // Update DTR display
    function updateDTRDisplay(records) {
        const dtrMonth = new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' }).toUpperCase();
        document.getElementById('dtrMonth').textContent = `${dtrMonth} DTR`;

        if (records.length > 0) {
            document.getElementById('dtrInfoText').textContent = `Showing DTR records. Total: ${records.length} employee(s)`;
        } else {
            document.getElementById('dtrInfoText').textContent = 'No DTR records yet. Press scan when employee is ready.';
        }
    }

    // Load recent employees from API
    async function loadRecentEmployees() {
        try {
            const response = await fetch('/api/biometric/recent-employees?limit=15');
            const data = await response.json();

            if (data.success) {
                employeesData = data.employees || [];
                displayEmployeesList(employeesData);
                generateLetterButtons(employeesData);
            }
        } catch (error) {
            console.error('Error loading employees:', error);
        }
    }

    // Display employees list
    function displayEmployeesList(employees) {
        const listContainer = document.getElementById('employeesList');
        const countBadge = document.getElementById('employeeCountBadge');

        countBadge.textContent = employees.length;

        if (employees.length === 0) {
            listContainer.innerHTML = '<p class="text-muted text-center py-4">No employee records yet.</p>';
            return;
        }

        listContainer.innerHTML = employees.map((emp, index) => `
            <div class="employee-item" onclick="filterByEmployee('${emp.employee_name}')">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong class="text-dark">${emp.employee_name}</strong>
                        <br>
                        <small class="text-muted">${emp.position}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${emp.status === 'late' ? 'warning' : 'success'}">${emp.last_action_time}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Generate letter filter buttons
    function generateLetterButtons(employees) {
        const letters = [...new Set(employees.map(emp => emp.employee_name.charAt(0).toUpperCase()))].sort();
        const container = document.getElementById('letterButtonsContainer');

        container.innerHTML = letters.map(letter => `
            <button class="btn btn-sm btn-outline-secondary letter-btn" onclick="filterByLetter('${letter}')">${letter}</button>
        `).join('');

        // Add "All" button
        const allBtn = document.createElement('button');
        allBtn.className = 'btn btn-sm btn-warning letter-btn active';
        allBtn.textContent = 'ALL';
        allBtn.onclick = () => displayEmployeesList(employeesData);
        container.insertAdjacentElement('afterbegin', allBtn);
    }

    // Filter by letter
    function filterByLetter(letter) {
        const filtered = employeesData.filter(emp => emp.employee_name.charAt(0).toUpperCase() === letter);
        displayEmployeesList(filtered);
        updateLetterButtons(letter);
    }

    // Filter by employee name
    function filterByEmployee(name) {
        const filtered = employeesData.filter(emp => emp.employee_name === name);
        displayEmployeesList(filtered);
    }

    // Update letter buttons active state
    function updateLetterButtons(letter) {
        document.querySelectorAll('.letter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent === letter) {
                btn.classList.add('active');
            }
        });
    }

    // Clear logs
    function clearLogs() {
        if (confirm('Are you sure you want to clear all logs?')) {
            document.getElementById('employeesList').innerHTML = '';
            document.getElementById('employeeCountBadge').textContent = '0';
            document.getElementById('dtrInfoText').textContent = 'Logs cleared. Press scan when employee is ready.';
        }
    }

    // Load sample data
    function loadSampleData() {
        const sampleEmployees = [
            { employee_number: '001', employee_name: 'LOQUIAS, Rizza T.', position: 'Assoc. Prof. V', last_action_time: '06:13:42 PM', status: 'present' },
            { employee_number: '002', employee_name: 'BENITEZ, Ian R', position: 'Faculty', last_action_time: '06:45:12 PM', status: 'present' },
            { employee_number: '003', employee_name: 'BUTTA, Shemaiah E.', position: 'Faculty', last_action_time: '08:00:30 PM', status: 'present' },
            { employee_number: '004', employee_name: 'BRUMA, Paul Anjay T.', position: 'Instructor 1', last_action_time: '05:20:22 PM', status: 'present' },
            { employee_number: '005', employee_name: 'TALANGAN, Jhumma M.', position: 'Assoc. Prof.', last_action_time: '06:25:10 PM', status: 'present' },
            { employee_number: '006', employee_name: 'AMOROSO, Mari Len A.', position: 'Professor', last_action_time: '05:58:00 PM', status: 'present' },
        ];

        employeesData = sampleEmployees;
        displayEmployeesList(sampleEmployees);
        generateLetterButtons(sampleEmployees);
        document.getElementById('employeeCountBadge').textContent = sampleEmployees.length;
        document.getElementById('dtrInfoText').textContent = `Sample data loaded. Total: ${sampleEmployees.length} employee(s)`;
    }

    // Search employees
    document.getElementById('employeeSearchInput')?.addEventListener('input', function (e) {
        const query = e.target.value.toLowerCase();
        const filtered = employeesData.filter(emp =>
            emp.employee_name.toLowerCase().includes(query) ||
            emp.employee_number.includes(query)
        );
        displayEmployeesList(filtered);
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\biometric-scanner-backup.blade.php ENDPATH**/ ?>