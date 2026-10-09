@extends('layouts.app')

@section('title', 'Biometric Management')

@section('content')
<style>
    .biometric-status-list .list-group-item {
        padding: 0.9rem 1rem;
    }

    .biometric-status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        flex-wrap: nowrap;
    }

    .biometric-status-copy {
        min-width: 0;
        flex: 1 1 auto;
    }

    .biometric-status-copy strong,
    .biometric-status-copy small {
        display: block;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .biometric-status-badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
        min-width: 132px;
        font-size: 0.8rem;
        padding: 0.5rem 0.7rem;
    }

    .biometric-profile-card {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        min-width: 0;
    }

    .biometric-profile-avatar {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid rgba(13, 110, 253, 0.2);
    }

    .biometric-profile-initials {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #0d6efd;
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
    }

    @media (max-width: 576px) {
        .biometric-status-list .list-group-item {
            padding: 0.8rem 0.75rem;
        }

        .biometric-status-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .biometric-status-copy {
            width: 100%;
        }

        .biometric-status-badge {
            width: 100%;
            min-width: 0;
            justify-content: center;
        }
    }

    body.dark-mode .biometric-page .card-header.bg-white,
    body.dark-mode .biometric-page .biometric-status-list .list-group-item,
    body.dark-mode .biometric-page .biometric-status-list .list-group-item.bg-light {
        background-color: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }

    body.dark-mode .biometric-page .biometric-status-list .list-group-item strong,
    body.dark-mode .biometric-page .biometric-status-list .list-group-item small {
        color: #e2e8f0 !important;
    }

    body.dark-mode .biometric-page .text-primary {
        color: #FFD166 !important;
    }

    .biometric-profile-link {
        color: inherit;
        text-decoration: none;
    }

    .biometric-profile-link:hover {
        text-decoration: underline;
    }

    body.dark-mode .biometric-page .biometric-profile-link {
        color: #FFD166 !important;
    }
</style>
<div class="container-fluid biometric-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h2><i class="fas fa-fingerprint me-2"></i> Biometric Management</h2>
                    <p class="mb-0">Manage fingerprint registrations for users in your branch and handle admin biometric setup.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin / Branch Head's Own DTR Card -->
    @php
        $user = Auth::user();
        $adminProfile = $user ? $user->getAdminProfile() : null;
        $branchHeadProfile = $user ? $user->getBranchHeadProfile() : null;
        $employeeProfile = $adminProfile ? App\Models\EmployeeProfile::find($adminProfile->employee_profile_id) : ($branchHeadProfile ? App\Models\EmployeeProfile::find($branchHeadProfile->employee_profile_id) : null);
        $todayAttendance = $employeeProfile ? App\Models\AttendanceLog::where('employee_profile_id', $employeeProfile->id)->whereDate('attendance_date', today())->first() : null;
        $canSelfRegisterAdminFingerprint = $user && ($user->isSuperAdmin() || $user->role === 'branch_head' || $user->role === 'admin');
        $isBranchHeadSelfAttendance = $user && $user->role === 'branch_head';
    @endphp
    
    @if(false && $employeeProfile && $canSelfRegisterAdminFingerprint)
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield text-danger me-2"></i> {{ $isBranchHeadSelfAttendance ? 'My Attendance (Branch Head)' : 'My Attendance (Admin)' }}</h5>
                </div>
                <div class="card-body text-center">
                    <div id="adminAttendanceStatus">
                        @if(!$adminProfile->is_fingerprint_registered)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Please register your fingerprint first.
                            </div>
                            <button onclick="registerAdminFingerprint()" class="btn btn-primary">
                                <i class="fas fa-fingerprint"></i> Register My Fingerprint
                            </button>
                        @elseif(!$todayAttendance || !$todayAttendance->am_in || !$todayAttendance->am_out || !$todayAttendance->pm_in || !$todayAttendance->pm_out)
                            @php
                                $adminAttendanceButtonClass = 'success';
                                if ($todayAttendance && $todayAttendance->am_in && !$todayAttendance->am_out) {
                                    $adminAttendanceButtonClass = 'warning';
                                } elseif ($todayAttendance && $todayAttendance->am_out && !$todayAttendance->pm_in) {
                                    $adminAttendanceButtonClass = 'info';
                                } elseif ($todayAttendance && $todayAttendance->pm_in && !$todayAttendance->pm_out) {
                                    $adminAttendanceButtonClass = 'danger';
                                }
                            @endphp
                            <button onclick="processAdminAttendance()" class="btn btn-{{ $adminAttendanceButtonClass }} btn-lg px-5 py-3">
                                <i class="fas fa-fingerprint fa-2x d-block mb-2"></i>
                                FINGERPRINT ATTENDANCE
                            </button>
                        @else
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Completed for Today
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Register Personnel Section -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-users text-primary me-2"></i> Fingerprint Registration</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h6 class="text-primary mb-2"><i class="fas fa-user-tie me-2"></i>Employees</h6>
            </div>
            <div id="unregisteredEmployeesList" class="biometric-status-list">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm"></div> Loading employees...
                </div>
            </div>

    @if(Auth::user()->isSuperAdmin() || (Auth::user()->role === 'admin' && Auth::user()->admin_type === 'branch_admin'))
        <div class="mt-4 pt-3 border-top">
            <h6 class="text-primary mb-2"><i class="fas fa-chart-line me-2"></i>Finance Officers</h6>
                <div id="unregisteredFinanceOfficerList">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm"></div> Loading finance officers...
                    </div>
                </div>
        </div>

    @if(Auth::user()->isSuperAdmin())
        <div class="mt-4 pt-3 border-top">
            <h6 class="text-primary mb-2"><i class="fas fa-user-shield me-2"></i>Branch Admins</h6>
                <div id="unregisteredBranchAdminList">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm"></div> Loading branch admins...
                    </div>
                </div>
        </div>
    @endif
        </div>
        </div>
    @endif
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const appBaseUrl = @json(request()->getBaseUrl());
const apiUrl = (path) => `${appBaseUrl}/api/${path}`;
const jsonHeaders = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': csrfToken
};

