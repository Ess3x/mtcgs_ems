<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\EmployeeController;

// Public landing page (show before login)
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Guest routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth')->group(function () {
    Route::get('/confirm-password', function () {
        return view('auth.confirm-password');
    })->name('password.confirm');

    Route::post('/confirm-password', function (Request $request) {
        if (!Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors([
                'password' => __('The provided password does not match your current password.'),
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended('/dashboard');
    });

    Route::get('/verify-email', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
        $user = \App\Models\User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return redirect(route('dashboard').'?verified=1');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', function (Request $request) {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    })->name('password.email');

    Route::get('/reset-password/{token}', function (string $token) {
        return view('auth.reset-password', ['token' => $token]);
    })->name('password.reset');

    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                ])->setRememberToken(\Illuminate\Support\Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    })->name('password.update.reset');
});

// Protected routes (all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-schedule', [App\Http\Controllers\EmployeeScheduleController::class, 'index'])->name('employee.schedule');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::get('/cash-advances', [App\Http\Controllers\CashAdvanceController::class, 'index'])->name('cash-advances.index');
    Route::post('/cash-advances', [App\Http\Controllers\CashAdvanceController::class, 'store'])->name('cash-advances.store');
    Route::post('/cash-advances/{application}/fo-review', [App\Http\Controllers\CashAdvanceController::class, 'reviewByFo'])->name('cash-advances.fo-review');
    Route::post('/cash-advances/{application}/bh-review', [App\Http\Controllers\CashAdvanceController::class, 'reviewByBh'])->name('cash-advances.bh-review');
    Route::post('/cash-advances/{application}/hr-review', [App\Http\Controllers\CashAdvanceController::class, 'reviewByHr'])->name('cash-advances.hr-review');
    Route::post('/cash-advances/{application}/fh-review', [App\Http\Controllers\CashAdvanceController::class, 'reviewByFh'])->name('cash-advances.fh-review');
    Route::post('/profile/signature', [DashboardController::class, 'saveSignature'])->name('profile.signature.save');
    Route::get('/profile/signature/{profile}', [DashboardController::class, 'signature'])->name('profile.signature');
    Route::get('/profile/signature/{type}/{id}', [DashboardController::class, 'profileSignature'])->name('profile.signature.any');
    Route::post('/profile/photo', [DashboardController::class, 'saveProfilePhoto'])->name('profile.photo.save');
    Route::get('/profile/photo/{type}/{id}', [DashboardController::class, 'profilePhoto'])->name('profile.photo');
    Route::patch('/profile', function (Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->user()->id,
            'sss_number' => 'nullable|string|max:30',
            'philhealth_number' => 'nullable|string|max:30',
            'pagibig_number' => 'nullable|string|max:30',
            'tin_number' => 'nullable|string|max:30',
        ]);

        $request->user()->forceFill([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $employeeProfile = $request->user()->getEmployeeProfile();
        if ($employeeProfile) {
            $employeeProfile->update([
                'sss_number' => $validated['sss_number'] ?? null,
                'philhealth_number' => $validated['philhealth_number'] ?? null,
                'pagibig_number' => $validated['pagibig_number'] ?? null,
                'tin_number' => $validated['tin_number'] ?? null,
            ]);
        }

        return redirect('/profile')->with('status', 'profile-updated');
    })->name('profile.update');

    Route::delete('/profile', function (Request $request) {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('profile.destroy');

    Route::put('/password', [DashboardController::class, 'updatePassword'])->name('password.update.profile');
    Route::get('/profile/document', [DashboardController::class, 'profileDocument'])->name('profile.document');
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/data', [App\Http\Controllers\AnalyticsController::class, 'data'])->name('analytics.data');
    Route::get('/admin/shifts', [App\Http\Controllers\ShiftController::class, 'index'])->name('admin.shifts.index');
    Route::post('/admin/shifts', [App\Http\Controllers\ShiftController::class, 'store'])->name('admin.shifts.store');
    Route::put('/admin/shifts/{shift}', [App\Http\Controllers\ShiftController::class, 'update'])->name('admin.shifts.update');
    Route::delete('/admin/shifts/{shift}', [App\Http\Controllers\ShiftController::class, 'destroy'])->name('admin.shifts.destroy');
    Route::post('/admin/shifts/assign/{employee}', [App\Http\Controllers\ShiftController::class, 'assign'])->name('admin.shifts.assign');

    Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/devices', [App\Http\Controllers\Admin\DeviceController::class, 'index'])->name('devices.index');
        Route::get('/devices/create', [App\Http\Controllers\Admin\DeviceController::class, 'create'])->name('devices.create');
        Route::post('/devices', [App\Http\Controllers\Admin\DeviceController::class, 'store'])->name('devices.store');
        Route::get('/devices/{device}/edit', [App\Http\Controllers\Admin\DeviceController::class, 'edit'])->name('devices.edit');
        Route::put('/devices/{device}', [App\Http\Controllers\Admin\DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}', [App\Http\Controllers\Admin\DeviceController::class, 'destroy'])->name('devices.destroy');
        
        Route::get('/branch-heads', [App\Http\Controllers\Admin\BranchHeadController::class, 'index'])->name('branch-heads.index');
        Route::get('/branch-heads/create', [App\Http\Controllers\Admin\BranchHeadController::class, 'create'])->name('branch-heads.create');
        Route::post('/branch-heads', [App\Http\Controllers\Admin\BranchHeadController::class, 'store'])->name('branch-heads.store');
        Route::get('/branch-heads/{branchHead}', [App\Http\Controllers\Admin\BranchHeadController::class, 'show'])->name('branch-heads.show');
        Route::get('/branch-heads/{branchHead}/edit', [App\Http\Controllers\Admin\BranchHeadController::class, 'edit'])->name('branch-heads.edit');
        Route::put('/branch-heads/{branchHead}', [App\Http\Controllers\Admin\BranchHeadController::class, 'update'])->name('branch-heads.update');
        Route::delete('/branch-heads/{branchHead}', [App\Http\Controllers\Admin\BranchHeadController::class, 'destroy'])->name('branch-heads.destroy');
    });
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    
    // Calendar routes (all authenticated users can view)
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [App\Http\Controllers\CalendarController::class, 'index'])->name('index');
        Route::get('/view', [App\Http\Controllers\CalendarController::class, 'calendar'])->name('calendar');
    });
    
    // Leave routes
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::get('/approve/{id}', [LeaveController::class, 'approve'])->name('approve');
        Route::get('/reject/{id}', [LeaveController::class, 'reject'])->name('reject');
    });
    
    // DTR (Daily Time Record) routes - Employee only
    Route::prefix('dtr')->name('employee.dtr.')->group(function () {
        Route::get('/', [App\Http\Controllers\DTRController::class, 'index'])->name('index');
        Route::get('/summary', [App\Http\Controllers\DTRController::class, 'summary'])->name('summary');
        Route::get('/{dtr}', [App\Http\Controllers\DTRController::class, 'show'])->name('show');
        Route::get('/{dtr}/download', [App\Http\Controllers\DTRController::class, 'downloadPdf'])->name('download');
        Route::get('/{dtr}/download-excel', [App\Http\Controllers\DTRController::class, 'downloadExcel'])->name('download-excel');
        Route::post('/{dtr}/submit', [App\Http\Controllers\DTRController::class, 'submit'])->name('submit');
        Route::post('/attendance/{attendance}/adjust-present', [App\Http\Controllers\DTRController::class, 'requestAttendanceAdjustment'])->name('attendance.adjust-present');
    });
    
    // Admin routes (no middleware, check inside controller)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
        Route::get('/employees/archives', [EmployeeController::class, 'archives'])->name('employees-archives');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employee-create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employee-store');
        Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('employee-edit');
        Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employee-update');
        Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('employee-delete');
        Route::post('/employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employee-restore');
        
        // Admin account edit routes (separate to avoid ID collisions)
        Route::get('/admin-accounts/{id}/edit', [EmployeeController::class, 'editAdmin'])->name('admin-edit');
        Route::put('/admin-accounts/{id}', [EmployeeController::class, 'updateAdmin'])->name('admin-update');
        
        // Calendar Management (Admin only)
        Route::resource('calendar', App\Http\Controllers\CalendarController::class)->except(['show']);
        
        // Branches routes
        Route::resource('branches', App\Http\Controllers\Admin\BranchController::class);
    });
});

