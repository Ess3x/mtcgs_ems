<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\AdminProfile;
use App\Models\AttendanceLog;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiometricController extends Controller
{
    // Register employee fingerprint (Admin or Finance Officer or Super Admin)
    public function registerFingerprint(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employee_profiles,id',
            'fingerprint_data' => 'required|string',
        ]);

        $user = auth()->user();
        
        // Allow Super Admin, Admin, or Finance staff to register fingerprints
        if ($user->role !== 'admin' && !in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            return response()->json(['error' => 'Only Admin, Super Admin, or Finance staff can register fingerprints'], 403);
        }

        $employee = EmployeeProfile::findOrFail($request->employee_id);
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
    
    // Process attendance for admin (same as employee)
    public function processAdminAttendance(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'mac_address' => 'nullable|string',
        ]);
        
        // Validate MAC address if provided
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Device not authorized. MAC address not registered.',
                    'mac_address' => $macAddress,
                ], 401);
            }
        }
        
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
            $standardIn = $this->scheduledTime($employeeProfile, 'start_time', 7);
            if ($now >= $standardIn) {
                $lateMinutes = $standardIn->diffInMinutes($now);
                $attendance->late_minutes = $lateMinutes;
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
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            if ($device) {
                $attendance->device_mac_address = $device->mac_address;
                $attendance->device_id = $device->id;
                $device->updateLastUsed();
            }
        }
        
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
            ]
        ]);
    }
    
    // Process attendance for finance officer
    public function processFinanceAttendance(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'mac_address' => 'nullable|string',
        ]);
        
        // Validate MAC address if provided
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Device not authorized. MAC address not registered.',
                    'mac_address' => $macAddress,
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
            $standardIn = $this->scheduledTime($employeeProfile, 'start_time', 7);
            if ($now >= $standardIn) {
                $lateMinutes = $standardIn->diffInMinutes($now);
                $attendance->late_minutes = $lateMinutes;
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
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            if ($device) {
                $attendance->device_mac_address = $device->mac_address;
                $attendance->device_id = $device->id;
                $device->updateLastUsed();
            }
        }
        
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
            ]
        ]);
    }
    
    // Verify a scanned fingerprint template and return the matched employee information.
    public function verifyFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
        ]);

        $scannedTemplate = $this->fingerprintBytes($request->fingerprint_data);

        $profile = EmployeeProfile::where(function ($query) {
                $query->where('is_fingerprint_registered', true)
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('fingerprint_template')
                            ->where('fingerprint_template', '!=', '')
                            ->whereRaw('LOWER(TRIM(fingerprint_template)) != "null"');
                    });
            })->get()->first(fn ($item) => $this->templatesMatch($item->fingerprint_template, $scannedTemplate));

        if (!$profile) {
            $profile = FinanceProfile::where(function ($query) {
                    $query->where('is_fingerprint_registered', true)
                        ->orWhere(function ($subQuery) {
                            $subQuery->whereNotNull('fingerprint_template')
                                ->where('fingerprint_template', '!=', '')
                                ->whereRaw('LOWER(TRIM(fingerprint_template)) != "null"');
                        });
                })->get()->first(fn ($item) => $this->templatesMatch($item->fingerprint_template, $scannedTemplate));
        }

        if (!$profile) {
            $profile = AdminProfile::where(function ($query) {
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

    private function scheduledTime($profile, string $field, int $fallbackHour): \Carbon\Carbon
    {
        return $profile->shift
            ? \Carbon\Carbon::today()->setTimeFromTimeString($profile->shift->{$field})
            : \Carbon\Carbon::today()->setTime($fallbackHour, 0, 0);
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

    private function attendanceBlockedReason($employee, $date): ?string
    {
        $day = $date instanceof \Carbon\Carbon
            ? $date->copy()
            : \Carbon\Carbon::parse($date);

        if ($day->isWeekend()) {
            return 'Attendance is not allowed on weekends.';
        }

        $workingDayService = app(\App\Services\WorkingDayService::class);
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
        ]);
        
        // Validate MAC address if provided
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Device not authorized. MAC address not registered.',
                    'mac_address' => $macAddress,
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
            $attendance->am_in = $now;
            $standardIn = $this->scheduledTime($employee, 'start_time', 7);
            if ($now >= $standardIn) {
                $lateMinutes = $standardIn->diffInMinutes($now);
                $attendance->late_minutes = $lateMinutes;
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
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            if ($device) {
                $attendance->device_mac_address = $device->mac_address;
                $attendance->device_id = $device->id;
                $device->updateLastUsed();
            }
        }
        
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
            ]
        ]);
    }
    
    // Get employees without fingerprint (for Admin/Super Admin/Finance Officer)
    public function getUnregisteredEmployees(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin' && $user->role !== 'finance_officer') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $branchId = null;
        if ($user->role === 'finance_officer') {
            $profile = $user->getFinanceProfile();
            $branchId = $profile->branch_id ?? null;
        }
        
        $query = EmployeeProfile::where('is_fingerprint_registered', false);

        if ($user->role === 'finance_officer') {
            $query->where('branch_id', $branchId);
        } elseif ($user->role === 'admin' && $user->admin_type === 'branch_admin') {
            $query->where('branch_id', $user->profile->branch_id);
        }

        $employees = $query->get()->map(function($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $employees,
            'count' => $employees->count()
        ]);
    }
    
    // Get unregistered finance officers (for Super Admin)
    public function getUnregisteredFinanceOfficers(Request $request)
    {
        $user = auth()->user();
        
        // Only Super Admin can view unregistered finance officers
        if (!$user->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can access this'], 403);
        }
        
        $financeOfficers = FinanceProfile::where('is_fingerprint_registered', false)
            ->with('user')
            ->get()
            ->map(function($finance) {
                return [
                    'id' => $finance->id,
                    'name' => $finance->first_name . ' ' . $finance->last_name,
                    'email' => $finance->user->email ?? 'N/A',
                    'employee_number' => $finance->employee_number,
                    'branch_id' => $finance->branch_id,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $financeOfficers,
            'count' => $financeOfficers->count()
        ]);
    }
    
    // Get unregistered admins (for Super Admin)
    public function getUnregisteredAdmins(Request $request)
    {
        $user = auth()->user();
        
        // Only Super Admin can view unregistered admins
        if (!$user->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can access this'], 403);
        }
        
        $admins = AdminProfile::where('is_fingerprint_registered', false)
            ->with('user')
            ->get()
            ->map(function($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->first_name . ' ' . $admin->last_name,
                    'email' => $admin->user->email ?? 'N/A',
                    'employee_number' => $admin->employee_number,
                    'admin_level' => $admin->admin_level,
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
        $templates = EmployeeProfile::where('is_fingerprint_registered', true)
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

    public function processTimeClock(Request $request)
    {
        $request->validate([
            'fingerprint_data' => 'required|string',
            'action' => 'required|in:TIME-IN,TIME-OUT',
            'mac_address' => 'nullable|string',
        ]);
        
        // Validate MAC address if provided
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Device not authorized. MAC address not registered.',
                    'mac_address' => $macAddress,
                ], 401);
            }
        }
        
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

        $attendanceDate = today();
        $nonWorkingDayResponse = $this->attendanceBlockedReason($employee, $attendanceDate);
        if ($nonWorkingDayResponse) {
            return response()->json([
                'success' => false,
                'error' => $nonWorkingDayResponse,
                'message' => $nonWorkingDayResponse,
                'attendance_blocked' => true,
                'date' => $attendanceDate->toDateString(),
            ], 403);
        }
        
        $attendance = AttendanceLog::firstOrNew([
            'employee_profile_id' => $employee->id,
            'attendance_date' => $attendanceDate,
        ]);
        
        $attendance->employee_profile_id = $employee->id;
        $attendance->employee_id = $employee->id;
        $attendance->branch_id = $employee->branch_id;
        $attendance->attendance_date = $attendanceDate;
        
        $now = now();
        $requestedAction = strtoupper(trim($request->input('action')));

        if ($requestedAction === 'TIME-IN') {
            if ($attendance->am_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'TIME-IN already recorded for today.',
                    'action' => 'TIME-IN',
                ], 409);
            }

            $attendance->am_in = $now;
            $standardIn = $this->scheduledTime($employee, 'start_time', 7);
            if ($now >= $standardIn) {
                $lateMinutes = $standardIn->diffInMinutes($now);
                $attendance->late_minutes = $lateMinutes;
                $attendance->status = 'late';
                $message = "TIME-IN recorded (LATE by {$lateMinutes} minutes)";
            } else {
                $attendance->status = 'present';
                $message = "TIME-IN recorded (On time)";
            }
            $action = 'TIME-IN';
        } else {
            if ($attendance->pm_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'TIME-OUT already recorded for today.',
                    'action' => 'TIME-OUT',
                ], 409);
            }

            $attendance->pm_out = $now;
            $standardOut = $this->scheduledTime($employee, 'end_time', 17);
            $timeoutStatus = $this->evaluateTimeOutStatus($now, $standardOut);
            $attendance->overtime_hours = $timeoutStatus['overtime_hours'];

            if ($timeoutStatus['is_early_out']) {
                $message = "TIME-OUT recorded (EARLY OUT by {$timeoutStatus['early_out_minutes']} minutes)";
            } elseif ($timeoutStatus['overtime_hours'] > 0) {
                $message = "TIME-OUT recorded (OVERTIME: {$attendance->overtime_hours} hours)";
            } else {
                $message = 'TIME-OUT recorded';
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
        
        if ($request->filled('mac_address')) {
            $macAddress = Device::normalizeMacAddress($request->mac_address);
            $device = Device::getByMacAddress($macAddress);
            if ($device) {
                $attendance->device_mac_address = $device->mac_address;
                $attendance->device_id = $device->id;
                $device->updateLastUsed();
            }
        }
        
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

        $employee = EmployeeProfile::where('employee_number', trim($request->employee_number))->first();
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
        $branchId = $request->query('branch_id');
        
        $attendances = AttendanceLog::where('attendance_date', today())
            ->when($branchId, function($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
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
        $branchId = $request->query('branch_id');
        $limit = $request->query('limit', 15);
        
        $attendances = AttendanceLog::where('attendance_date', today())
            ->when($branchId, function($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
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
            'finger_name' => 'nullable|string|max:30',
        ]);

        $fingerprintTemplate = $this->normalizeFingerprintTemplate($request->fingerprint_data);
        $employeeNumber = trim((string) ($request->employee_number ?? ''));
        $employeeNumber = $employeeNumber !== '' ? $employeeNumber : 'latest';

        $profile = null;
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
                $profile->fingerprint_template = $this->appendFingerprintTemplate(
                    $profile->fingerprint_template,
                    $fingerprintTemplate
                );
                $profile->is_fingerprint_registered = true;
                $profile->save();
            }
        }

        $payload = [
            'fingerprint_data' => $fingerprintTemplate,
            'employee_number' => $request->employee_number ?? 'latest',
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'finger_name' => $request->finger_name,
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
                    ? 'Fingerprint registered in database for employee #' . $employeeNumber
                    : 'Fingerprint data received for employee #' . $employeeNumber)
                : 'Fingerprint data received and stored as latest temporary record.',
            'database_saved' => (bool) $profile,
        ]);
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

