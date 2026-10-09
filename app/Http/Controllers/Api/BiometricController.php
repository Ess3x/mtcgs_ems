<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use App\Models\BranchHeadProfile;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\AuditLog;
use App\Services\BiometricDeviceAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BiometricController extends Controller
{
    // Register employee fingerprint (Admin or Finance Head)
    public function registerFingerprint(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employee_profiles,id',
            'fingerprint_data' => 'required|string',
        ]);

        $user = auth()->user();
        
        // Finance Officers can view the employee list but cannot register fingerprints.
        if ($user->role !== 'admin' && $user->role !== 'finance_head') {
            return response()->json(['error' => 'Only Admin or Finance Head can register fingerprints'], 403);
        }

        $employee = EmployeeProfile::findOrFail($request->employee_id);
        if ($user->role === 'admin' && $user->admin_type === 'branch_admin'
            && (int) $employee->branch_id !== (int) $user->getEffectiveBranchId()) {
            return response()->json(['error' => 'You can only register fingerprints for employees in your branch.'], 403);
        }
        $duplicate = $this->findFingerprintOwner($request->fingerprint_data, $employee);
        if ($duplicate) {
            return $this->duplicateFingerprintResponse($duplicate);
        }
        $employee->fingerprint_template = $this->appendFingerprintTemplate(
            $employee->fingerprint_template,
            $request->fingerprint_data
        );
        $employee->is_fingerprint_registered = true;
        $employee->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Fingerprint registered for ' . $employee->first_name . ' ' . $employee->last_name
        ]);
    }

    public function registerFingerprintByEmployeeNumber(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string',
            'fingerprint_data' => 'required|string',
        ]);

        $employee = EmployeeProfile::where('employee_number', $request->employee_number)->first();
        if (!$employee) {
            $employee = FinanceProfile::where('employee_number', $request->employee_number)->first();
        }
        if (!$employee) {
            $employee = AdminProfile::where('employee_number', $request->employee_number)->first();
        }

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee number not found in the system.'
            ], 404);
        }

        $employee->fingerprint_template = $this->appendFingerprintTemplate(
            $employee->fingerprint_template,
            $request->fingerprint_data
        );
        $employee->is_fingerprint_registered = true;

        $duplicate = $this->findFingerprintOwner($request->fingerprint_data, $employee);
        if ($duplicate) {
            return $this->duplicateFingerprintResponse($duplicate);
        }
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Fingerprint registered for ' . ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''),
            'employee_number' => $employee->employee_number,
        ]);
    }
    
    // Register Admin's own fingerprint
    public function registerAdminFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
        ]);

        $user = auth()->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Only Admin can register their own fingerprint'], 403);
        }
        
        $profile = $user->getAdminProfile();
        if ($profile) {
            $duplicate = $this->findFingerprintOwner($request->fingerprint_data, $profile);
            if ($duplicate) {
                return $this->duplicateFingerprintResponse($duplicate);
            }
            $profile->fingerprint_template = $this->appendFingerprintTemplate(
                $profile->fingerprint_template,
                $request->fingerprint_data
            );
            $profile->is_fingerprint_registered = true;
            $profile->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Your fingerprint has been registered successfully'
            ]);
        }
        
        return response()->json(['error' => 'Admin profile not found'], 404);
    }
    
    // Register Finance Officer's own fingerprint
    public function registerFinanceFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
        ]);

        $user = auth()->user();
        
        if (!in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            return response()->json(['error' => 'Only Finance staff can register their own fingerprint'], 403);
        }
        
        $profile = $user->getFinanceProfile();
        if ($profile) {
            $duplicate = $this->findFingerprintOwner($request->fingerprint_data, $profile);
            if ($duplicate) {
                return $this->duplicateFingerprintResponse($duplicate);
            }
            $profile->fingerprint_template = $this->appendFingerprintTemplate(
                $profile->fingerprint_template,
                $request->fingerprint_data
            );
            $profile->is_fingerprint_registered = true;
            $profile->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Your fingerprint has been registered successfully'
            ]);
        }
        
        return response()->json(['error' => 'Finance profile not found'], 404);
    }
    
    private function logBiometricAttempt(Request $request, string $action, array $details = [], ?string $error = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => 'biometric_request',
            'auditable_id' => $request->input('employee_id') ?? $request->input('employee_number') ?? null,
            'old_values' => $error ? ['error' => $error] : null,
            'new_values' => [
                'employee_number' => $request->input('employee_number'),
                'device_serial' => $request->input('device_serial'),
                'wifi_mac' => $request->input('wifi_mac') ?: $request->input('mac_address'),
                'laptop_mac' => $request->input('laptop_mac'),
                'timestamp' => $request->input('timestamp'),
                'nonce' => $request->input('nonce'),
                'details' => $details,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);
    }

    private function findFingerprintOwner(string $incomingTemplate, $ignoreProfile = null): ?array
    {
        foreach ([EmployeeProfile::class, FinanceProfile::class, AdminProfile::class, BranchHeadProfile::class] as $profileType) {
            foreach ($profileType::query()->whereNotNull('fingerprint_template')->where('fingerprint_template', '!=', '')->get() as $profile) {
                if ($ignoreProfile
                    && get_class($ignoreProfile) === get_class($profile)
                    && (int) $ignoreProfile->id === (int) $profile->id) {
                    continue;
                }

                if ($this->templatesMatch($profile->fingerprint_template, $incomingTemplate)) {
                    return [
                        'name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: 'another user',
                        'employee_number' => $profile->employee_number ?? null,
                    ];
                }
            }
        }

        return null;
    }

    private function duplicateFingerprintResponse(array $duplicate)
    {
        $identifier = $duplicate['employee_number'] ? ' #' . $duplicate['employee_number'] : '';

        return response()->json([
            'success' => false,
            'error' => 'Fingerprint already registered.',
            'message' => 'This fingerprint is already registered to ' . $duplicate['name'] . $identifier . '.',
        ], 409);
    }

    private function validateDeviceIdentity(Request $request): ?array
    {
        return app(BiometricDeviceAuthenticator::class)->authenticate($request);
    }

    private function resolveDeviceFromRequest(Request $request): ?Device
    {
        $serial = Device::normalizeSerialNumber($request->input('device_serial'));
        $macAddress = Device::normalizeMacAddress($request->input('mac_address'));

        if ($serial === '' || strlen($macAddress) !== 12) {
            return null;
        }

        $device = Device::getBySerialNumber($serial);
        if (!$device || !hash_equals(Device::normalizeMacAddress($device->mac_address), $macAddress)) {
            return null;
        }

        return $device;
    }

    private function attachDeviceMetadata(AttendanceLog $attendance, ?Device $device): void
    {
        if (!$device) {
            return;
        }

        $attendance->device_id = $device->id;
        $attendance->device_mac_address = $device->mac_address;
        $attendance->device_serial_number = $device->serial_number;
        $attendance->location = $device->location;
        $device->updateLastUsed();
    }

    // Process attendance for admin (same as employee)
    public function processAdminAttendance(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'device_serial' => 'nullable|string',
            'mac_address' => 'nullable|string',
            'wifi_mac' => 'nullable|string',
            'laptop_mac' => 'nullable|string',
            'timestamp' => 'nullable|string',
            'nonce' => 'nullable|string',
            'signature' => 'nullable|string',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['error' => $deviceValidation['error']], $deviceValidation['code']);
        }

        $device = $deviceValidation['device'] ?? null;
        
        // Find admin by fingerprint
        $incomingFingerprint = $this->fingerprintBytes($request->fingerprint_data);
        $admin = AdminProfile::where('is_fingerprint_registered', true)
            ->whereNotNull('fingerprint_template')->get()
            ->first(fn ($profile) => $this->templatesMatch($profile->fingerprint_template, $incomingFingerprint));
        
        if (!$admin) {
            return response()->json(['error' => 'Fingerprint not recognized'], 401);
        }
        
        // Get the linked employee profile
        $employeeProfile = EmployeeProfile::find($admin->employee_profile_id);
        
        if (!$employeeProfile) {
            return response()->json(['error' => 'Employee profile not linked for this admin'], 400);
        }
        
        $attendance = AttendanceLog::firstOrNew([
            'employee_profile_id' => $employeeProfile->id,
            'attendance_date' => today(),
        ]);
        
        $attendance->employee_profile_id = $employeeProfile->id;
        $attendance->employee_id = $employeeProfile->user_id ?? $employeeProfile->id;
        $attendance->branch_id = $admin->branch_id ?? 1;
        $attendance->attendance_date = today();
        
        $now = now();
        $currentHour = (int)date('H', strtotime($now));
        $message = '';
        $action = '';
        
        // Same logic as employee
        if (!$attendance->am_in && $currentHour >= 6 && $currentHour < 12) {
            $attendance->am_in = $now;
            $standardIn = $this->scheduledTime($employeeProfile, 'start_time', 8);
            $lateMinutes = \App\Services\AttendanceTimeRules::lateMinutes($now, $standardIn);
            $attendance->late_minutes = $lateMinutes;
            if ($lateMinutes > 0) {
                $attendance->status = 'late';
                $message = "AM In recorded (LATE by {$lateMinutes} minutes)";
            } else {
                $attendance->status = 'present';
                $message = "AM In recorded (On time)";
            }
            $action = 'AM In';
        }
        elseif ($attendance->am_in && !$attendance->am_out && $currentHour >= 12 && $currentHour < 14) {
            $attendance->am_out = $now;
            $message = "Lunch Out recorded";
            $action = 'AM Out';
        }
        elseif ($attendance->am_out && !$attendance->pm_in && $currentHour >= 13 && $currentHour < 16) {
            $attendance->pm_in = $now;
            $message = "PM In recorded";
            $action = 'PM In';
        }
        elseif (($attendance->pm_in || $attendance->am_in) && !$attendance->pm_out && $currentHour >= 16) {
            $attendance->pm_out = $now;
            $standardOut = $this->scheduledTime($employeeProfile, 'end_time', 17);
            $timeoutStatus = $this->evaluateTimeOutStatus($now, $standardOut);
            $attendance->overtime_hours = $timeoutStatus['overtime_hours'];

            if ($timeoutStatus['is_early_out']) {
                $message = "PM Out recorded (EARLY OUT by {$timeoutStatus['early_out_minutes']} minutes)";
            } elseif ($timeoutStatus['overtime_hours'] > 0) {
                $message = "PM Out recorded (OVERTIME: {$attendance->overtime_hours} hours)";
            } else {
                $message = "PM Out recorded";
            }

            $attendance->status = $attendance->status === 'late' ? 'late' : 'present';
            $action = 'PM Out';
        }
        else {
            if (!$attendance->am_in) {
                $message = "Please record AM In first (6:00 AM - 11:59 AM)";
            } elseif (!$attendance->am_out && $currentHour < 12) {
                $message = "AM Out will be available at 12:00 PM - 1:30 PM";
            } elseif (!$attendance->am_out && $currentHour < 14) {
                $message = "Please record AM Out (Lunch break)";
            } elseif (!$attendance->pm_in && $currentHour < 16) {
                $message = "Please record PM In (After lunch)";
            } elseif (!$attendance->pm_out) {
                $message = "Please record PM Out after 4:00 PM";
            } else {
                $message = "Attendance already completed for today";
            }
            return response()->json(['error' => $message, 'action' => 'waiting'], 400);
        }
        
        $attendance->verification_method = 'fingerprint';
        
        // Store MAC address if provided (Admin Attendance)
        $this->attachDeviceMetadata($attendance, $device ?: $this->resolveDeviceFromRequest($request));
        
        $attendance->save();

        // Keep the DTR synchronized with the recorded fingerprint attendance.
        \App\Models\DTR::generateForCurrentPeriod($employeeProfile->id, $attendance->attendance_date);

        return response()->json([
            'success' => true,
            'message' => $message,
            'action' => $action,
            'data' => [
                'employee_name' => $employeeProfile->first_name . ' ' . $employeeProfile->last_name,
                'am_in' => $attendance->am_in ? date('h:i A', strtotime($attendance->am_in)) : '--',
                'am_out' => $attendance->am_out ? date('h:i A', strtotime($attendance->am_out)) : '--',
                'pm_in' => $attendance->pm_in ? date('h:i A', strtotime($attendance->pm_in)) : '--',
                'pm_out' => $attendance->pm_out ? date('h:i A', strtotime($attendance->pm_out)) : '--',
                'late_minutes' => $attendance->late_minutes,
                'overtime_hours' => $attendance->overtime_hours,
                'device_id' => $attendance->device_id,
                'device_serial_number' => $attendance->device_serial_number,
                'location' => $attendance->location,
            ]
        ]);
    }
    
    // Process attendance for finance officer
    public function processFinanceAttendance(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'mac_address' => 'nullable|string',
            'wifi_mac' => 'nullable|string',
            'laptop_mac' => 'nullable|string',
            'device_serial' => 'nullable|string',
            'device_id' => 'nullable|string',
            'attendance_timestamp' => 'nullable|date',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric|min:0',
            'gps_timestamp' => 'nullable|date',
        ]);
        
        // Validate MAC address if provided
        $device = $this->resolveDeviceFromRequest($request);
        if ($request->filled('mac_address') || $request->filled('wifi_mac') || $request->filled('laptop_mac') || $request->filled('device_serial') || $request->filled('device_id')) {
            if (!$device) {
                return response()->json([
                    'error' => 'Device not authorized. MAC address not registered.',
                    'mac_address' => Device::normalizeMacAddress($request->input('mac_address') ?: $request->input('wifi_mac')),
                ], 401);
            }
        }
        
        $incomingFingerprint = $this->fingerprintBytes($request->fingerprint_data);
        $finance = FinanceProfile::where('is_fingerprint_registered', true)
            ->whereNotNull('fingerprint_template')->get()
            ->first(fn ($profile) => $this->templatesMatch($profile->fingerprint_template, $incomingFingerprint));
        
        if (!$finance) {
            return response()->json(['error' => 'Fingerprint not recognized'], 401);
        }
        
        $employeeProfile = EmployeeProfile::find($finance->employee_profile_id);
        
        if (!$employeeProfile) {
            return response()->json(['error' => 'Employee profile not linked for this finance officer'], 400);
        }
        
        $now = now();
        if ($request->filled('timestamp')) {
            try {
                $now = \Carbon\Carbon::createFromFormat(
                    'm/d/Y h:i:s A',
                    strtoupper(trim($request->input('timestamp'))),
                    config('app.timezone')
                );
            } catch (\Throwable $exception) {
                $now = now();
            }
        }

        if (app(\App\Services\WorkingDayService::class)->isSuspension($now, $employeeProfile->branch_id)) {
            $message = 'Attendance is not allowed because work is suspended today.';
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => $message,
                'attendance_blocked' => true,
                'date' => $now->toDateString(),
            ], 403);
        }

        $attendance = AttendanceLog::firstOrNew([
            'employee_profile_id' => $employeeProfile->id,
            'attendance_date' => $now->toDateString(),
        ]);
        
        $attendance->employee_profile_id = $employeeProfile->id;
        $attendance->employee_id = $employeeProfile->user_id ?? $employeeProfile->id;
        $attendance->branch_id = $finance->branch_id;
        $attendance->attendance_date = $now->toDateString();
        $currentHour = (int)date('H', strtotime($now));
        $message = '';
        $action = '';
        
        if (!$attendance->am_in && $currentHour >= 6 && $currentHour < 12) {
            $attendance->am_in = $now;
            $standardIn = $this->scheduledTime($employeeProfile, 'start_time', 8);
            $lateMinutes = \App\Services\AttendanceTimeRules::lateMinutes($now, $standardIn);
            $attendance->late_minutes = $lateMinutes;
            if ($lateMinutes > 0) {
                $attendance->status = 'late';
                $message = "AM In recorded (LATE by {$lateMinutes} minutes)";
            } else {
                $attendance->status = 'present';
                $message = "AM In recorded (On time)";
            }
            $action = 'AM In';
        }
        elseif ($attendance->am_in && !$attendance->am_out && $currentHour >= 12 && $currentHour < 14) {
            $attendance->am_out = $now;
            $message = "Lunch Out recorded";
            $action = 'AM Out';
        }
        elseif ($attendance->am_out && !$attendance->pm_in && $currentHour >= 13 && $currentHour < 16) {
            $attendance->pm_in = $now;
            $message = "PM In recorded";
            $action = 'PM In';
        }
        elseif (($attendance->pm_in || $attendance->am_in) && !$attendance->pm_out && $currentHour >= 16) {
            $attendance->pm_out = $now;
            $standardOut = $this->scheduledTime($employeeProfile, 'end_time', 17);
            $timeoutStatus = $this->evaluateTimeOutStatus($now, $standardOut);
            $attendance->overtime_hours = $timeoutStatus['overtime_hours'];

            if ($timeoutStatus['is_early_out']) {
                $message = "PM Out recorded (EARLY OUT by {$timeoutStatus['early_out_minutes']} minutes)";
            } elseif ($timeoutStatus['overtime_hours'] > 0) {
                $message = "PM Out recorded (OVERTIME: {$attendance->overtime_hours} hours)";
            } else {
                $message = "PM Out recorded";
            }

            $attendance->status = $attendance->status === 'late' ? 'late' : 'present';
            $action = 'PM Out';
        }
        else {
            if (!$attendance->am_in) {
                $message = "Please record AM In first (6:00 AM - 11:59 AM)";
            } elseif (!$attendance->am_out && $currentHour < 12) {
                $message = "AM Out will be available at 12:00 PM - 1:30 PM";
            } elseif (!$attendance->am_out && $currentHour < 14) {
                $message = "Please record AM Out (Lunch break)";
            } elseif (!$attendance->pm_in && $currentHour < 16) {
                $message = "Please record PM In (After lunch)";
            } elseif (!$attendance->pm_out) {
                $message = "Please record PM Out after 4:00 PM";
            } else {
                $message = "Attendance already completed for today";
            }
            return response()->json(['error' => $message, 'action' => 'waiting'], 400);
        }
        
        $attendance->verification_method = 'fingerprint';
        
        // Store MAC address if provided (Finance Attendance)
        $this->attachDeviceMetadata($attendance, $device);
        $attendance->gps_latitude = $request->input('gps_latitude');
        $attendance->gps_longitude = $request->input('gps_longitude');
        $attendance->gps_accuracy = $request->input('gps_accuracy');
        $attendance->gps_timestamp = $request->input('gps_timestamp');
        
        $attendance->save();

        // Keep the DTR synchronized with the recorded fingerprint attendance.
        \App\Models\DTR::generateForCurrentPeriod($employeeProfile->id, $attendance->attendance_date);

        return response()->json([
            'success' => true,
            'message' => $message,
            'action' => $action,
            'data' => [
                'employee_name' => $employeeProfile->first_name . ' ' . $employeeProfile->last_name,
                'am_in' => $attendance->am_in ? date('h:i A', strtotime($attendance->am_in)) : '--',
                'am_out' => $attendance->am_out ? date('h:i A', strtotime($attendance->am_out)) : '--',
                'pm_in' => $attendance->pm_in ? date('h:i A', strtotime($attendance->pm_in)) : '--',
                'pm_out' => $attendance->pm_out ? date('h:i A', strtotime($attendance->pm_out)) : '--',
                'late_minutes' => $attendance->late_minutes,
                'overtime_hours' => $attendance->overtime_hours,
                'device_id' => $attendance->device_id,
                'device_serial_number' => $attendance->device_serial_number,
                'location' => $attendance->location,
            ]
        ]);
    }
    
    // Verify a scanned fingerprint template and return the matched employee information.
    public function verifyFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['success' => false, 'message' => $deviceValidation['error']], $deviceValidation['code']);
        }

        $device = $deviceValidation['device'];
        if ($device->branch_id === null) {
            return response()->json(['success' => false, 'message' => 'This device is not assigned to a branch.'], 403);
        }

        $scannedTemplate = $this->fingerprintBytes($request->fingerprint_data);
        $profile = EmployeeProfile::where('branch_id', $device->branch_id)
            ->where(function ($query) {
                $query->where('is_fingerprint_registered', true)
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('fingerprint_template')
                            ->where('fingerprint_template', '!=', '')
                            ->whereRaw('LOWER(TRIM(fingerprint_template)) != "null"');
                    });
            })->get()->first(fn ($item) => $this->templatesMatch($item->fingerprint_template, $scannedTemplate));

        if (!$profile) {
            $profile = FinanceProfile::where('branch_id', $device->branch_id)
                ->where(function ($query) {
                    $query->where('is_fingerprint_registered', true)
                        ->orWhere(function ($subQuery) {
                            $subQuery->whereNotNull('fingerprint_template')
                                ->where('fingerprint_template', '!=', '')
                                ->whereRaw('LOWER(TRIM(fingerprint_template)) != "null"');
                        });
                })->get()->first(fn ($item) => $this->templatesMatch($item->fingerprint_template, $scannedTemplate));
        }

        if (!$profile) {
            $profile = AdminProfile::where('branch_id', $device->branch_id)
                ->where(function ($query) {
                    $query->where('is_fingerprint_registered', true)
                        ->orWhere(function ($subQuery) {
                            $subQuery->whereNotNull('fingerprint_template')
                                ->where('fingerprint_template', '!=', '')
                                ->whereRaw('LOWER(TRIM(fingerprint_template)) != "null"');
                        });
                })->get()->first(fn ($item) => $this->templatesMatch($item->fingerprint_template, $scannedTemplate));
        }

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Fingerprint not recognized.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fingerprint matched.',
            'employee_number' => $profile->employee_number ?? null,
            'employee_name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: 'Unknown',
            'branch' => $profile->branch ? $profile->branch->branch_name : ($profile->branch_name ?? 'N/A'),
            'position' => $profile->position ?? 'N/A',
            'role' => $profile->user?->role ?? null,
        ]);
    }

    public function templatesMatch($storedTemplate, $incomingTemplate): bool
    {
        foreach ($this->fingerprintTemplateList($storedTemplate) as $storedItem) {
            if ($this->singleTemplateMatches($storedItem, $incomingTemplate)) {
                return true;
            }
        }

        return false;
    }

    private function singleTemplateMatches($storedTemplate, $incomingTemplate): bool
    {
        $stored = $this->isBinaryTemplate($storedTemplate)
            ? (string) $storedTemplate
            : $this->normalizeFingerprintTemplate((string) $storedTemplate);
        $incoming = $this->isBinaryTemplate($incomingTemplate)
            ? (string) $incomingTemplate
            : $this->normalizeFingerprintTemplate((string) $incomingTemplate);

        if ($stored === '' || $incoming === '') {
            return false;
        }

        $storedBytes = $this->fingerprintBytes($stored);
        $incomingBytes = $this->fingerprintBytes($incoming);

        if ($storedBytes !== '' && $incomingBytes !== '' && $storedBytes === $incomingBytes) {
            return true;
        }

        $storedNormalized = $this->normalizeFingerprintTemplate(base64_encode($storedBytes));
        $incomingNormalized = $this->normalizeFingerprintTemplate(base64_encode($incomingBytes));

        return hash_equals($storedNormalized, $incomingNormalized)
            || hash_equals($stored, $incoming)
            || hash_equals($storedBytes, $incomingBytes);
    }

    private function fingerprintTemplateList($storedTemplate): array
    {
        $stored = (string) $storedTemplate;
        $decoded = json_decode($stored, true);

        if (is_array($decoded) && isset($decoded['templates']) && is_array($decoded['templates'])) {
            return array_values(array_filter($decoded['templates'], 'is_string'));
        }

        if ($stored === '') {
            return [];
        }

        $storedBytes = $this->fingerprintBytes($stored);
        return [$storedBytes !== '' ? base64_encode($storedBytes) : $stored];
    }

    private function appendFingerprintTemplate($storedTemplate, $incomingTemplate): string
    {
        $incomingBytes = $this->fingerprintBytes($incomingTemplate);
        if ($incomingBytes === '') {
            return (string) $storedTemplate;
        }

        $templates = $this->fingerprintTemplateList($storedTemplate);
        foreach ($templates as $template) {
            if ($this->singleTemplateMatches($template, $incomingBytes)) {
                return (string) $storedTemplate;
            }
        }

        if (count($templates) === 0) {
            return $incomingBytes;
        }

        $templates[] = base64_encode($incomingBytes);
        return json_encode([
            'version' => 1,
            'templates' => $templates,
        ], JSON_UNESCAPED_SLASHES);
    }

    private function normalizeFingerprintTemplate(string $template): string
    {
        $template = trim((string) $template);

        if (str_starts_with($template, 'data:') && str_contains($template, ',')) {
            $template = substr($template, strpos($template, ',') + 1);
        }

        return preg_replace('/\s+/', '', $template) ?? '';
    }

    private function scheduledTime($profile, string $field, int $fallbackHour, ?\Carbon\Carbon $date = null): \Carbon\Carbon
    {
        $date = $date?->copy() ?: \Carbon\Carbon::today();

        return $profile->shift
            ? $date->setTimeFromTimeString($profile->shift->{$field})
            : $date->setTime($fallbackHour, 0, 0);
    }

    private function fingerprintBytes($value): string
    {
        $value = (string) $value;
        if ($this->isBinaryTemplate($value)) {
            return $value;
        }

        $normalized = $this->normalizeFingerprintTemplate($value);

        if ($normalized === '' || strlen($normalized) % 4 !== 0 ||
            !preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $normalized)) {
            return $value;
        }

        $decoded = base64_decode($normalized, true);

        return $decoded !== false && base64_encode($decoded) === $normalized ? $decoded : $value;
    }

    private function isBinaryTemplate($value): bool
    {
        $value = (string) $value;
        return $value !== '' && preg_match('//u', $value) !== 1;
    }

    private function fingerprintBase64($value): string
    {
        return base64_encode($this->fingerprintBytes($value));
    }

    private function preventDuplicateAction(AttendanceLog $attendance, string $action, string $message): ?string
    {
        if ($action === 'AM In' && !empty($attendance->am_in)) {
            return 'AM In already recorded for today.';
        }

        if ($action === 'AM Out' && !empty($attendance->am_out)) {
            return 'AM Out already recorded for today.';
        }

        if ($action === 'PM In' && !empty($attendance->pm_in)) {
            return 'PM In already recorded for today.';
        }

        if ($action === 'PM Out' && !empty($attendance->pm_out)) {
            return 'PM Out already recorded for today.';
        }

        return null;
    }

    private function attendanceBlockedReason($employee, $date, bool $allowSuspensionTimeOut = false): ?string
    {
        $day = $date instanceof \Carbon\Carbon
            ? $date->copy()
            : \Carbon\Carbon::parse($date);

        if ($day->isWeekend()) {
            return 'Attendance is not allowed on weekends.';
        }

        $workingDayService = app(\App\Services\WorkingDayService::class);
        if ($workingDayService->isSuspension($day, $employee->branch_id) && !$allowSuspensionTimeOut) {
            return 'Attendance is not allowed because work is suspended today.';
        }

        if ($workingDayService->isHoliday($day, $employee->branch_id)) {
            return 'Attendance is not allowed because today is a holiday.';
        }

        $leave = \App\Models\LeaveRequest::where('employee_profile_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->latest('id')
            ->first();

        if (!$leave) {
            return null;
        }

        return $leave->is_absent
            ? 'Attendance is not allowed because the employee is on leave without pay.'
            : 'Attendance is not allowed because the employee is on paid leave.';
    }

    public function evaluateTimeOutStatus($actualOut, $scheduledOut): array
    {
        $actual = $actualOut instanceof \Carbon\Carbon ? $actualOut : \Carbon\Carbon::parse($actualOut);
        $scheduled = $scheduledOut instanceof \Carbon\Carbon ? $scheduledOut : \Carbon\Carbon::parse($scheduledOut);

        $isEarlyOut = $actual->lt($scheduled);
        $overtimeMinutes = $actual->gt($scheduled) ? $scheduled->diffInMinutes($actual) : 0;
        $earlyOutMinutes = $isEarlyOut ? $scheduled->diffInMinutes($actual) : 0;

        return [
            'status' => 'present',
            'is_early_out' => $isEarlyOut,
            'early_out_minutes' => $earlyOutMinutes,
            'overtime_hours' => round($overtimeMinutes / 60, 2),
        ];
    }

    // Process regular employee attendance
    public function processAttendance(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'mac_address' => 'nullable|string',
            'wifi_mac' => 'nullable|string',
            'laptop_mac' => 'nullable|string',
            'device_serial' => 'nullable|string',
            'device_id' => 'nullable|integer',
        ]);
        
        // Validate MAC address if provided
        $device = $this->resolveDeviceFromRequest($request);
        if ($request->filled('mac_address') || $request->filled('wifi_mac') || $request->filled('laptop_mac') || $request->filled('device_serial') || $request->filled('device_id')) {
            if (!$device) {
                return response()->json([
                    'error' => 'Device not authorized. MAC address not registered.',
                    'mac_address' => Device::normalizeMacAddress($request->input('mac_address') ?: $request->input('wifi_mac')),
                ], 401);
            }
        }
        
        $incomingFingerprint = $this->fingerprintBytes($request->fingerprint_data);
        $employee = EmployeeProfile::where('is_fingerprint_registered', true)
            ->whereNotNull('fingerprint_template')->get()
            ->first(fn ($profile) => $this->templatesMatch($profile->fingerprint_template, $incomingFingerprint));
        
        if (!$employee) {
            return response()->json(['error' => 'Fingerprint not recognized'], 401);
        }
        
        $now = now();
        if ($request->filled('timestamp')) {
            try {
                $now = \Carbon\Carbon::createFromFormat(
                    'm/d/Y h:i:s A',
                    strtoupper(trim($request->input('timestamp'))),
                    config('app.timezone')
                );
            } catch (\Throwable $exception) {
                $now = now();
            }
        }

        if (app(\App\Services\WorkingDayService::class)->isSuspension($now, $employee->branch_id)) {
            $message = 'Attendance is not allowed because work is suspended today.';
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => $message,
                'attendance_blocked' => true,
                'date' => $now->toDateString(),
            ], 403);
        }

        $attendance = AttendanceLog::firstOrNew([
            'employee_profile_id' => $employee->id,
            'attendance_date' => $now->toDateString(),
        ]);
        
        $attendance->employee_profile_id = $employee->id;
        $attendance->employee_id = $employee->id;
        $attendance->branch_id = $employee->branch_id;
        $attendance->attendance_date = $now->toDateString();
        $currentHour = (int)date('H', strtotime($now));
        $message = '';
        $action = '';
        
        if (!$attendance->am_in && $currentHour >= 6 && $currentHour < 12) {
            $duplicateReason = $this->preventDuplicateAction($attendance, 'AM In', 'AM In');
            if ($duplicateReason) {
                $this->logBiometricAttempt($request, 'biometric_duplicate_clock_in', ['employee_id' => $employee->id, 'attendance_date' => $attendance->attendance_date], $duplicateReason);
                return response()->json(['error' => $duplicateReason, 'action' => 'duplicate'], 409);
            }

            $attendance->am_in = $now;
            $standardIn = $this->scheduledTime($employee, 'start_time', 8);
            $lateMinutes = \App\Services\AttendanceTimeRules::lateMinutes($now, $standardIn);
            $attendance->late_minutes = $lateMinutes;
            if ($lateMinutes > 0) {
                $attendance->status = 'late';
                $message = "AM In recorded (LATE by {$lateMinutes} minutes)";
            } else {
                $attendance->status = 'present';
                $message = "AM In recorded (On time)";
            }
            $action = 'AM In';
        }
        elseif ($attendance->am_in && !$attendance->am_out && $currentHour >= 12 && $currentHour < 14) {
            $duplicateReason = $this->preventDuplicateAction($attendance, 'AM Out', 'AM Out');
            if ($duplicateReason) {
                $this->logBiometricAttempt($request, 'biometric_duplicate_clock_in', ['employee_id' => $employee->id, 'attendance_date' => $attendance->attendance_date], $duplicateReason);
                return response()->json(['error' => $duplicateReason, 'action' => 'duplicate'], 409);
            }

            $attendance->am_out = $now;
            $message = "Lunch Out recorded";
            $action = 'AM Out';
        }
        elseif ($attendance->am_out && !$attendance->pm_in && $currentHour >= 13 && $currentHour < 16) {
            $duplicateReason = $this->preventDuplicateAction($attendance, 'PM In', 'PM In');
            if ($duplicateReason) {
                $this->logBiometricAttempt($request, 'biometric_duplicate_clock_in', ['employee_id' => $employee->id, 'attendance_date' => $attendance->attendance_date], $duplicateReason);
                return response()->json(['error' => $duplicateReason, 'action' => 'duplicate'], 409);
            }

            $attendance->pm_in = $now;
            $message = "PM In recorded";
            $action = 'PM In';
        }
        elseif (($attendance->pm_in || $attendance->am_in) && !$attendance->pm_out && $currentHour >= 16) {
            $duplicateReason = $this->preventDuplicateAction($attendance, 'PM Out', 'PM Out');
            if ($duplicateReason) {
                $this->logBiometricAttempt($request, 'biometric_duplicate_clock_in', ['employee_id' => $employee->id, 'attendance_date' => $attendance->attendance_date], $duplicateReason);
                return response()->json(['error' => $duplicateReason, 'action' => 'duplicate'], 409);
            }

            $attendance->pm_out = $now;
            $standardOut = $this->scheduledTime($employee, 'end_time', 17);
            $timeoutStatus = $this->evaluateTimeOutStatus($now, $standardOut);
            $attendance->overtime_hours = $timeoutStatus['overtime_hours'];

            if ($timeoutStatus['is_early_out']) {
                $message = "PM Out recorded (EARLY OUT by {$timeoutStatus['early_out_minutes']} minutes)";
            } elseif ($timeoutStatus['overtime_hours'] > 0) {
                $message = "PM Out recorded (OVERTIME: {$attendance->overtime_hours} hours)";
            } else {
                $message = "PM Out recorded";
            }

            $attendance->status = $attendance->status === 'late' ? 'late' : 'present';
            $action = 'PM Out';
        }
        else {
            if (!$attendance->am_in) {
                $message = "Please record AM In first (6:00 AM - 11:59 AM)";
            } elseif (!$attendance->am_out && $currentHour < 12) {
                $message = "AM Out will be available at 12:00 PM - 1:30 PM";
            } elseif (!$attendance->am_out && $currentHour < 14) {
                $message = "Please record AM Out (Lunch break)";
            } elseif (!$attendance->pm_in && $currentHour < 16) {
                $message = "Please record PM In (After lunch)";
            } elseif (!$attendance->pm_out) {
                $message = "Please record PM Out after 4:00 PM";
            } else {
                $message = "Attendance already completed for today";
            }
            return response()->json(['error' => $message, 'action' => 'waiting'], 400);
        }
        
        $attendance->verification_method = 'fingerprint';
        
        // Store MAC address if provided (Employee Attendance)
        $this->attachDeviceMetadata($attendance, $device);
        
        $attendance->save();

        // Keep the current DTR synchronized with the attendance log so the recorded
        // time-in/time-out entries appear in the DTR table for the employee.
        \App\Models\DTR::generateForCurrentPeriod($employee->id, $attendance->attendance_date);

        return response()->json([
            'success' => true,
            'message' => $message,
            'action' => $action,
            'data' => [
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'am_in' => $attendance->am_in ? date('h:i A', strtotime($attendance->am_in)) : '--',
                'am_out' => $attendance->am_out ? date('h:i A', strtotime($attendance->am_out)) : '--',
                'pm_in' => $attendance->pm_in ? date('h:i A', strtotime($attendance->pm_in)) : '--',
                'pm_out' => $attendance->pm_out ? date('h:i A', strtotime($attendance->pm_out)) : '--',
                'late_minutes' => $attendance->late_minutes,
                'overtime_hours' => $attendance->overtime_hours,
                'device_id' => $attendance->device_id,
                'device_serial_number' => $attendance->device_serial_number,
                'location' => $attendance->location,
            ]
        ]);
    }
    
    // Get employees with their fingerprint status (for Admin/Super Admin/Finance Head)
    public function getUnregisteredEmployees(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin' && $user->role !== 'finance_head') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $branchId = null;
        if (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            $profile = $user->getFinanceProfile();
            $branchId = $profile->branch_id ?? null;
        } elseif ($user->role === 'admin' && $user->admin_type === 'branch_admin') {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId) {
                return response()->json(['error' => 'Branch admin has no assigned branch.'], 403);
            }
        }
        
        $query = EmployeeProfile::query()
            ->whereNotIn('id', FinanceProfile::query()->select('employee_profile_id')->whereNotNull('employee_profile_id'))
            ->whereDoesntHave('user', function ($userQuery) {
                $userQuery->whereIn('role', ['finance_officer', 'finance_head']);
            });

        if (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            $query->where('branch_id', $branchId);
        } elseif ($user->role === 'admin' && $user->admin_type === 'branch_admin') {
            $query->where('branch_id', $branchId);
        }

        $employees = $query->with('branch')->get()->map(function($employee) {
            return [
                'id' => $employee->id,
                'profile_type' => 'employee',
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
                'branch_id' => $employee->branch_id,
                'branch_name' => $employee->branch?->branch_name ?? 'Unknown Branch',
                'profile_photo_url' => $employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo)
                    ? asset('storage/' . ltrim($employee->profile_photo, '/') . '?v=' . $employee->updated_at?->timestamp)
                    : null,
                'is_fingerprint_registered' => (bool) $employee->is_fingerprint_registered,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $employees,
            'count' => $employees->count()
        ]);
    }
    
    // Get finance officers with their fingerprint status (for Super Admin)
    public function getUnregisteredFinanceOfficers(Request $request)
    {
        $user = auth()->user();

        $isBranchAdmin = $user->role === 'admin' && $user->admin_type === 'branch_admin';
        if (!$user->isSuperAdmin() && !$isBranchAdmin) {
            return response()->json(['error' => 'Only Super Admin or Branch Admin can access this'], 403);
        }

        $branchId = $isBranchAdmin ? $user->getEffectiveBranchId() : null;
        if ($isBranchAdmin && !$branchId) {
            return response()->json(['error' => 'Branch admin has no assigned branch.'], 403);
        }

        $financeOfficers = FinanceProfile::with(['user', 'branch'])
            ->whereHas('user', fn ($query) => $query->where('role', 'finance_officer'))
            ->when($isBranchAdmin, fn ($query) => $query->where('branch_id', $branchId))
            ->get()
            ->map(function($finance) {
            return [
                'id' => $finance->id,
                'profile_type' => 'financeprofile',
                'name' => $finance->first_name . ' ' . $finance->last_name,
                'email' => $finance->user->email ?? 'N/A',
                'employee_number' => $finance->employee_number,
                'branch_id' => $finance->branch_id,
                'branch_name' => $finance->branch?->branch_name ?? 'Unknown Branch',
                'profile_photo_url' => $finance->profile_photo && Storage::disk('public')->exists($finance->profile_photo)
                    ? asset('storage/' . ltrim($finance->profile_photo, '/') . '?v=' . $finance->updated_at?->timestamp)
                    : null,
                'role' => $finance->user?->role ?? 'finance_officer',
                'is_fingerprint_registered' => (bool) $finance->is_fingerprint_registered,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $financeOfficers,
            'count' => $financeOfficers->count()
        ]);
    }
    
    // Get admins with their fingerprint status (for Super Admin)
    public function getUnregisteredAdmins(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can access this'], 403);
        }
        
        $admins = AdminProfile::with(['user', 'branch'])
            ->where(function ($query) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('admin_type', 'branch_admin'))
                    ->orWhere('admin_level', 'branch_admin');
            })
            ->get()
            ->map(function($admin) {
            $userAdminType = $admin->user?->admin_type ?? $admin->admin_level ?? 'admin';
            $adminLevel = $admin->admin_level ?? $userAdminType;
            $isBranchAdmin = strtolower((string) ($userAdminType ?: $adminLevel)) === 'branch_admin'
                || strtolower((string) ($admin->admin_level ?? '')) === 'branch_admin';

            return [
                'id' => $admin->id,
                'profile_type' => 'adminprofile',
                'name' => $admin->first_name . ' ' . $admin->last_name,
                'email' => $admin->user->email ?? 'N/A',
                'employee_number' => $admin->employee_number,
                'admin_level' => $adminLevel,
                'admin_type' => $userAdminType,
                'branch_name' => $isBranchAdmin ? ($admin->branch?->branch_name ?? 'Unknown Branch') : null,
                'profile_photo_url' => $admin->profile_photo && Storage::disk('public')->exists($admin->profile_photo)
                    ? asset('storage/' . ltrim($admin->profile_photo, '/') . '?v=' . $admin->updated_at?->timestamp)
                    : null,
                'role' => $admin->user?->role ?? 'admin',
                'is_branch_admin' => $isBranchAdmin,
                'is_fingerprint_registered' => (bool) $admin->is_fingerprint_registered,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $admins,
            'count' => $admins->count()
        ]);
    }
    
    // Register finance officer fingerprint (for Super Admin)
    public function registerFinanceOfficerFingerprint(Request $request)
    {
        $request->validate([
            'finance_id' => 'required|exists:finance_profiles,id',
            'fingerprint_data' => 'required|string',
        ]);

        $user = auth()->user();
        
        // Only Super Admin can register finance officer fingerprints
        if (!$user->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can register finance officer fingerprints'], 403);
        }

        $finance = FinanceProfile::findOrFail($request->finance_id);
        if ($finance->user?->role === 'finance_head') {
            return response()->json(['error' => 'Finance Head fingerprint registration is not allowed'], 422);
        }

        $finance->fingerprint_template = $this->appendFingerprintTemplate(
            $finance->fingerprint_template,
            $request->fingerprint_data
        );
        $finance->is_fingerprint_registered = true;
        $finance->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Fingerprint registered for finance officer: ' . $finance->first_name . ' ' . $finance->last_name
        ]);
    }
    
    // Register admin fingerprint (for Super Admin)
    public function registerAdminOfficerFingerprint(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admin_profiles,id',
            'fingerprint_data' => 'required|string',
        ]);

        $user = auth()->user();
        
        // Only Super Admin can register admin fingerprints
        if (!$user->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can register admin fingerprints'], 403);
        }

        $admin = AdminProfile::findOrFail($request->admin_id);
        $userAdminType = strtolower((string) ($admin->user?->admin_type ?? ''));
        $profileAdminLevel = strtolower((string) ($admin->admin_level ?? ''));
        if ($userAdminType !== 'branch_admin' && $profileAdminLevel !== 'branch_admin') {
            return response()->json(['error' => 'HR and Super Admin fingerprint registration is not allowed'], 422);
        }

        $admin->fingerprint_template = $this->appendFingerprintTemplate(
            $admin->fingerprint_template,
            $request->fingerprint_data
        );
        $admin->is_fingerprint_registered = true;
        $admin->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Fingerprint registered for admin: ' . $admin->first_name . ' ' . $admin->last_name
        ]);
    }
    
    // Get Admin's fingerprint status
    public function getAdminStatus(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $profile = $user->getAdminProfile();
        
        return response()->json([
            'success' => true,
            'is_registered' => $profile ? $profile->is_fingerprint_registered : false,
            'message' => $profile && $profile->is_fingerprint_registered ? 
                'Your fingerprint is registered' : 
                'You need to register your fingerprint'
        ]);
    }

    public function getBridgeStatus(Request $request)
    {
        $path = env('MTCGS_ENROLL_EXE') ?: env('MTCGS_ENROLL_EXE', '');

        return response()->json([
            'success' => true,
            'enrollment_exe' => $path,
            'registry_file' => base_path('biometric/mtcgs-enroll.reg'),
        ]);
    }
    
    // Get Finance Officer's fingerprint status
    public function getFinanceStatus(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role !== 'finance_officer') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $profile = $user->getFinanceProfile();
        
        return response()->json([
            'success' => true,
            'is_registered' => $profile ? $profile->is_fingerprint_registered : false,
            'message' => $profile && $profile->is_fingerprint_registered ? 
                'Your fingerprint is registered' : 
                'You need to register your fingerprint'
        ]);
    }

    // Simplified time clock for fingerprint scanner (TIME-IN / TIME-OUT)
    public function getFingerprintTemplates(Request $request)
    {
        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json([
                'success' => false,
                'error' => $deviceValidation['error'],
            ], $deviceValidation['code']);
        }

        $device = $deviceValidation['device'];
        if ($device->branch_id === null) {
            return response()->json([
                'success' => false,
                'error' => 'This device is not assigned to a branch and cannot retrieve fingerprint templates.',
            ], 403);
        }

        $templates = EmployeeProfile::where('branch_id', $device->branch_id)
            ->where('is_fingerprint_registered', true)
            ->whereNotNull('fingerprint_template')
            ->with('branch:id,branch_name')
            ->when($request->filled('employee_number'), function ($query) use ($request) {
                $query->where('employee_number', trim($request->employee_number));
            })
            ->get(['employee_number', 'first_name', 'last_name', 'branch_id', 'position', 'fingerprint_template'])
            ->flatMap(function ($employee) {
                return collect($this->fingerprintTemplateList($employee->fingerprint_template))
                    ->map(function ($template) use ($employee) {
                        return [
                            'employee_number' => $employee->employee_number,
                            'first_name' => $employee->first_name,
                            'last_name' => $employee->last_name,
                            'branch_id' => $employee->branch_id,
                            'branch_name' => $employee->branch?->branch_name ?? 'N/A',
                            'position' => $employee->position,
                            'fingerprint_template' => base64_encode($this->fingerprintBytes($template)),
                        ];
                    });
            });

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    public function getAttendanceSuspensions(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'mac_address' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['success' => false, 'message' => $deviceValidation['error']], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];

        $startDate = \Carbon\Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = \Carbon\Carbon::parse($validated['end_date'])->startOfDay();
        if ($startDate->diffInDays($endDate) > 366) {
            return response()->json(['success' => false, 'message' => 'The requested date range cannot exceed one year.'], 422);
        }

        $dates = \App\Models\CalendarEvent::suspensions()
            ->approved()
            ->whereDate('event_date', '>=', $startDate->toDateString())
            ->whereDate('event_date', '<=', $endDate->toDateString())
            ->where(function ($query) use ($device) {
                $query->whereNull('branch_id');
                if ($device->branch_id !== null) {
                    $query->orWhere('branch_id', $device->branch_id);
                }
            })
            ->orderBy('event_date')
            ->pluck('event_date')
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'dates' => $dates,
        ]);
    }

    public function processTimeClock(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'action' => 'required|in:TIME-IN,TIME-OUT',
            'mac_address' => 'nullable|string',
            'wifi_mac' => 'nullable|string',
            'laptop_mac' => 'nullable|string',
            'device_serial' => 'nullable|string',
            'device_id' => 'nullable|string',
            'attendance_timestamp' => 'nullable|date',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric|min:0',
            'gps_timestamp' => 'nullable|date',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['error' => $deviceValidation['error']], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];
        
        // Find employee by fingerprint
        $incomingFingerprint = $this->fingerprintBytes($request->fingerprint_data);
        $employee = EmployeeProfile::where('is_fingerprint_registered', true)
            ->whereNotNull('fingerprint_template')
            ->get()
            ->first(function ($profile) use ($incomingFingerprint) {
                return $this->templatesMatch($profile->fingerprint_template, $incomingFingerprint);
            });
        
        if (!$employee) {
            return response()->json(['error' => 'Fingerprint not recognized'], 401);
        }

        if ($device->branch_id === null) {
            return response()->json([
                'success' => false,
                'error' => 'This device is not assigned to a branch and cannot record attendance.',
                'message' => 'This device is not assigned to a branch and cannot record attendance.',
            ], 403);
        }

        if ((int) $device->branch_id !== (int) $employee->branch_id) {
            $message = 'This device belongs to ' . $device->branch?->branch_name
                . '. This user cannot record attendance on this device.';

            return response()->json([
                'success' => false,
                'error' => $message,
                'message' => $message,
            ], 403);
        }

    $rawTimestamp = $request->input('attendance_timestamp') ?: $request->input('timestamp');
        $now = $rawTimestamp ? \Carbon\Carbon::parse($rawTimestamp)->setTimezone(config('app.timezone')) : now();
        $attendanceDate = $now->copy()->toDateString();
        $requestedAction = strtoupper(trim($request->input('action')));
        $attendance = AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();
        if (!$attendance) {
            $attendance = new AttendanceLog([
                'employee_profile_id' => $employee->id,
                'attendance_date' => $attendanceDate,
            ]);
        }
        $hasOpenAttendance = AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->whereNotNull('am_in')
            ->whereNull('pm_out')
            ->exists();
        $allowSuspensionTimeOut = $requestedAction === 'TIME-OUT'
            && $hasOpenAttendance;
        $nonWorkingDayResponse = $this->attendanceBlockedReason(
            $employee,
            $attendanceDate,
            (bool) $allowSuspensionTimeOut
        );
        if ($nonWorkingDayResponse) {
            return response()->json([
                'success' => false,
                'error' => $nonWorkingDayResponse,
                'message' => $nonWorkingDayResponse,
                'attendance_blocked' => true,
                'date' => $attendanceDate,
            ], 403);
        }

        $attendance->employee_profile_id = $employee->id;
        $attendance->employee_id = $employee->id;
        $attendance->branch_id = $employee->branch_id;
        $attendance->attendance_date = $attendanceDate;
        
        if ($requestedAction === 'TIME-IN') {
            $attendance->am_in = $now;
            $standardIn = $this->scheduledTime($employee, 'start_time', 8, $now);
            $lateMinutes = \App\Services\AttendanceTimeRules::lateMinutes($now, $standardIn);
            $attendance->late_minutes = $lateMinutes;
            if ($lateMinutes > 0) {
                $attendance->status = 'late';
                $message = "TIME-IN updated (LATE by {$lateMinutes} minutes)";
            } else {
                $attendance->status = 'present';
                $message = "TIME-IN updated (On time)";
            }
            $action = 'TIME-IN';
        } else {
            $attendance->pm_out = $now;
            $standardOut = $this->scheduledTime($employee, 'end_time', 17, $now);
            $timeoutStatus = $this->evaluateTimeOutStatus($now, $standardOut);
            $attendance->overtime_hours = $timeoutStatus['overtime_hours'];

            if ($timeoutStatus['is_early_out']) {
                $message = "TIME-OUT updated (EARLY OUT by {$timeoutStatus['early_out_minutes']} minutes)";
            } elseif ($timeoutStatus['overtime_hours'] > 0) {
                $message = "TIME-OUT updated (OVERTIME: {$attendance->overtime_hours} hours)";
            } else {
                $message = 'TIME-OUT updated';
            }

            $attendance->status = $attendance->status === 'late' ? 'late' : 'present';
            $action = 'TIME-OUT';
        }

        if ($attendance->am_in && $attendance->pm_out) {
            $attendance->status = $attendance->status === 'late' ? 'late' : 'present';
        }

        if (!$action) {
            return response()->json([
                'error' => 'Attendance already completed for today',
                'action' => 'completed',
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            ], 400);
        }
        
        $attendance->verification_method = 'fingerprint';
        
        $this->attachDeviceMetadata($attendance, $device);
        $attendance->gps_latitude = $request->input('gps_latitude');
        $attendance->gps_longitude = $request->input('gps_longitude');
        $attendance->gps_accuracy = $request->input('gps_accuracy');
        $attendance->gps_timestamp = $request->input('gps_timestamp');
        
        $attendance->save();

        \App\Models\DTR::generateForCurrentPeriod($employee->id, $attendance->attendance_date);
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'action' => $action,
            'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            'employee_number' => $employee->employee_number,
            'branch' => $employee->branch ? $employee->branch->branch_name : ($employee->branch_name ?? 'N/A'),
            'position' => $employee->position ?? 'N/A',
            'data' => [
                'date' => $attendance->attendance_date->format('m/d/Y'),
                'action_time' => $now->format('h:i A'),
                'time_in' => $attendance->am_in ? $attendance->am_in->format('h:i A') : '--',
                'time_out' => $attendance->pm_out ? $attendance->pm_out->format('h:i A') : ($attendance->am_out ? $attendance->am_out->format('h:i A') : '--'),
                'late_minutes' => $attendance->late_minutes ?? 0,
                'overtime_hours' => $attendance->overtime_hours ?? 0,
            ]
        ]);
    }

    public function checkAttendance(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string',
            'action' => 'required|in:TIME-IN,TIME-OUT',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['success' => false, 'message' => $deviceValidation['error']], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];
        if ($device->branch_id === null) {
            return response()->json(['success' => false, 'message' => 'This device is not assigned to a branch.'], 403);
        }

        $employee = EmployeeProfile::where('branch_id', $device->branch_id)
            ->where('employee_number', trim($request->employee_number))
            ->first();
        if (!$employee) {
            return response()->json(['exists' => false, 'message' => 'Employee not found.'], 404);
        }

        $attendance = AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        $exists = $request->action === 'TIME-IN'
            ? (bool) ($attendance && $attendance->am_in)
            : (bool) ($attendance && $attendance->pm_out);

        return response()->json([
            'success' => true,
            'exists' => $exists,
            'action' => $request->action,
            'employee_number' => $employee->employee_number,
        ]);
    }

    // Get today's DTR for the scanner display
    public function getTodayDTR(Request $request)
    {
        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['success' => false, 'message' => $deviceValidation['error']], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];
        if ($device->branch_id === null) {
            return response()->json(['success' => false, 'message' => 'This device is not assigned to a branch.'], 403);
        }
        
        $attendances = AttendanceLog::where('attendance_date', today())
            ->where('branch_id', $device->branch_id)
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $dtrData = $attendances->map(function($attendance) {
            return [
                'id' => $attendance->id,
                'date' => $attendance->attendance_date->format('m/d/Y'),
                'employee_name' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
                'employee_number' => $attendance->employee->employee_number,
                'time_in' => $attendance->am_in ? $attendance->am_in->format('h:i A') : '--',
                'time_out' => $attendance->pm_out ? $attendance->pm_out->format('h:i A') : ($attendance->am_out ? $attendance->am_out->format('h:i A') : '--'),
                'late_minutes' => $attendance->late_minutes ?? 0,
                'overtime_hours' => $attendance->overtime_hours ?? 0,
                'status' => $attendance->status ?? 'present',
            ];
        });
        
        return response()->json([
            'success' => true,
            'date' => now()->format('M. Y'),
            'records' => $dtrData,
            'count' => $dtrData->count()
        ]);
    }

    // Get recent employee attendance for the scanner list view
    public function getRecentEmployeesAttendance(Request $request)
    {
        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['success' => false, 'message' => $deviceValidation['error']], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];
        if ($device->branch_id === null) {
            return response()->json(['success' => false, 'message' => 'This device is not assigned to a branch.'], 403);
        }
        $limit = $request->query('limit', 15);
        
        $attendances = AttendanceLog::where('attendance_date', today())
            ->where('branch_id', $device->branch_id)
            ->with('employee')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
        
        $employeesList = $attendances->map(function($attendance) {
            $lastTime = $attendance->pm_out ?? $attendance->pm_in ?? $attendance->am_out ?? $attendance->am_in;
            return [
                'employee_number' => $attendance->employee->employee_number,
                'employee_name' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
                'position' => $attendance->employee->position,
                'last_action_time' => $lastTime ? $lastTime->format('h:i A') : '--',
                'status' => $attendance->status ?? 'present',
            ];
        });
        
        return response()->json([
            'success' => true,
            'employees' => $employeesList,
            'count' => $employeesList->count()
        ]);
    }

    // Temporary storage for fingerprint during enrollment (before employee is created)
    // Uses employee_number as key so browser and C# app can share data
    public function registerFingerprintTemp(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'employee_number' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|string',
            'branch' => 'nullable|string|max:255',
            'finger_name' => 'nullable|string|max:30',
            'device_serial' => 'nullable|string|max:100',
            'mac_address' => 'nullable|string|max:100',
            'device_id' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'device_serial' => 'required|string|max:100',
            'device_id' => 'nullable|string|max:100',
            'timestamp' => 'required|string',
            'nonce' => 'required|string|max:100',
            'signature' => 'required|string|size:64',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json([
                'success' => false,
                'message' => $deviceValidation['error'],
            ], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];

        if ($device->branch_id === null) {
            $this->clearFingerprintTempCache($request);
            return response()->json([
                'success' => false,
                'message' => 'This enrollment device is not assigned to a branch.',
            ], 403);
        }

        $fingerprintTemplate = $this->normalizeFingerprintTemplate($request->fingerprint_data);
        $fingerprintBytes = $this->fingerprintBytes($fingerprintTemplate);
        $decodedTemplate = base64_decode($fingerprintTemplate, true);
        if ($fingerprintBytes === ''
            || strlen($fingerprintTemplate) % 4 !== 0
            || !preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $fingerprintTemplate)
            || $decodedTemplate === false
            || $decodedTemplate === '') {
            return response()->json([
                'success' => false,
                'message' => 'The fingerprint template is empty or unreadable.',
            ], 422);
        }
        $employeeNumber = trim((string) ($request->employee_number ?? ''));
        $employeeNumber = $employeeNumber !== '' ? $employeeNumber : 'latest';

        $profile = null;
        $hadExistingFingerprint = false;
        if ($request->filled('employee_number')) {
            $profile = EmployeeProfile::where('employee_number', $employeeNumber)->first();
            if (!$profile) {
                $profile = FinanceProfile::where('employee_number', $employeeNumber)->first();
            }
            if (!$profile) {
                $profile = AdminProfile::where('employee_number', $employeeNumber)->first();
            }

            if (!$profile && $request->filled('first_name') && $request->filled('last_name')) {
                $findByName = function ($model) use ($request) {
                    return $model::whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($request->first_name))])
                        ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($request->last_name))])
                        ->first();
                };

                $profile = $findByName(EmployeeProfile::class)
                    ?? $findByName(FinanceProfile::class)
                    ?? $findByName(AdminProfile::class);
            }

            if ($profile) {
                $hadExistingFingerprint = !empty($profile->fingerprint_template)
                    || (bool) $profile->is_fingerprint_registered;
            }

        }

        $requestedBranchName = strtolower(trim((string) $request->input('branch')));
        $deviceBranchName = strtolower(trim((string) $device->branch?->branch_name));
        $profileBranchId = $profile?->branch_id;

        if ($profile && ($profileBranchId === null || (int) $profileBranchId !== (int) $device->branch_id)) {
            $this->clearFingerprintTempCache($request);
            return response()->json([
                'success' => false,
                'message' => 'This device belongs to ' . $device->branch?->branch_name . ' branch and this user cannot enroll here.',
            ], 403);
        }

        if (!$profile && ($requestedBranchName === '' || $deviceBranchName !== $requestedBranchName)) {
            $this->clearFingerprintTempCache($request);
            return response()->json([
                'success' => false,
                'message' => 'This enrollment device is restricted to ' . $device->branch?->branch_name . '.',
            ], 403);
        }

        $duplicate = $this->findFingerprintOwner($fingerprintTemplate, $profile);
        if ($duplicate) {
            return $this->duplicateFingerprintResponse($duplicate);
        }

        if ($profile) {
            $profile->fingerprint_template = $this->appendFingerprintTemplate(
                $profile->fingerprint_template,
                $fingerprintTemplate
            );
            $profile->is_fingerprint_registered = true;
            $profile->save();
        }

        $action = $hadExistingFingerprint ? 'updated' : 'registered';
        $fingerprintCount = $profile
            ? count($this->fingerprintTemplateList($profile->fingerprint_template))
            : 1;

        $payload = [
            'fingerprint_data' => $fingerprintTemplate,
            'employee_number' => $request->employee_number ?? 'latest',
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'finger_name' => $request->finger_name,
            'device_serial' => $request->device_serial,
            'mac_address' => $request->mac_address,
            'device_id' => $request->device_id,
            'location' => $request->location,
            'action' => $action,
            'fingerprint_count' => $fingerprintCount,
            'timestamp' => now(),
        ];

        cache()->put('fingerprint_temp_latest', $payload, 3600);

        if ($request->filled('employee_number')) {
            cache()->put("fingerprint_temp_{$employeeNumber}", $payload, 3600);
        }

        return response()->json([
            'success' => true,
            'message' => $request->filled('employee_number')
                ? ($profile
                    ? ($action === 'updated'
                        ? 'Fingerprint updated in database for employee #' . $employeeNumber
                        : 'Fingerprint registered in database for employee #' . $employeeNumber)
                    : 'Fingerprint data received for employee #' . $employeeNumber)
                : 'Fingerprint data received and stored as latest temporary record.',
            'database_saved' => (bool) $profile,
            'action' => $action,
            'finger_name' => $request->input('finger_name'),
            'fingerprint_count' => $fingerprintCount,
        ]);
    }

    private function clearFingerprintTempCache(Request $request): void
    {
        cache()->forget('fingerprint_temp_latest');

        $employeeNumber = trim((string) $request->input('employee_number'));
        if ($employeeNumber !== '') {
            cache()->forget("fingerprint_temp_{$employeeNumber}");
        }
    }

    public function getFingerprintTemp(Request $request)
    {
        $employeeNumber = $request->query('employee_number') ?? $request->input('employee_number');

        $fingerprintData = null;

        if ($employeeNumber) {
            $fingerprintData = cache()->get("fingerprint_temp_{$employeeNumber}");
        }

        if (!$fingerprintData) {
            $fingerprintData = cache()->get('fingerprint_temp_latest');
        }

        if (!$fingerprintData) {
            return response()->json([
                'success' => false,
                'pending' => true,
                'message' => 'No fingerprint data available yet.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $fingerprintData,
        ]);
    }

    // Check if employee's fingerprint is registered in database
    public function checkFingerprintStatus(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string',
        ]);

        $deviceValidation = $this->validateDeviceIdentity($request);
        if (isset($deviceValidation['error'])) {
            return response()->json(['success' => false, 'message' => $deviceValidation['error']], $deviceValidation['code']);
        }
        $device = $deviceValidation['device'];
        if ($device->branch_id === null) {
            return response()->json(['success' => false, 'message' => 'This device is not assigned to a branch.'], 403);
        }

        $employee = EmployeeProfile::where('branch_id', $device->branch_id)
            ->where('employee_number', $request->employee_number)
            ->first();
        if (!$employee) {
            $employee = FinanceProfile::where('branch_id', $device->branch_id)
                ->where('employee_number', $request->employee_number)
                ->first();
        }
        if (!$employee) {
            $employee = AdminProfile::where('branch_id', $device->branch_id)
                ->where('employee_number', $request->employee_number)
                ->first();
        }

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee number not found in the system.',
                'status' => 'not_found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee found in database',
            'employee_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: 'Unknown',
            'employee_number' => $employee->employee_number,
            'branch' => $employee->branch ? $employee->branch->branch_name : ($employee->branch_name ?? 'N/A'),
            'position' => $employee->position ?? 'N/A',
            'is_fingerprint_registered' => (bool) ($employee->is_fingerprint_registered ?? false),
            'status' => $employee->is_fingerprint_registered ? 'registered' : 'not_registered'
        ]);
    }
}