Route::post('/attendance/clock', [App\Http\Controllers\Api\AttendanceController::class, 'clockAttendance']);

// Biometric setup page (admin only)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/biometric', function () {
        $unregisteredEmployees = \App\Models\Employee::where('is_fingerprint_registered', false)->get();
        $registeredEmployees = \App\Models\Employee::where('is_fingerprint_registered', true)->get();
        return view('admin.biometric', compact('unregisteredEmployees', 'registeredEmployees'));
    })->name('biometric');

    Route::view('/fingerprint-scanner', 'admin.biometric-scanner')->name('fingerprint-scanner');
    
    // Fingerprint Demo
    Route::get('/fingerprint-demo', [App\Http\Controllers\Admin\FingerprintDemoController::class, 'index'])->name('fingerprint-demo');

    // Unified Demo (All Demos in One Place)
    Route::get('/unified-demo', [App\Http\Controllers\Admin\UnifiedDemoController::class, 'index'])->name('unified-demo');
    Route::post('/unified-demo/attendance', [App\Http\Controllers\Admin\UnifiedDemoController::class, 'simulateAttendance'])->name('unified-demo.attendance');
    Route::post('/unified-demo/notification', [App\Http\Controllers\Admin\UnifiedDemoController::class, 'simulateNotification'])->name('unified-demo.notification');

    // Payroll routes
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/periods', [App\Http\Controllers\Admin\PayrollController::class, 'periods'])->name('periods');
        Route::post('/create-period', [App\Http\Controllers\Admin\PayrollController::class, 'createPeriod'])->name('create-period');
        Route::post('/process/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'process'])->name('process');
        Route::post('/submit-to-branch/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'submitByHrToBranch'])->name('submit-to-branch');
        Route::get('/entries/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'viewEntries'])->name('entries');
        Route::get('/download/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'downloadPayroll'])->name('download');
        Route::post('/approve/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('approve');
        Route::post('/recheck/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'recheck'])->name('recheck');
        Route::post('/return/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnToDraft'])->name('return');
        Route::post('/admin-approval-stage/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'toggleAdminApprovalStage'])->name('admin-approval-stage');
        Route::post('/branch-approve/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'approveByBranchHead'])->name('branch-approve');
        Route::post('/branch-submit-finance/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'submitByBranchToFinance'])->name('branch-submit-finance');
        Route::post('/return-to-bh/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnPeriodToBranchHead'])->name('return-to-bh');
        Route::post('/return-selected-to-bh/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnEntriesToBranchHead'])->name('return-selected-to-bh');
        Route::post('/return-to-hr/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnPeriodToHr'])->name('return-to-hr');
        Route::post('/return-selected-to-hr/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnEntriesToHr'])->name('return-selected-to-hr');
        Route::post('/return-to-fh/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnPeriodToFinanceHead'])->name('return-to-fh');
        Route::post('/return-selected-to-fh/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'returnEntriesToFinanceHead'])->name('return-selected-to-fh');
        Route::post('/resubmit-corrected/{periodId}', [App\Http\Controllers\Admin\PayrollController::class, 'resubmitCorrectedPeriod'])->name('resubmit-corrected');
        Route::get('/entry/{entryId}/edit', [App\Http\Controllers\Admin\PayrollController::class, 'editEntry'])->name('edit-entry');
        Route::put('/entry/{entryId}', [App\Http\Controllers\Admin\PayrollController::class, 'updateEntry'])->name('entry.update');
        Route::get('/payslip/{entryId}', [App\Http\Controllers\Admin\PayrollController::class, 'viewPayslip'])->name('payslip');
        Route::get('/payslip/{entryId}/download', [App\Http\Controllers\Admin\PayrollController::class, 'generatePayslip'])->name('download-payslip');
        Route::post('/payslip/{entryId}/submit', [App\Http\Controllers\Admin\PayrollController::class, 'submitPayslip'])->name('submit-payslip');
    });
});

