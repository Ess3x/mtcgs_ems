<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt"></i> Daily Time Record (DTR) Demo
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-dark">
                    <i class="fas fa-info-circle"></i>
                    <strong>Demo Mode:</strong> This demonstrates the DTR view where employees can see their attendance records and administrators can manage time records.
                </div>

                <!-- DTR Filters -->
                <div class="card border-dark mb-4">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-filter"></i> DTR Filters
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Select Employee</label>
                                    <select class="form-control" id="demo-dtr-employee-select">
                                        <option value="">All Employees</option>
                                        <option value="EMP001">Juan Dela Cruz (EMP001)</option>
                                        <option value="EMP002">Maria Santos (EMP002)</option>
                                        <option value="EMP003">Pedro Reyes (EMP003)</option>
                                        <option value="EMP004">Ana Garcia (EMP004)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Month</label>
                                    <select class="form-control" id="demo-dtr-month-select">
                                        <option value="current">Current Month</option>
                                        <option value="previous">Previous Month</option>
                                        <option value="2024-01">January 2024</option>
                                        <option value="2024-02">February 2024</option>
                                        <option value="2024-03">March 2024</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">View Type</label>
                                    <select class="form-control" id="demo-dtr-view-select">
                                        <option value="daily">Daily View</option>
                                        <option value="weekly">Weekly Summary</option>
                                        <option value="monthly">Monthly Summary</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="button" id="demo-dtr-generate-btn" class="btn btn-dark btn-lg">
                                <i class="fas fa-search"></i> Generate DTR
                            </button>
                            <button type="button" id="demo-dtr-export-btn" class="btn btn-outline-dark btn-lg ms-2">
                                <i class="fas fa-download"></i> Export PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- DTR Table -->
                <div id="demo-dtr-table-container" class="d-none">
                    <div class="card border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-table"></i> Daily Time Record - <span id="demo-dtr-title"></span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="demo-dtr-table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>AM In</th>
                                            <th>AM Out</th>
                                            <th>PM In</th>
                                            <th>PM Out</th>
                                            <th>Total Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="demo-dtr-table-body">
                                        <!-- DTR rows will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DTR Summary -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> DTR Summary
                </h5>
            </div>
            <div class="card-body">
                <div id="demo-dtr-summary" class="d-none">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-user"></i> Employee Summary</h6>
                        <div id="demo-dtr-employee-info"></div>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0 text-success" id="demo-dtr-total-days">0</div>
                                <small class="text-muted">Working Days</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0 text-primary" id="demo-dtr-total-hours">0.00</div>
                                <small class="text-muted">Total Hours</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>Attendance Breakdown:</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 text-success" id="demo-dtr-present-days">0</div>
                                    <small class="text-muted">Present</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 text-warning" id="demo-dtr-late-days">0</div>
                                    <small class="text-muted">Late</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 text-danger" id="demo-dtr-absent-days">0</div>
                                    <small class="text-muted">Absent</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h5 text-info" id="demo-dtr-leave-days">0</div>
                                    <small class="text-muted">Leave</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="demo-dtr-complete-btn" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-check"></i> Complete Demo Tour
                        </button>
                    </div>
                </div>

                <div id="demo-dtr-placeholder" class="text-center text-muted">
                    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                    <p>Select filters and generate DTR to see summary</p>
                </div>

                <!-- DTR Actions -->
                <div class="mt-4">
                    <h6 class="text-muted">Quick Actions:</h6>
                    <div class="d-grid gap-2">
                        <button type="button" id="demo-dtr-add-entry-btn" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Manual Entry
                        </button>
                        <button type="button" id="demo-dtr-edit-entry-btn" class="btn btn-outline-warning btn-sm" disabled>
                            <i class="fas fa-edit"></i> Edit Entry
                        </button>
                        <button type="button" id="demo-dtr-approve-btn" class="btn btn-outline-success btn-sm" disabled>
                            <i class="fas fa-check"></i> Approve DTR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// DTR Demo Variables