function getOrCreateFingerprint(key, seed) {
    const stored = localStorage.getItem(key);
    if (stored) {
        return stored;
    }

    const generated = btoa(`${seed}_${Date.now()}`);
    localStorage.setItem(key, generated);
    return generated;
}

// Admin's own fingerprint registration
async function registerAdminFingerprint() {
    const user = {
        name: '{{ Auth::user()->name }}',
        email: '{{ Auth::user()->email }}',
        role: 'admin'
    };
    
    // Launch C# Fingerprint Enrollment App
    const uri = 'mtcgs-enroll:/register' +
        '?name=' + encodeURIComponent(user.name) +
        '&email=' + encodeURIComponent(user.email) +
        '&role=' + encodeURIComponent(user.role) +
        '&type=admin';
    
    try {
        window.location.href = uri;
        alert('Launching Fingerprint Enrollment App...\nPlease scan your fingerprint when the application opens.');
        
        // Poll for fingerprint data from the C# app
        let attempts = 0;
        const pollInterval = setInterval(async () => {
            attempts++;
            
            // Check if fingerprint was registered (max 2 minutes)
            if (attempts > 120) {
                clearInterval(pollInterval);
                return;
            }
            
            const response = await fetch(apiUrl('biometric/admin-status'), {
                headers: jsonHeaders
            });
            
            const result = await response.json();
            
            if (result.data && result.data.is_fingerprint_registered) {
                clearInterval(pollInterval);
                alert('✓ Fingerprint registered successfully!');
                location.reload();
            }
        }, 1000);
        
    } catch (error) {
        const fallbackPath = '{{ env("MTCGS_ENROLL_EXE") ?: "" }}';
        const fallbackText = fallbackPath ? '\n\nFallback local executable: ' + fallbackPath : '';

        alert('Could not launch C# app. Please ensure the mtcgs-enroll URI handler is registered.\n\nRun this in PowerShell as Administrator:\nreg import "c:\\xampp\\htdocs\\mtcgs-main_08-13-26\\mtcgs-ems\\biometric\\mtcgs-enroll.reg"' + fallbackText);
    }
}