// DTR Management Routes (Admin/Finance only)
Route::middleware('auth')->prefix('admin')->name('admin.dtr.')->group(function () {
    Route::get('/dtr-management', [App\Http\Controllers\Admin\DTRManagementController::class, 'index'])->name('index');
    Route::get('/dtr/{dtr}', [App\Http\Controllers\Admin\DTRManagementController::class, 'show'])->name('show');
    Route::post('/dtr/{dtr}/approve', [App\Http\Controllers\Admin\DTRManagementController::class, 'approve'])->name('approve');
    Route::post('/dtr/{dtr}/reject', [App\Http\Controllers\Admin\DTRManagementController::class, 'reject'])->name('reject');
    Route::post('/attendance/{attendance}/approve-adjustment', [App\Http\Controllers\Admin\DTRManagementController::class, 'approveAttendanceAdjustment'])->name('attendance.approve-adjustment');
    Route::post('/attendance/{attendance}/reject-adjustment', [App\Http\Controllers\Admin\DTRManagementController::class, 'rejectAttendanceAdjustment'])->name('attendance.reject-adjustment');
    Route::get('/employee/{employee}/dtrs', [App\Http\Controllers\Admin\DTRManagementController::class, 'employeeDTRs'])->name('employee-dtrs');
});

// Finance Officer Payroll Generation Routes (from DTRs)
Route::middleware('auth')->prefix('finance')->name('finance.')->group(function () {
    Route::prefix('payroll-generation')->name('payroll-generation.')->group(function () {
        Route::get('/ready-dtrs', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'readyDTRs'])->name('ready-dtrs');
        Route::get('/dtr/{dtr}/review', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'reviewDTR'])->name('review-dtr');
        Route::post('/dtr/{dtr}/generate', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'generateFromDTR'])->name('from-dtr');
        Route::post('/period/{period}/generate', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'generateForPeriod'])->name('for-period');
        Route::get('/entry/{entry}', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'viewEntry'])->name('entry');
        Route::put('/entry/{entry}', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'updateEntry'])->name('update-entry');
        Route::post('/entry/{entry}/finalize', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'finalizeEntry'])->name('finalize-entry');
        Route::get('/period/{period}/summary', [App\Http\Controllers\Finance\PayrollGenerationController::class, 'summaryByPeriod'])->name('summary');
    });
});

