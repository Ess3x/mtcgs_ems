@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid profile-page">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">
                        <i class="fas fa-user-circle text-primary me-2"></i>My Profile
                    </h2>
                    <p class="text-muted mb-0">View and manage your account information</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card text-primary me-2"></i>Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('photo_updated'))
                        <div class="alert alert-success py-2" role="alert">
                            <i class="fas fa-check-circle me-1"></i>{{ session('photo_updated') }}
                        </div>
                    @endif
                    @if($profile && $profile->profile_photo)
                        <div class="text-center mb-3">
                            <img src="{{ route('profile.photo', [strtolower(class_basename($profile)), $profile->id]) . '?v=' . $profile->updated_at?->timestamp }}" alt="Profile photo" class="rounded-circle border" style="width: 140px; height: 140px; object-fit: cover;">
                        </div>
                    @endif
                    <form method="POST" action="{{ route('profile.photo.save') }}" enctype="multipart/form-data" class="mb-4">
                        @csrf
                        <label for="profile_photo" class="form-label">Profile Picture</label>
                        <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                        <small class="text-muted">JPG, PNG, or WEBP up to 2 MB.</small>
                        @error('profile_photo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-upload me-1"></i>Save Profile Picture
                        </button>
                    </form>

                    <!-- User Account Info -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Account Details
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Full Name</div>
                                <p class="mb-0 fw-semibold">{{ $user->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Email Address</div>
                                <form method="POST" action="{{ route('profile.update') }}" class="d-flex gap-2 align-items-start">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="name" value="{{ $user->name }}">
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control form-control-sm" required autocomplete="email">
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                        <i class="fas fa-save me-1"></i>Save
                                    </button>
                                </form>
                                @if(session('status') === 'profile-updated')
                                    <small class="text-success d-block mt-1"><i class="fas fa-check-circle me-1"></i>Email updated successfully.</small>
                                @endif
                                @error('email')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Role</div>
                                <p class="mb-0">
                                    <span class="badge bg-primary">{{ ucfirst($user->role) }}</span>
                                    @if($user->role === 'admin' && $user->admin_type)
                                        <small class="text-muted ms-1">({{ ucfirst(str_replace('_', ' ', $user->admin_type)) }})</small>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Account Status</div>
                                <p class="mb-0">
                                    @if($user->is_active)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">ID Verification Status</div>
                                <p class="mb-0">
                                    @if($user->id_verification_status === 'approved')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                    @elseif($user->id_verification_status === 'pending')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                    @elseif($user->id_verification_status === 'rejected')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Rejected
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-question-circle me-1"></i>Not Submitted
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($user->last_login_at)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Last Login</div>
                                <p class="mb-0 fw-semibold">{{ $user->last_login_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Profile Specific Information -->
                    @if($profile)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-briefcase me-2"></i>{{ $profileType }} Details
                            </h6>
                        </div>

                        @if($profile instanceof \App\Models\EmployeeProfile)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Employee Number</div>
                                <p class="mb-0 fw-semibold">{{ $profile->employee_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Position</div>
                                <p class="mb-0 fw-semibold">{{ $profile->position ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Department</div>
                                <p class="mb-0 fw-semibold">{{ $profile->department ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Employment Type</div>
                                <p class="mb-0 fw-semibold">{{ $profile->employment_type ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Date Hired</div>
                                <p class="mb-0 fw-semibold">
                                    @if($profile->date_hired)
                                        {{ \Carbon\Carbon::parse($profile->date_hired)->format('M d, Y') }}
                                    @else
                                        Not specified
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Basic Salary</div>
                                <p class="mb-0 fw-semibold">
                                    @if($profile->basic_salary)
                                        ₱{{ number_format($profile->basic_salary, 2) }}
                                    @else
                                        Not specified
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Branch</div>
                                <p class="mb-0 fw-semibold">{{ $profile->branch ? $profile->branch->branch_name : 'Not assigned' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Fingerprint Status</div>
                                <p class="mb-0">
                                    @if($profile->is_fingerprint_registered)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Registered
                                        </span>
                                    @else
                                        <span class="badge bg-warning">
                                            <i class="fas fa-times-circle me-1"></i>Not Registered
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0"><i class="fas fa-id-card me-2"></i>Government Numbers</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="edit-government-numbers"><i class="fas fa-pen me-1"></i>Edit</button>
                            </div>
                            <form method="POST" action="{{ route('profile.update') }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="name" value="{{ $user->name }}">
                                <input type="hidden" name="email" value="{{ $user->email }}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="sss-number" class="form-label">SSS Number</label>
                                        <input id="sss-number" name="sss_number" type="text" class="form-control government-number-field" value="{{ old('sss_number', $profile->sss_number) }}" placeholder="e.g. 12-3456789-0" maxlength="30" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="philhealth-number" class="form-label">PhilHealth Number</label>
                                        <input id="philhealth-number" name="philhealth_number" type="text" class="form-control government-number-field" value="{{ old('philhealth_number', $profile->philhealth_number) }}" placeholder="Enter PhilHealth number" maxlength="30" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pagibig-number" class="form-label">Pag-IBIG Number</label>
                                        <input id="pagibig-number" name="pagibig_number" type="text" class="form-control government-number-field" value="{{ old('pagibig_number', $profile->pagibig_number) }}" placeholder="Enter Pag-IBIG number" maxlength="30" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tin-number" class="form-label">TIN Number</label>
                                        <input id="tin-number" name="tin_number" type="text" class="form-control government-number-field" value="{{ old('tin_number', $profile->tin_number) }}" placeholder="Enter TIN number" maxlength="30" disabled>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary d-none" id="save-government-numbers"><i class="fas fa-save me-1"></i>Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @elseif($profile instanceof \App\Models\AdminProfile)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Employee Number</div>
                                <p class="mb-0 fw-semibold">{{ $profile->employee_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Position</div>
                                <p class="mb-0 fw-semibold">{{ $profile->position ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Department</div>
                                <p class="mb-0 fw-semibold">{{ $profile->department ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Admin Level</div>
                                <p class="mb-0 fw-semibold">{{ ucfirst(str_replace('_', ' ', $profile->admin_level ?? 'regular')) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Date Hired</div>
                                <p class="mb-0 fw-semibold">
                                    @if($profile->date_hired)
                                        {{ \Carbon\Carbon::parse($profile->date_hired)->format('M d, Y') }}
                                    @else
                                        Not specified
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Branch</div>
                                <p class="mb-0 fw-semibold">{{ $profile->branch ? $profile->branch->branch_name : 'Not assigned' }}</p>
                            </div>
                        </div>
                        @elseif($profile instanceof \App\Models\FinanceProfile)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Employee Number</div>
                                <p class="mb-0 fw-semibold">{{ $profile->employee_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Position</div>
                                <p class="mb-0 fw-semibold">{{ $profile->position ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Department</div>
                                <p class="mb-0 fw-semibold">{{ $profile->department ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Payroll Processing</div>
                                <p class="mb-0">
                                    @if($profile->can_process_payroll)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Enabled
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times-circle me-1"></i>Disabled
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Date Hired</div>
                                <p class="mb-0 fw-semibold">
                                    @if($profile->date_hired)
                                        {{ \Carbon\Carbon::parse($profile->date_hired)->format('M d, Y') }}
                                    @else
                                        Not specified
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-label text-muted">Branch</div>
                                <p class="mb-0 fw-semibold">{{ $profile->branch ? $profile->branch->branch_name : 'Not assigned' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- ID Document Section -->
        <div class="col-lg-4">
            @if($profile instanceof \App\Models\EmployeeProfile || $profile instanceof \App\Models\FinanceProfile || $profile instanceof \App\Models\AdminProfile)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-signature text-primary me-2"></i>E-Signature for DTR
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('signature_updated'))
                        <div class="alert alert-success py-2" role="alert">
                            <i class="fas fa-check-circle me-1"></i>{{ session('signature_updated') }}
                        </div>
                    @endif
                    <p class="text-muted small">Draw your signature below. It will be used when submitting your DTR.</p>
                    @php
                        $linkedSignaturePath = null;
                        if ($profile instanceof \App\Models\EmployeeProfile) {
                            $linkedSignaturePath = \App\Models\FinanceProfile::where('employee_profile_id', $profile->id)->value('signature_path')
                                ?? \App\Models\AdminProfile::where('employee_profile_id', $profile->id)->value('signature_path');
                        }
                        $hasSavedSignature = ($profile->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->signature_path))
                            || ($linkedSignaturePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($linkedSignaturePath));
                    @endphp
                    @if($hasSavedSignature)
                        <div class="border rounded p-2 mb-3 text-center">
                            <img src="{{ ($profile instanceof \App\Models\EmployeeProfile ? route('profile.signature', $profile->id) : route('profile.signature.any', [strtolower(class_basename($profile)), $profile->id])) . '?v=' . $profile->updated_at?->timestamp }}" alt="Saved e-signature" class="img-fluid" style="width: 320px; max-width: 100%; height: 100px; object-fit: contain;">
                            <div class="small text-muted mt-1">Saved signature</div>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('profile.signature.save') }}" id="signature-form">
                        @csrf
                        <canvas id="signature-canvas" width="500" height="180" aria-label="Signature drawing area"></canvas>
                        <input type="hidden" name="signature" id="signature-input">
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-signature">
                                <i class="fas fa-eraser me-1"></i>Clear
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i>Save Signature
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs text-primary me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        @if($user->role === 'employee')
                        <a href="{{ route('leave.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-calendar-alt me-2"></i>Request Leave
                        </a>
                        <a href="{{ route('employee.payslips') }}" class="btn btn-outline-info">
                            <i class="fas fa-file-invoice-dollar me-2"></i>View Payslips
                        </a>
                        @endif
                        <a href="#change-password" class="btn btn-outline-primary" id="quick-change-password">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Profile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm mt-4 d-none" id="change-password">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-lock text-primary me-2"></i>Change Password
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="close-password-form">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
                <div class="card-body" id="password-form-container">
                    @if(session('password_updated'))
                        <div class="alert alert-success py-2" role="alert">
                            <i class="fas fa-check-circle me-1"></i>{{ session('password_updated') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update.profile') }}">
                        @csrf
                        @method('PUT')
                        <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" hidden>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                       id="current_password" name="current_password" required autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password" aria-label="Show current password" title="Show password"><i class="fas fa-eye"></i></button>
                            </div>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                       id="password" name="password" required minlength="8" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" aria-label="Show new password" title="Show password"><i class="fas fa-eye"></i></button>
                            </div>
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Use at least 8 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation"
                                       name="password_confirmation" required minlength="8" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_confirmation" aria-label="Show password confirmation" title="Show password"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Save New Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#signature-canvas {
    display: block;
    width: 100%;
    height: 150px;
    background: #fff;
    border: 1px dashed #adb5bd;
    border-radius: 4px;
    cursor: crosshair;
    touch-action: none;
}

@media print {
    .btn, .card-header, .menu-toggle, .top-navbar {
        display: none !important;
    }
    .sidebar {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .container-fluid {
        max-width: none !important;
    }
}
</style>
@if($profile instanceof \App\Models\EmployeeProfile || $profile instanceof \App\Models\FinanceProfile || $profile instanceof \App\Models\AdminProfile)
<script>
    (() => {
        const canvas = document.getElementById('signature-canvas');
        const form = document.getElementById('signature-form');
        const input = document.getElementById('signature-input');
        const clearButton = document.getElementById('clear-signature');
        const context = canvas.getContext('2d');
        let drawing = false;
        let hasInk = false;

        context.strokeStyle = '#111827';
        context.lineWidth = 2;
        context.lineCap = 'round';

        const position = (event) => {
            const bounds = canvas.getBoundingClientRect();
            return {
                x: (event.clientX - bounds.left) * (canvas.width / bounds.width),
                y: (event.clientY - bounds.top) * (canvas.height / bounds.height),
            };
        };

        const start = (event) => {
            event.preventDefault();
            canvas.setPointerCapture?.(event.pointerId);
            drawing = true;
            const point = position(event);
            context.beginPath();
            context.moveTo(point.x, point.y);
        };

        const draw = (event) => {
            if (!drawing) return;
            event.preventDefault();
            const point = position(event);
            context.lineTo(point.x, point.y);
            context.stroke();
            hasInk = true;
        };

        const stop = (event) => {
            drawing = false;
            if (event?.pointerId !== undefined) {
                canvas.releasePointerCapture?.(event.pointerId);
            }
        };
        canvas.addEventListener('pointerdown', start);
        canvas.addEventListener('pointermove', draw);
        canvas.addEventListener('pointerup', stop);
        canvas.addEventListener('pointercancel', stop);
        clearButton.addEventListener('click', () => {
            context.clearRect(0, 0, canvas.width, canvas.height);
            hasInk = false;
        });
        form.addEventListener('submit', (event) => {
            if (!hasInk) {
                event.preventDefault();
                alert('Please draw your signature first.');
                return;
            }
            input.value = canvas.toDataURL('image/png');
        });
    })();
</script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editButton = document.getElementById('edit-government-numbers');
        const saveButton = document.getElementById('save-government-numbers');
        const fields = document.querySelectorAll('.government-number-field');

        if (editButton && saveButton) {
            editButton.addEventListener('click', function () {
                fields.forEach((field) => field.disabled = false);
                editButton.classList.add('d-none');
                saveButton.classList.remove('d-none');
                fields[0]?.focus();
            });
        }

        document.querySelectorAll('.toggle-password').forEach((button) => {
            button.addEventListener('click', function () {
                const field = document.getElementById(button.dataset.target);
                const icon = button.querySelector('i');
                const showing = field.type === 'text';

                field.type = showing ? 'password' : 'text';
                icon.classList.toggle('fa-eye', showing);
                icon.classList.toggle('fa-eye-slash', !showing);
                button.title = showing ? 'Show password' : 'Hide password';
                button.setAttribute('aria-label', button.title);
            });
        });

        const passwordToggle = document.getElementById('quick-change-password');
        const passwordContainer = document.getElementById('password-form-container');
        const closePasswordButton = document.getElementById('close-password-form');
        const passwordWasUpdated = {{ session('password_updated') ? 'true' : 'false' }};

        if (passwordToggle && passwordContainer) {
            passwordToggle.addEventListener('click', function (event) {
                event.preventDefault();
                document.getElementById('change-password')?.classList.remove('d-none');
                passwordContainer.classList.remove('d-none');
                passwordContainer.querySelector('input')?.focus();
            });

            closePasswordButton?.addEventListener('click', function () {
                document.getElementById('change-password')?.classList.add('d-none');
                passwordContainer.classList.add('d-none');
                passwordContainer.querySelector('form')?.reset();
                window.history.replaceState(null, '', window.location.pathname);
            });

            const passwordHasErrors = {{ $errors->updatePassword->any() ? 'true' : 'false' }};

            if (passwordWasUpdated || passwordHasErrors) {
                document.getElementById('change-password')?.classList.remove('d-none');
                passwordContainer.classList.remove('d-none');
                document.getElementById('change-password')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });
</script>

@endsection