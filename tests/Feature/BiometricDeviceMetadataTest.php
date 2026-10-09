<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BiometricController;
use App\Http\Controllers\Api\AttendanceController;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Device;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BiometricDeviceMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function signature(array $payload): string
    {
        return hash_hmac('sha256', implode('|', [
            $payload['employee_number'] ?? null,
            $payload['device_serial'] ?? null,
            $payload['wifi_mac'] ?? null,
            $payload['device_id'] ?? null,
            $payload['mac_address'] ?? null,
            $payload['timestamp'] ?? null,
            $payload['nonce'] ?? null,
        ]), 'mtcgs-2026-secure-device-hmac-7c7fba18a9b2');
    }

    private function signedDevicePayload(array $payload): array
    {
        $secret = config('app.biometric_device_secret') ?: env('BIOMETRIC_DEVICE_SECRET') ?: config('app.key');
        $payload['signature'] = hash_hmac('sha256', implode('|', [
            $payload['employee_id'] ?? null,
            $payload['employee_number'] ?? null,
            $payload['device_serial'] ?? null,
            $payload['wifi_mac'] ?? null,
            $payload['device_id'] ?? null,
            $payload['mac_address'] ?? null,
            $payload['timestamp'] ?? null,
            $payload['nonce'] ?? null,
        ]), $secret);

        return $payload;
    }

    public function test_device_templates_are_branch_scoped_and_cross_branch_clock_is_rejected(): void
    {
        $iriga = Branch::create([
            'branch_code' => 'BR-TEMPLATE-IRIGA',
            'branch_name' => 'Iriga Test Branch',
            'address' => 'Iriga Test Address',
        ]);
        $buhi = Branch::create([
            'branch_code' => 'BR-TEMPLATE-BUHI',
            'branch_name' => 'Buhi Test Branch',
            'address' => 'Buhi Test Address',
        ]);

        $irigaUser = User::create([
            'name' => 'Iriga Test Employee',
            'email' => 'iriga.template.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $iriga->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $irigaEmployee = EmployeeProfile::create([
            'user_id' => $irigaUser->id,
            'branch_id' => $iriga->id,
            'employee_number' => 'EMP-IRIGA-TEMPLATE',
            'first_name' => 'Iriga',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => now(),
            'fingerprint_template' => 'iriga-fingerprint-template',
            'is_fingerprint_registered' => true,
        ]);

        $buhiUser = User::create([
            'name' => 'Buhi Test Employee',
            'email' => 'buhi.template.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $buhi->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $buhiEmployee = EmployeeProfile::create([
            'user_id' => $buhiUser->id,
            'branch_id' => $buhi->id,
            'employee_number' => 'EMP-BUHI-TEMPLATE',
            'first_name' => 'Buhi',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => now(),
            'fingerprint_template' => 'buhi-fingerprint-template',
            'is_fingerprint_registered' => true,
        ]);

        $device = Device::create([
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'serial_number' => 'SERIAL-IRIGA-TEMPLATE-001',
            'device_name' => 'Iriga Test Scanner',
            'device_type' => 'computer',
            'branch_id' => $iriga->id,
            'status' => 'active',
        ]);

        $timestamp = now()->toIso8601String();
        $deviceIdentity = $this->signedDevicePayload([
            'employee_id' => null,
            'employee_number' => null,
            'device_serial' => $device->serial_number,
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'device_id' => (string) $device->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => $timestamp,
            'nonce' => 'iriga-template-list-nonce',
        ]);
        $templatesResponse = (new BiometricController())->getFingerprintTemplates(
            Request::create('/api/biometric/templates', 'GET', $deviceIdentity)
        );

        $this->assertSame(200, $templatesResponse->getStatusCode(), $templatesResponse->content());
        $templateNumbers = collect(json_decode($templatesResponse->content(), true)['templates'])
            ->pluck('employee_number')
            ->unique()
            ->values()
            ->all();
        $this->assertSame([$irigaEmployee->employee_number], $templateNumbers);

        $attendancePayload = $this->signedDevicePayload([
            'employee_id' => null,
            'employee_number' => $buhiEmployee->employee_number,
            'fingerprint_data' => $buhiEmployee->fingerprint_template,
            'action' => 'TIME-IN',
            'device_serial' => $device->serial_number,
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'device_id' => (string) $device->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => $timestamp,
            'attendance_timestamp' => $timestamp,
            'nonce' => 'cross-branch-time-in-nonce',
        ]);
        $attendanceResponse = (new BiometricController())->processTimeClock(
            Request::create('/api/biometric/time-clock', 'POST', $attendancePayload)
        );

        $this->assertSame(403, $attendanceResponse->getStatusCode(), $attendanceResponse->content());
        $this->assertDatabaseMissing('attendance_logs', [
            'employee_profile_id' => $buhiEmployee->id,
        ]);
    }

    public function test_laptop_mac_is_ignored_for_device_authorization_and_location_is_saved_from_device(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-LAPTOP-001',
            'branch_name' => 'Laptop Branch',
            'address' => 'Laptop street',
        ]);

        $user = User::create([
            'name' => 'Laptop Test Employee',
            'email' => 'laptop.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-LAPTOP-001',
            'first_name' => 'Laptop',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => 'sample-fingerprint-template',
            'is_fingerprint_registered' => true,
        ]);

        Device::create([
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'wifi_mac_address' => null,
            'laptop_mac_address' => 'AA-BB-CC-DD-EE-11',
            'serial_number' => 'SERIAL-LAPTOP-001',
            'device_name' => 'Laptop Terminal',
            'device_type' => 'computer',
            'branch_id' => $branch->id,
            'location' => 'Ground Floor',
            'status' => 'active',
        ]);

        $invalidPayload = [
            'fingerprint_data' => 'sample-fingerprint-template',
            'employee_number' => 'EMP-LAPTOP-001',
            'laptop_mac' => 'AA-BB-CC-DD-EE-11',
            'mac_address' => 'AA:BB:CC:DD:EE:11',
            'timestamp' => now()->format('m/d/Y h:i:s A'),
            'nonce' => 'invalid-device-test',
        ];
        $invalidPayload['signature'] = $this->signature($invalidPayload);
        $request = Request::create('/api/biometric/attendance', 'POST', $invalidPayload);

        $response = (new BiometricController())->processAttendance($request);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Device not authorized. MAC address not registered.', json_decode($response->content(), true)['error']);

        $validPayload = [
            'fingerprint_data' => 'sample-fingerprint-template',
            'employee_number' => 'EMP-LAPTOP-001',
            'device_serial' => 'SERIAL-LAPTOP-001',
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'device_id' => '1',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => now()->setTime(8, 15)->format('m/d/Y h:i:s A'),
            'nonce' => 'valid-device-test',
        ];
        $validPayload['signature'] = $this->signature($validPayload);
        $validRequest = Request::create('/api/biometric/attendance', 'POST', $validPayload);

        $validResponse = (new BiometricController())->processAttendance($validRequest);

        $this->assertSame(200, $validResponse->getStatusCode());
        $this->assertDatabaseHas('attendance_logs', [
            'employee_profile_id' => $employeeProfile->id,
            'late_minutes' => 15,
        ]);
        $this->assertDatabaseHas('attendance_logs', [
            'employee_profile_id' => $employeeProfile->id,
            'location' => 'Ground Floor',
            'device_id' => 1,
        ]);
    }

    public function test_scanner_attendance_is_rejected_during_an_approved_suspension(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-28 08:00:00');

        $branch = Branch::create([
            'branch_code' => 'BR-SUSPENSION-BLOCK',
            'branch_name' => 'Suspension Block Branch',
            'address' => 'Suspension Test Address',
        ]);

        $user = User::create([
            'name' => 'Suspension Test Employee',
            'email' => 'suspension.block@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employee = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-SUSPENSION-BLOCK',
            'first_name' => 'Suspension',
            'last_name' => 'Test Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => 'suspension-test-template',
            'is_fingerprint_registered' => true,
        ]);

        $device = Device::create([
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'serial_number' => 'SERIAL-SUSPENSION-001',
            'device_name' => 'Suspension Test Terminal',
            'device_type' => 'computer',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        CalendarEvent::create([
            'title' => 'Approved Suspension',
            'event_date' => '2026-09-28',
            'event_type' => 'suspension',
            'created_by' => $user->id,
            'branch_id' => null,
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $suspensionsResponse = (new BiometricController())->getAttendanceSuspensions(Request::create(
            '/api/biometric/suspensions',
            'GET',
            [
                'device_serial' => $device->serial_number,
                'start_date' => '2026-09-28',
                'end_date' => '2026-09-28',
            ]
        ));

        $this->assertSame(200, $suspensionsResponse->getStatusCode());
        $suspensionDates = json_decode($suspensionsResponse->content(), true)['dates'];
        $this->assertContains('2026-09-28', $suspensionDates, json_encode($suspensionDates));

        $payload = [
            'employee_id' => null,
            'employee_number' => $employee->employee_number,
            'fingerprint_data' => 'suspension-test-template',
            'action' => 'TIME-IN',
            'device_serial' => $device->serial_number,
            'device_id' => (string) $device->id,
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => now()->toIso8601String(),
            'attendance_timestamp' => now()->toIso8601String(),
            'nonce' => 'suspension-test-nonce',
        ];
        $secret = config('app.biometric_device_secret')
            ?: env('BIOMETRIC_DEVICE_SECRET')
            ?: config('app.key');
        $payload['signature'] = hash_hmac('sha256', implode('|', [
            $payload['employee_id'],
            $payload['employee_number'],
            $payload['device_serial'],
            $payload['wifi_mac'],
            $payload['device_id'],
            $payload['mac_address'],
            $payload['timestamp'],
            $payload['nonce'],
        ]), $secret);

        $response = (new BiometricController())->processTimeClock(
            Request::create('/api/biometric/time-clock', 'POST', $payload)
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertTrue(json_decode($response->content(), true)['attendance_blocked']);
        $this->assertDatabaseMissing('attendance_logs', [
            'employee_profile_id' => $employee->id,
            'attendance_date' => '2026-09-28',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employee->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => '2026-09-28',
            'am_in' => '2026-09-28 08:00:00',
            'status' => 'present',
        ]);
        $this->assertNotNull(AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereDate('attendance_date', '2026-09-28')
            ->first()?->am_in);
        \Illuminate\Support\Carbon::setTestNow('2026-09-28 09:15:00');
        $payload['action'] = 'TIME-OUT';
        $payload['timestamp'] = '2026-09-28T09:15:00+08:00';
        $payload['attendance_timestamp'] = $payload['timestamp'];
        $payload['nonce'] = 'suspension-timeout-nonce';
        $payload['signature'] = hash_hmac('sha256', implode('|', [
            $payload['employee_id'],
            $payload['employee_number'],
            $payload['device_serial'],
            $payload['wifi_mac'],
            $payload['device_id'],
            $payload['mac_address'],
            $payload['timestamp'],
            $payload['nonce'],
        ]), $secret);

        $timeoutResponse = (new BiometricController())->processTimeClock(
            Request::create('/api/biometric/time-clock', 'POST', $payload)
        );

        $this->assertSame(200, $timeoutResponse->getStatusCode(), $timeoutResponse->content());
        $attendanceAfterTimeout = AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereDate('attendance_date', '2026-09-28')
            ->first();
        $this->assertNotNull($attendanceAfterTimeout);
        $this->assertSame('09:15:00', $attendanceAfterTimeout->pm_out->format('H:i:s'));

        $clockOutResponse = (new AttendanceController())->clockAttendance(Request::create(
            '/api/attendance/clock',
            'POST',
            [
                'branch_id' => $branch->id,
                'employee_number' => $employee->employee_number,
                'attendance_type' => 'am_out',
                'fingerprint_data' => 'suspension-test-template',
            ]
        ));

        $this->assertSame(200, $clockOutResponse->getStatusCode(), $clockOutResponse->getContent());

        $legacyResponse = (new BiometricController())->processAttendance(Request::create(
            '/api/biometric/process',
            'POST',
            [
                'fingerprint_data' => 'suspension-test-template',
                'timestamp' => now()->format('m/d/Y h:i:s A'),
            ]
        ));

        $this->assertSame(403, $legacyResponse->getStatusCode());
        $this->assertTrue(json_decode($legacyResponse->content(), true)['attendance_blocked']);

        $clockResponse = (new AttendanceController())->clockAttendance(Request::create(
            '/api/attendance/clock',
            'POST',
            [
                'branch_id' => $branch->id,
                'employee_number' => $employee->employee_number,
                'attendance_type' => 'am_in',
                'fingerprint_data' => 'suspension-test-template',
            ]
        ));

        $this->assertSame(403, $clockResponse->getStatusCode());
        $this->assertTrue(json_decode($clockResponse->content(), true)['attendance_blocked']);
        $this->assertSame(1, AttendanceLog::where('employee_profile_id', $employee->id)
            ->whereDate('attendance_date', '2026-09-28')
            ->count());

        \Illuminate\Support\Carbon::setTestNow();
    }
}