// Employee payslip view
Route::middleware('auth')->get('/my-payslips', [App\Http\Controllers\Admin\PayrollController::class, 'myPayslips'])->name('employee.payslips');

// Admin Verification Routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/verifications', [App\Http\Controllers\Admin\VerificationController::class, 'index'])->name('verifications');
    Route::get('/verify/{userId}/document', [App\Http\Controllers\Admin\VerificationController::class, 'document'])->name('verify.document');
    Route::post('/verify/{userId}/approve', [App\Http\Controllers\Admin\VerificationController::class, 'approve'])->name('verify.approve');
    Route::post('/verify/{userId}/reject', [App\Http\Controllers\Admin\VerificationController::class, 'reject'])->name('verify.reject');
    Route::post('/verify/status-change/{employeeId}/approve', [App\Http\Controllers\Admin\VerificationController::class, 'approveStatusChange'])->name('verify.status-change.approve');
    Route::post('/verify/status-change/{employeeId}/reject', [App\Http\Controllers\Admin\VerificationController::class, 'rejectStatusChange'])->name('verify.status-change.reject');
    
    // Admin Permission Management (Super Admin Only)
    Route::get('/permissions', [App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('permissions');
    Route::post('/permissions/{adminId}/grant', [App\Http\Controllers\Admin\PermissionController::class, 'grantVerifyPermission'])->name('permissions.grant');
    Route::post('/permissions/{adminId}/revoke', [App\Http\Controllers\Admin\PermissionController::class, 'revokeVerifyPermission'])->name('permissions.revoke');
    
    // Employee Creation Authority Management (Super Admin Only)
    Route::get('/authority', [App\Http\Controllers\Admin\AuthorityController::class, 'index'])->name('authority');
    Route::post('/authority/admin/{adminId}/grant', [App\Http\Controllers\Admin\AuthorityController::class, 'grantAdminAuthority'])->name('authority.admin.grant');
    Route::post('/authority/admin/{adminId}/revoke', [App\Http\Controllers\Admin\AuthorityController::class, 'revokeAdminAuthority'])->name('authority.admin.revoke');
    Route::post('/authority/finance/{financeId}/grant', [App\Http\Controllers\Admin\AuthorityController::class, 'grantFinanceAuthority'])->name('authority.finance.grant');
    Route::post('/authority/finance/{financeId}/revoke', [App\Http\Controllers\Admin\AuthorityController::class, 'revokeFinanceAuthority'])->name('authority.finance.revoke');
});