async function processAdminAttendance() {
    const fakeFingerprint = getOrCreateFingerprint('mtcgs_admin_fingerprint', 'admin_fingerprint');
    
    const response = await fetch(apiUrl('biometric/admin-attendance'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(result.message);
        location.reload();
    } else {
        alert('Error: ' + (result.error || 'Please follow schedule'));
    }
}

// Load unregistered employees grouped by branch
async function loadUnregisteredEmployees() {
    const response = await fetch(apiUrl('biometric/unregistered-employees'), {
        headers: jsonHeaders
    });
    
    const result = await response.json();
    
    const container = document.getElementById('unregisteredEmployeesList');
    if (!container) {
        return;
    }

    if (!result.success || !result.data || result.data.length === 0) {
        container.innerHTML = '<div class="alert alert-success">All employees have registered fingerprints!</div>';
        return;
    }

    const branches = {};
    result.data.forEach(emp => {
        const branchName = emp.branch_name || 'Unknown Branch';
        if (!branches[branchName]) {
            branches[branchName] = [];
        }
        branches[branchName].push(emp);
    });

    const branchEntries = Object.entries(branches);
    let html = '<div class="list-group biometric-status-list">';

    branchEntries.forEach(([branchName, employees]) => {
        html += `
            <div class="list-group-item bg-light">
                <h6 class="mb-0 fw-bold text-primary">${branchName}</h6>
            </div>
        `;

        employees.forEach(emp => {
            const isRegistered = Boolean(emp.is_fingerprint_registered);
            const buttonLabel = isRegistered ? 'Registered' : 'Pending';
            const statusClass = isRegistered ? 'bg-success' : 'bg-warning';
            const buttonIcon = isRegistered ? 'fa-check-circle' : 'fa-clock';

            html += `
                <div class="list-group-item">
                    <div class="biometric-status-item">
                        <div class="biometric-profile-card biometric-status-copy">
                            ${profileAvatar(emp)}
                            <div>
                                ${profileLink(emp)}
                            <small class="text-muted">${emp.employee_number} - ${emp.position || 'Staff'}</small>
                            </div>
                        </div>
                        <span class="badge ${statusClass} text-white biometric-status-badge">
                            <i class="fas ${buttonIcon}"></i> ${buttonLabel}
                        </span>
                    </div>
                </div>
            `;
        });
    });

    html += '</div>';
    container.innerHTML = html;
}

function profileAvatar(item) {
    const initials = (item.name || '?')
        .split(/\s+/)
        .filter(Boolean)
        .map(part => part.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('');

    if (item.profile_photo_url) {
        return `<img src="${item.profile_photo_url}" alt="${item.name || 'Profile photo'}" class="biometric-profile-avatar">`;
    }

    return `<span class="biometric-profile-avatar biometric-profile-initials" aria-hidden="true">${initials}</span>`;
}

function profileLink(item) {
    const name = item.name || 'Unnamed profile';
    if (!item.id || !item.profile_type) {
        return `<strong>${name}</strong>`;
    }

    const profileType = encodeURIComponent(item.profile_type);
    const profileId = encodeURIComponent(item.id);
    return `<a class="biometric-profile-link fw-bold" href="${appBaseUrl}/admin/employee/${profileType}/${profileId}/profile">${name}</a>`;
}

// Render list of status items for a target container
function renderStatusList(container, items, emptyMessage, accentClass, labelPrefix, groupByBranch = false) {
    if (!container) {
        return;
    }

    if (!items || items.length === 0) {
        container.innerHTML = `<div class="alert alert-success">${emptyMessage}</div>`;
        return;
    }

    const groups = groupByBranch
        ? items.reduce((result, item) => {
            const branchName = item.branch_name || 'Unknown Branch';
            (result[branchName] ||= []).push(item);
            return result;
        }, {})
        : { '': items };

    let html = '<div class="list-group biometric-status-list">';
    Object.entries(groups).forEach(([branchName, groupItems]) => {
        if (groupByBranch) {
            html += `
                <div class="list-group-item bg-light">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-building me-2"></i>${branchName}</h6>
                </div>
            `;
        }

        groupItems.forEach(item => {
        const isRegistered = Boolean(item.is_fingerprint_registered);
        const buttonLabel = isRegistered ? 'Registered' : 'Pending';
        const buttonIcon = isRegistered ? 'fa-check-circle' : 'fa-clock';
        const statusClass = isRegistered ? 'bg-success' : accentClass;
        const emailText = item.email || item.position || 'N/A';
        const employeeLabel = item.employee_number ? `${item.employee_number}` : '';
        const secondaryLine = item.email
            ? `${item.email}${employeeLabel ? ` - ${employeeLabel}` : ''}`
            : (employeeLabel || emailText);

        html += `
            <div class="list-group-item">
                <div class="biometric-status-item">
                    <div class="biometric-profile-card biometric-status-copy">
                        ${profileAvatar(item)}
                        <div>
                            ${profileLink(item)}
                            <small class="text-muted">${secondaryLine}</small>
                        </div>
                    </div>
                    <span class="badge ${statusClass} text-white biometric-status-badge">
                        <i class="fas ${buttonIcon}"></i> ${buttonLabel}
                    </span>
                </div>
            </div>
        `;
        });
    });
    html += '</div>';
    container.innerHTML = html;
}

// Load unregistered finance officers
async function loadUnregisteredFinanceOfficers() {
    const financeOfficerList = document.getElementById('unregisteredFinanceOfficerList');
    if (!financeOfficerList) {
        return;
    }

    const response = await fetch(apiUrl('biometric/unregistered-finance-officers'), {
        headers: jsonHeaders
    });
    
    const result = await response.json();
    
    if (result.success && result.data.length > 0) {
        const financeOfficers = result.data;
        renderStatusList(financeOfficerList, financeOfficers, 'All finance officers have registered fingerprints!', 'bg-primary', 'Finance Officer', true);
    } else {
        renderStatusList(financeOfficerList, [], 'All finance officers have registered fingerprints!', 'bg-primary', 'Finance Officer');
    }
}

// Load unregistered admins
async function loadUnregisteredAdmins() {
    const branchAdminList = document.getElementById('unregisteredBranchAdminList');
    if (!branchAdminList) {
        return;
    }

    const response = await fetch(apiUrl('biometric/unregistered-admins'), {
        headers: jsonHeaders
    });
    
    const result = await response.json();
    
    if (result.success && result.data.length > 0) {
        const branchAdmins = result.data;
        renderStatusList(branchAdminList, branchAdmins, 'All branch admins have registered fingerprints!', 'bg-warning', 'Branch Admin', true);
    } else {
        renderStatusList(branchAdminList, [], 'All branch admins have registered fingerprints!', 'bg-warning', 'Branch Admin');
    }
}

// Register employee fingerprint
async function registerEmployeeFingerprint(employeeId, employeeName) {
    const confirmed = window.confirm(`Are you sure you want to register the fingerprint for ${employeeName}?`);
    if (!confirmed) {
        return;
    }

    const fakeFingerprint = btoa('employee_fingerprint_' + Date.now());
    
    const response = await fetch(apiUrl('biometric/register'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ employee_id: employeeId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for ${employeeName}`);
        await loadUnregisteredEmployees();
    } else {
        alert('Error: ' + result.error);
    }
}

// Register finance officer fingerprint (Super Admin)
async function registerFinanceOfficerFingerprint(financeId, financeName) {
    const confirmed = window.confirm(`Are you sure you want to register the fingerprint for ${financeName}?`);
    if (!confirmed) {
        return;
    }

    const fakeFingerprint = btoa('finance_officer_fingerprint_' + Date.now());
    
    const response = await fetch(apiUrl('biometric/register-finance-officer'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ finance_id: financeId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for finance officer: ${financeName}`);
        await loadUnregisteredFinanceOfficers();
    } else {
        alert('Error: ' + result.error);
    }
}

// Register admin fingerprint (Super Admin)
async function registerAdminOfficerFingerprint(adminId, adminName) {
    const confirmed = window.confirm(`Are you sure you want to register the fingerprint for ${adminName}?`);
    if (!confirmed) {
        return;
    }

    const fakeFingerprint = btoa('admin_officer_fingerprint_' + Date.now());
    
    const response = await fetch(apiUrl('biometric/register-admin-officer'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...jsonHeaders
        },
        body: JSON.stringify({ admin_id: adminId, fingerprint_data: fakeFingerprint })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`✅ Fingerprint registered for admin: ${adminName}`);
        await loadUnregisteredAdmins();
    } else {
        alert('Error: ' + result.error);
    }
}

// Load all sections
loadUnregisteredEmployees();
if (document.getElementById('unregisteredFinanceHeadList') || document.getElementById('unregisteredFinanceOfficerList') || document.getElementById('unregisteredFinanceList')) {
    loadUnregisteredFinanceOfficers();
}
if (document.getElementById('unregisteredHrList') || document.getElementById('unregisteredBranchAdminList') || document.getElementById('unregisteredAdminsList')) {
    loadUnregisteredAdmins();
}
</script>
@endsection