let demoDtrData = [];
let demoDtrSummary = {
    totalDays: 0,
    totalHours: 0,
    presentDays: 0,
    lateDays: 0,
    absentDays: 0,
    leaveDays: 0
};

// DOM Elements
const demoDtrEmployeeSelect = document.getElementById('demo-dtr-employee-select');
const demoDtrMonthSelect = document.getElementById('demo-dtr-month-select');
const demoDtrViewSelect = document.getElementById('demo-dtr-view-select');
const demoDtrGenerateBtn = document.getElementById('demo-dtr-generate-btn');
const demoDtrExportBtn = document.getElementById('demo-dtr-export-btn');
const demoDtrTableContainer = document.getElementById('demo-dtr-table-container');
const demoDtrTitle = document.getElementById('demo-dtr-title');
const demoDtrTableBody = document.getElementById('demo-dtr-table-body');
const demoDtrSummaryDiv = document.getElementById('demo-dtr-summary');
const demoDtrPlaceholder = document.getElementById('demo-dtr-placeholder');
const demoDtrEmployeeInfo = document.getElementById('demo-dtr-employee-info');
const demoDtrTotalDays = document.getElementById('demo-dtr-total-days');
const demoDtrTotalHours = document.getElementById('demo-dtr-total-hours');
const demoDtrPresentDays = document.getElementById('demo-dtr-present-days');
const demoDtrLateDays = document.getElementById('demo-dtr-late-days');
const demoDtrAbsentDays = document.getElementById('demo-dtr-absent-days');
const demoDtrLeaveDays = document.getElementById('demo-dtr-leave-days');
const demoDtrCompleteBtn = document.getElementById('demo-dtr-complete-btn');

// Generate Button Click
demoDtrGenerateBtn.addEventListener('click', function() {
    demoGenerateDTR();
});

// Export Button Click
demoDtrExportBtn.addEventListener('click', function() {
    alert('PDF export feature would be implemented here. In a real system, this would generate a PDF report.');
});

// Complete Button Click
demoDtrCompleteBtn.addEventListener('click', function() {
    alert('Demo tour completed! You have successfully explored all features of the MTCGS EMS system.');
    updateDemoStatus('dtr', 'completed');
});

// Generate DTR
function demoGenerateDTR() {
    const employee = demoDtrEmployeeSelect.value;
    const month = demoDtrMonthSelect.value;
    const view = demoDtrViewSelect.value;

    const employeeName = employee ? demoDtrEmployeeSelect.options[demoDtrEmployeeSelect.selectedIndex].text : 'All Employees';
    const monthName = demoDtrMonthSelect.options[demoDtrMonthSelect.selectedIndex].text;

    demoDtrTitle.textContent = `${employeeName} - ${monthName}`;

    // Generate sample DTR data
    demoDtrData = demoGenerateSampleDTR(employee, month, view);
    demoRenderDTRTable();
    demoCalculateSummary();

    demoDtrTableContainer.classList.remove('d-none');
    demoDtrSummaryDiv.classList.remove('d-none');
    demoDtrPlaceholder.classList.add('d-none');

    // Update demo status
    updateDemoStatus('dtr', 'active');
}