// Dashboard Data API (for auto-refresh)
Route::middleware('auth')->prefix('employee')->group(function () {
    Route::get('/dashboard-data', [App\Http\Controllers\Api\DashboardDataController::class, 'getEmployeeDashboard']);
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/dashboard-data', [App\Http\Controllers\Api\DashboardDataController::class, 'getAdminDashboard']);
});

// Payroll API (for auto-refresh)
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/payroll-data', [App\Http\Controllers\Api\PayrollDataController::class, 'getPayrollData']);
});

// Admin User Management Routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs');
    Route::get('/user-management', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('user-management');
    Route::get('/user-management/{role}/{id}', [App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('user-view');
    Route::get('/user-management/{role}/{id}/edit', [App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('user-edit');
    Route::put('/user-management/{role}/{id}', [App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('user-update');
    Route::post('/user-management/finance/{id}/approve', [App\Http\Controllers\Admin\UserManagementController::class, 'approveFinanceChanges'])->name('user-finance-approve');
    Route::post('/user-management/finance/{id}/reject', [App\Http\Controllers\Admin\UserManagementController::class, 'rejectFinanceChanges'])->name('user-finance-reject');
    Route::delete('/user-management/{role}/{id}', [App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('user-delete');
    Route::post('/user-management/{id}/change-role', [App\Http\Controllers\Admin\UserManagementController::class, 'changeRole'])->name('user-change-role');
});

// Reactivate deactivated account
Route::middleware('auth')->post('/admin/verify/{userId}/reactivate', [App\Http\Controllers\Admin\VerificationController::class, 'reactivate'])->name('admin.verify.reactivate');

// Permanent delete user (admin only)
Route::middleware('auth')->delete('/admin/verify/{userId}/delete', [App\Http\Controllers\Admin\VerificationController::class, 'permanentDelete'])->name('admin.verify.delete');

// Reports Routes
Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/payroll', function () {
        // Generate payroll report for main office
        return redirect()->back()->with('info', 'Payroll report generation coming soon.');
    })->name('payroll');
    
    Route::get('/attendance', function () {
        return redirect()->back()->with('info', 'Attendance report generation coming soon.');
    })->name('attendance');
});

// Finance Reports Routes
Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/bulk-payslips/{periodId}', [App\Http\Controllers\Finance\ReportController::class, 'bulkPayslips'])->name('bulk-payslips');
    Route::get('/monthly', [App\Http\Controllers\Finance\ReportController::class, 'monthlyReport'])->name('monthly');
    Route::get('/contributions', [App\Http\Controllers\Finance\ReportController::class, 'contributionReport'])->name('contributions');
    Route::get('/tax', [App\Http\Controllers\Finance\ReportController::class, 'taxReport'])->name('tax');
    Route::get('/bir-annual', [App\Http\Controllers\Finance\ReportController::class, 'birAnnual'])->name('bir-annual');
    Route::get('/payslip/{entryId}', [App\Http\Controllers\Finance\ReportController::class, 'viewPayslip'])->name('payslip');
});

// Finance Reports Routes (PDF)
Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/monthly-pdf', [App\Http\Controllers\Finance\ReportController::class, 'monthlyReport'])->name('monthly-pdf');
    Route::get('/contributions-pdf', [App\Http\Controllers\Finance\ReportController::class, 'contributionReport'])->name('contributions-pdf');
    Route::get('/tax-pdf', [App\Http\Controllers\Finance\ReportController::class, 'taxReport'])->name('tax-pdf');
});

// Employee Payslips Route
Route::middleware('auth')->get('/my-payslips', [App\Http\Controllers\Admin\PayrollController::class, 'myPayslips'])->name('employee.payslips');

// Biometric Setup Page (Admin only)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/biometric', function () {
        return view('admin.biometric');
    })->name('admin.biometric');
});

// Biometric Setup Page (Admin only - simplified)
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/biometric', function () {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Admin only.');
        }
        return view('admin.biometric');
    })->name('admin.biometric');
});

// Finance Employee Management
Route::middleware(['auth'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/employees', [App\Http\Controllers\Finance\EmployeeController::class, 'index'])->name('employees');
    Route::get('/employee/{id}/attendance', [App\Http\Controllers\Finance\EmployeeController::class, 'attendance'])->name('employee.attendance');
});
