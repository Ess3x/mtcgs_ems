<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\BiometricController;
use App\Http\Controllers\Api\DeductionController;
use App\Http\Controllers\Api\MacAddressController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/ping', fn () => response()->json(['success' => true]));

// Self-service attendance (no auth required for clocking in/out)
Route::post('/attendance/clock', [App\Http\Controllers\Api\AttendanceController::class, 'clockAttendance']);

// Fingerprint scanner time-in/time-out (no auth required)
Route::post('/biometric/time-clock', [BiometricController::class, 'processTimeClock']);
Route::get('/biometric/check-attendance', [BiometricController::class, 'checkAttendance']);
Route::get('/biometric/templates', [BiometricController::class, 'getFingerprintTemplates']);
Route::get('/biometric/dtr-today', [BiometricController::class, 'getTodayDTR']);
Route::get('/biometric/recent-employees', [BiometricController::class, 'getRecentEmployeesAttendance']);
Route::post('/biometric/verify', [BiometricController::class, 'verifyFingerprint']);

// Temporary fingerprint storage during employee enrollment
Route::post('/fingerprint-register-temp', [BiometricController::class, 'registerFingerprintTemp']);
Route::get('/fingerprint-temp', [BiometricController::class, 'getFingerprintTemp']);
Route::post('/fingerprint-check-status', [BiometricController::class, 'checkFingerprintStatus']);

// MAC Address Validation (no auth required - called from C# application)
Route::post('/validate-mac-address', [MacAddressController::class, 'validateMacAddress']);
Route::get('/device-info', [MacAddressController::class, 'getDeviceInfo']);

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Biometric attendance and registration for web-authenticated users
    Route::post('/biometric/register', [BiometricController::class, 'registerFingerprint']);
    Route::post('/biometric/register-by-number', [BiometricController::class, 'registerFingerprintByEmployeeNumber']);
    Route::post('/biometric/process', [BiometricController::class, 'processAttendance']);
    Route::get('/biometric/employees', [BiometricController::class, 'getEmployeesStatus']);
    Route::get('/biometric/unregistered-employees', [App\Http\Controllers\Api\BiometricController::class, 'getUnregisteredEmployees']);

    Route::post('/biometric/register-finance', [App\Http\Controllers\Api\BiometricController::class, 'registerFinanceFingerprint']);
    Route::post('/biometric/finance-attendance', [App\Http\Controllers\Api\BiometricController::class, 'processFinanceAttendance']);
    Route::get('/biometric/finance-status', [App\Http\Controllers\Api\BiometricController::class, 'getFinanceStatus']);

    Route::post('/biometric/register-admin', [App\Http\Controllers\Api\BiometricController::class, 'registerAdminFingerprint']);
    Route::post('/biometric/admin-attendance', [App\Http\Controllers\Api\BiometricController::class, 'processAdminAttendance']);
    Route::get('/biometric/admin-status', [App\Http\Controllers\Api\BiometricController::class, 'getAdminStatus']);

    Route::post('/biometric/register-finance-officer', [App\Http\Controllers\Api\BiometricController::class, 'registerFinanceOfficerFingerprint']);
    Route::post('/biometric/register-admin-officer', [App\Http\Controllers\Api\BiometricController::class, 'registerAdminOfficerFingerprint']);
    Route::get('/biometric/unregistered-finance-officers', [App\Http\Controllers\Api\BiometricController::class, 'getUnregisteredFinanceOfficers']);
    Route::get('/biometric/unregistered-admins', [App\Http\Controllers\Api\BiometricController::class, 'getUnregisteredAdmins']);

    // Attendance flow for finance users from the web dashboard
    Route::post('/biometric/attendance', [App\Http\Controllers\Api\BiometricController::class, 'processAttendance']);

    // MAC Address Device Management (Admin only)
    Route::post('/devices/register', [MacAddressController::class, 'registerDevice']);
    Route::get('/devices/list', [MacAddressController::class, 'listDevices']);
    Route::post('/devices/{device}/status', [MacAddressController::class, 'updateDeviceStatus']);
});

// Deduction Management Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/deductions/preview', [DeductionController::class, 'getDeductionPreview']);
    Route::post('/deductions/add', [DeductionController::class, 'addDeduction']);
    Route::put('/deductions/{deductionId}', [DeductionController::class, 'updateDeduction']);
    Route::delete('/deductions/{deductionId}/remove', [DeductionController::class, 'removeDeduction']);
    Route::post('/deductions/calculate-net-pay', [DeductionController::class, 'calculateEstimatedNetPay']);
});
