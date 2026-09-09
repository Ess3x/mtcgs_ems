<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bell"></i> Notifications Demo
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-primary">
                    <i class="fas fa-info-circle"></i>
                    <strong>Demo Mode:</strong> This demonstrates the real-time notifications and email alerts when attendance is recorded.
                </div>

                <!-- Notification Settings -->
                <div class="card border-info mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-cog"></i> Notification Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="demo-notif-email" checked>
                                    <label class="form-check-label" for="demo-notif-email">
                                        <i class="fas fa-envelope"></i> Email Notifications
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="demo-notif-realtime" checked>
                                    <label class="form-check-label" for="demo-notif-realtime">
                                        <i class="fas fa-bolt"></i> Real-time Notifications
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="demo-notif-sms" disabled>
                                    <label class="form-check-label" for="demo-notif-sms">
                                        <i class="fas fa-sms"></i> SMS Notifications <small class="text-muted">(Coming Soon)</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Test Employee</label>
                                    <select class="form-control" id="demo-notif-employee-select">
                                        <option value="">Select employee...</option>
                                        <option value="EMP001">Juan Dela Cruz (EMP001)</option>
                                        <option value="EMP002">Maria Santos (EMP002)</option>
                                        <option value="EMP003">Pedro Reyes (EMP003)</option>
                                        <option value="EMP004">Ana Garcia (EMP004)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notification Type</label>
                                    <select class="form-control" id="demo-notif-type-select">
                                        <option value="clock_in">Clock In</option>
                                        <option value="clock_out">Clock Out</option>
                                        <option value="late">Late Arrival</option>
                                        <option value="early_departure">Early Departure</option>
                                    </select>
                                </div>
                                <div class="text-center">
                                    <button type="button" id="demo-notif-send-btn" class="btn btn-primary btn-lg" disabled>
                                        <i class="fas fa-paper-plane"></i> Send Notification
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Preview -->
                <div id="demo-email-preview" class="card border-secondary d-none">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-envelope-open"></i> Email Preview
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3 bg-light">
                            <div class="mb-2">
                                <strong>To:</strong> <span id="demo-email-to"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Subject:</strong> <span id="demo-email-subject"></span>
                            </div>
                            <hr>
                            <div id="demo-email-body"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Feed -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-stream"></i> Notification Feed
                </h5>
            </div>
            <div class="card-body">
                <div id="demo-notif-feed" class="mb-3">
                    <div class="text-center text-muted">
                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                        <p>No notifications yet</p>
                    </div>
                </div>

                <div id="demo-notif-results" class="d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Notification Sent!</h6>
                        <div id="demo-notif-result-info"></div>
                    </div>

                    <div class="mt-3">
                        <button type="button" id="demo-notif-next-btn" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-arrow-right"></i> Next: Try DTR View
                        </button>
                    </div>
                </div>

                <!-- Notification Stats -->
                <div class="mt-4">
                    <h6 class="text-muted">Today's Notifications:</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-primary" id="demo-notif-today-email">0</div>
                                <small class="text-muted">Emails</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-success" id="demo-notif-today-realtime">0</div>
                                <small class="text-muted">Real-time</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Connection Status -->
                <div class="mt-3">
                    <h6 class="text-muted">Connection Status:</h6>
                    <div id="demo-connection-status" class="alert alert-info">
                        <i class="fas fa-wifi"></i> Connected to notification server
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Notification Demo Variables
let demoNotifFeed = [];
let demoNotifStats = { email: 0, realtime: 0 };

// DOM Elements
const demoNotifEmployeeSelect = document.getElementById('demo-notif-employee-select');
const demoNotifTypeSelect = document.getElementById('demo-notif-type-select');
const demoNotifSendBtn = document.getElementById('demo-notif-send-btn');
const demoNotifEmailCheck = document.getElementById('demo-notif-email');
const demoNotifRealtimeCheck = document.getElementById('demo-notif-realtime');
const demoEmailPreview = document.getElementById('demo-email-preview');
const demoEmailTo = document.getElementById('demo-email-to');
const demoEmailSubject = document.getElementById('demo-email-subject');
const demoEmailBody = document.getElementById('demo-email-body');
const demoNotifFeedDiv = document.getElementById('demo-notif-feed');
const demoNotifResults = document.getElementById('demo-notif-results');
const demoNotifResultInfo = document.getElementById('demo-notif-result-info');
const demoNotifNextBtn = document.getElementById('demo-notif-next-btn');
const demoNotifTodayEmail = document.getElementById('demo-notif-today-email');
const demoNotifTodayRealtime = document.getElementById('demo-notif-today-realtime');
const demoConnectionStatus = document.getElementById('demo-connection-status');

// Employee/Type Change
demoNotifEmployeeSelect.addEventListener('change', updateSendButton);
demoNotifTypeSelect.addEventListener('change', updateSendButton);

function updateSendButton() {
    const employee = demoNotifEmployeeSelect.value;
    const type = demoNotifTypeSelect.value;
    demoNotifSendBtn.disabled = !employee || !type;
}

// Send Button Click
demoNotifSendBtn.addEventListener('click', function() {
    demoSendNotification();
});