// Generate Sample DTR Data
function demoGenerateSampleDTR(employee, month, view) {
    const data = [];
    const daysInMonth = month === 'current' ? new Date().getDate() : 30;
    const startDay = month === 'current' ? 1 : 1;

    for (let day = startDay; day <= daysInMonth; day++) {
        const date = new Date();
        if (month !== 'current') {
            date.setMonth(month.split('-')[1] - 1);
        }
        date.setDate(day);

        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
        const isWeekend = dayName === 'Sat' || dayName === 'Sun';

        let amIn = '', amOut = '', pmIn = '', pmOut = '', totalHours = '', status = '';

        if (!isWeekend) {
            // Simulate attendance
            const present = Math.random() > 0.1; // 90% attendance rate
            if (present) {
                amIn = '08:00';
                amOut = '12:00';
                pmIn = '13:00';
                pmOut = '17:00';
                totalHours = '8.00';

                // Sometimes late
                if (Math.random() > 0.8) {
                    amIn = '08:30';
                    totalHours = '7.50';
                    status = 'Late';
                } else {
                    status = 'Present';
                }
            } else {
                status = Math.random() > 0.5 ? 'Absent' : 'Leave';
            }
        } else {
            status = 'Weekend';
        }

        data.push({
            date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            day: dayName,
            amIn: amIn,
            amOut: amOut,
            pmIn: pmIn,
            pmOut: pmOut,
            totalHours: totalHours,
            status: status
        });
    }

    return data;
}

// Render DTR Table
function demoRenderDTRTable() {
    let html = '';
    demoDtrData.forEach(entry => {
        const statusClass = entry.status === 'Present' ? 'table-success' :
                           entry.status === 'Late' ? 'table-warning' :
                           entry.status === 'Absent' ? 'table-danger' :
                           entry.status === 'Leave' ? 'table-info' : '';

        html += `
            <tr class="${statusClass}">
                <td>${entry.date}</td>
                <td>${entry.day}</td>
                <td>${entry.amIn}</td>
                <td>${entry.amOut}</td>
                <td>${entry.pmIn}</td>
                <td>${entry.pmOut}</td>
                <td>${entry.totalHours}</td>
                <td><span class="badge bg-${entry.status === 'Present' ? 'success' :
                                             entry.status === 'Late' ? 'warning' :
                                             entry.status === 'Absent' ? 'danger' :
                                             entry.status === 'Leave' ? 'info' : 'secondary'}">${entry.status}</span></td>
            </tr>
        `;
    });

    demoDtrTableBody.innerHTML = html;
}

// Calculate Summary
function demoCalculateSummary() {
    demoDtrSummary = {
        totalDays: 0,
        totalHours: 0,
        presentDays: 0,
        lateDays: 0,
        absentDays: 0,
        leaveDays: 0
    };

    demoDtrData.forEach(entry => {
        if (entry.status !== 'Weekend') {
            demoDtrSummary.totalDays++;
            if (entry.totalHours) {
                demoDtrSummary.totalHours += parseFloat(entry.totalHours);
            }

            if (entry.status === 'Present') demoDtrSummary.presentDays++;
            else if (entry.status === 'Late') demoDtrSummary.lateDays++;
            else if (entry.status === 'Absent') demoDtrSummary.absentDays++;
            else if (entry.status === 'Leave') demoDtrSummary.leaveDays++;
        }
    });

    // Update display
    const employee = demoDtrEmployeeSelect.value;
    const employeeName = employee ? demoDtrEmployeeSelect.options[demoDtrEmployeeSelect.selectedIndex].text : 'All Employees';

    demoDtrEmployeeInfo.innerHTML = `
        <strong>Name:</strong> ${employeeName}<br>
        <strong>Period:</strong> ${demoDtrMonthSelect.options[demoDtrMonthSelect.selectedIndex].text}
    `;

    demoDtrTotalDays.textContent = demoDtrSummary.totalDays;
    demoDtrTotalHours.textContent = demoDtrSummary.totalHours.toFixed(2);
    demoDtrPresentDays.textContent = demoDtrSummary.presentDays;
    demoDtrLateDays.textContent = demoDtrSummary.lateDays;
    demoDtrAbsentDays.textContent = demoDtrSummary.absentDays;
    demoDtrLeaveDays.textContent = demoDtrSummary.leaveDays;
}

// Initialize
demoGenerateDTR(); // Generate default DTR on load
</script><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\demo\dtr-view.blade.php ENDPATH**/ ?>