// Next Button Click
demoNotifNextBtn.addEventListener('click', function() {
    const dtrTab = document.getElementById('dtr-tab');
    dtrTab.click();
});

// Send Notification
function demoSendNotification() {
    const employeeSelect = demoNotifEmployeeSelect;
    const employeeName = employeeSelect.options[employeeSelect.selectedIndex].text;
    const employeeNumber = employeeSelect.value;
    const notifType = demoNotifTypeSelect.value;
    const sendEmail = demoNotifEmailCheck.checked;
    const sendRealtime = demoNotifRealtimeCheck.checked;

    // Show email preview if email is enabled
    if (sendEmail) {
        demoEmailPreview.classList.remove('d-none');
        demoEmailTo.textContent = employeeName + ' <employee@company.com>';
        demoEmailSubject.textContent = 'Attendance Notification - ' + notifType.replace('_', ' ').toUpperCase();

        const currentTime = new Date().toLocaleString();
        demoEmailBody.innerHTML = `
            <p>Dear ${employeeName},</p>
            <p>Your attendance has been recorded:</p>
            <ul>
                <li><strong>Type:</strong> ${notifType.replace('_', ' ').toUpperCase()}</li>
                <li><strong>Time:</strong> ${currentTime}</li>
                <li><strong>Status:</strong> Verified</li>
            </ul>
            <p>Thank you for using the MTCGS EMS system.</p>
            <p>Best regards,<br>MTCGS Attendance System</p>
        `;
    } else {
        demoEmailPreview.classList.add('d-none');
    }

    // Simulate API call
    fetch('<?php echo e(route("admin.unified-demo.notification")); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
        },
        body: JSON.stringify({
            employee_name: employeeName,
            employee_number: employeeNumber,
            notification_type: notifType,
            send_email: sendEmail,
            send_realtime: sendRealtime
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            demoShowNotification(data.data);
        } else {
            alert('Notification failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Fallback for demo
        demoShowNotification({
            employee_name: employeeName,
            employee_number: employeeNumber,
            type: notifType,
            timestamp: new Date().toLocaleString(),
            email_sent: sendEmail,
            realtime_sent: sendRealtime
        });
    });
}

// Show Notification
function demoShowNotification(data) {
    // Add to feed
    const feedEntry = {
        id: Date.now(),
        employee: data.employee_name,
        type: data.type.replace('_', ' ').toUpperCase(),
        time: data.timestamp,
        email: data.email_sent,
        realtime: data.realtime_sent
    };

    demoNotifFeed.unshift(feedEntry);
    demoUpdateFeed();

    // Update stats
    if (data.email_sent) demoNotifStats.email++;
    if (data.realtime_sent) demoNotifStats.realtime++;
    demoUpdateStats();

    // Show results
    demoNotifResults.classList.remove('d-none');
    demoNotifResultInfo.innerHTML = `
        <strong>Employee:</strong> ${data.employee_name}<br>
        <strong>Type:</strong> ${data.type.replace('_', ' ').toUpperCase()}<br>
        <strong>Time:</strong> ${data.timestamp}<br>
        <strong>Email:</strong> <span class="badge ${data.email_sent ? 'bg-success' : 'bg-secondary'}">${data.email_sent ? 'Sent' : 'Not Sent'}</span><br>
        <strong>Real-time:</strong> <span class="badge ${data.realtime_sent ? 'bg-success' : 'bg-secondary'}">${data.realtime_sent ? 'Sent' : 'Not Sent'}</span>
    `;

    // Update demo status
    updateDemoStatus('notification', 'completed');

    // Simulate real-time notification
    if (data.realtime_sent) {
        setTimeout(() => {
            demoConnectionStatus.className = 'alert alert-success';
            demoConnectionStatus.innerHTML = '<i class="fas fa-check-circle"></i> Real-time notification delivered!';
            setTimeout(() => {
                demoConnectionStatus.className = 'alert alert-info';
                demoConnectionStatus.innerHTML = '<i class="fas fa-wifi"></i> Connected to notification server';
            }, 3000);
        }, 1000);
    }
}

// Update Notification Feed Display
function demoUpdateFeed() {
    if (demoNotifFeed.length === 0) return;

    let html = '<div class="list-group">';
    demoNotifFeed.slice(0, 5).forEach(entry => {
        const emailIcon = entry.email ? '<i class="fas fa-envelope text-primary me-1"></i>' : '';
        const realtimeIcon = entry.realtime ? '<i class="fas fa-bolt text-success me-1"></i>' : '';

        html += `
            <div class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${entry.employee}</h6>
                    <small>${entry.time}</small>
                </div>
                <p class="mb-1">${emailIcon}${realtimeIcon}${entry.type}</p>
            </div>
        `;
    });
    html += '</div>';

    demoNotifFeedDiv.innerHTML = html;
}

// Update Statistics
function demoUpdateStats() {
    demoNotifTodayEmail.textContent = demoNotifStats.email;
    demoNotifTodayRealtime.textContent = demoNotifStats.realtime;
}

// Initialize
updateSendButton();
</script><?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\admin\demo\notifications.blade.php ENDPATH**/ ?>