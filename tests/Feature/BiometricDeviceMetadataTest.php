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

    private const DEVICE_SECRET = 'test-biometric-device-secret-for-signed-requests';

    private function legacySignature(array $payload): string
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

    private function signedDevicePayload(array $payload, string $method, string $path): array
    {
        $values = [
            strtoupper($method),
            ltrim($path, '/'),
            $payload['employee_id'] ?? null,
            $payload['employee_number'] ?? null,
            $payload['device_serial'] ?? null,
            $payload['wifi_mac'] ?? null,
            $payload['device_id'] ?? null,
            $payload['mac_address'] ?? null,
            $payload['timestamp'] ?? null,
            $payload['nonce'] ?? null,
            $payload['action'] ?? null,
            $payload['fingerprint_data'] ?? null,
        ];
        if (isset($payload['finger_name']) && trim((string) $payload['finger_name']) !== '') {
            $values[] = $payload['finger_name'];
        }
        $values[] = $payload['branch'] ?? null;
        $values[] = $payload['attendance_timestamp'] ?? null;
        $payload['signature'] = hash_hmac(
            'sha256',
            implode('|', array_map(static fn ($value) => is_scalar($value) ? (string) $value : '', $values)),
            self::DEVICE_SECRET
        );

        return $payload;
    }

    public function test_authenticated_buhi_device_uses_its_registered_branch_for_new_enrollment(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-ENROLL-BUHI',
            'branch_name' => 'Buhi',
            'address' => 'Buhi Test Address',
        ]);
        $device = Device::create([
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'serial_number' => 'SERIAL-ENROLL-BUHI-001',
            'api_secret' => self::DEVICE_SECRET,
            'device_name' => 'Buhi Enrollment Scanner',
            'device_type' => 'computer',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $payload = $this->signedDevicePayload([
            'employee_number' => 'NEW-BUHI-EMPLOYEE',
            'fingerprint_data' => base64_encode(str_repeat('valid-template-data', 10)),
            'first_name' => 'New',
            'last_name' => 'Employee',
            'branch' => 'Buhi Branch',
            'device_serial' => $device->serial_number,
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'device_id' => (string) $device->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => now()->toIso8601String(),
            'nonce' => 'new-buhi-enrollment-nonce',
        ], 'POST', '/api/fingerprint-register-temp');

        $response = (new BiometricController())->registerFingerprintTemp(
            Request::create('/api/fingerprint-register-temp', 'POST', $payload)
        );

        $this->assertSame(200, $response->getStatusCode(), $response->content());
        $this->assertFalse(json_decode($response->content(), true)['database_saved']);
        $this->assertSame(
            'Buhi',
            cache()->get('fingerprint_temp_NEW-BUHI-EMPLOYEE')['branch']
        );
    }

    public function test_enrolling_again_updates_the_existing_named_finger_slot(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FINGER-SLOTS',
            'branch_name' => 'Finger Slot Test Branch',
            'address' => 'Finger Slot Test Address',
        ]);
        $user = User::create([
            'name' => 'Finger Slot Employee',
            'email' => 'finger.slot.employee@example.com',
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
            'employee_number' => 'EMP-FINGER-SLOTS',
            'first_name' => 'Finger',
            'last_name' => 'Slot',
            'position' => 'Staff',
            'date_hired' => now(),
            'is_fingerprint_registered' => false,
        ]);
        $device = Device::create([
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'serial_number' => 'SERIAL-FINGER-SLOTS-001',
            'api_secret' => self::DEVICE_SECRET,
            'device_name' => 'Finger Slot Test Scanner',
            'device_type' => 'computer',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $registrations = [
            ['finger_name' => 'Left Thumb', 'fingerprint_data' => base64_encode(str_repeat('left-thumb-first', 10))],
            ['finger_name' => 'Right Thumb', 'fingerprint_data' => base64_encode(str_repeat('right-thumb', 10))],
            ['finger_name' => 'Left Thumb', 'fingerprint_data' => base64_encode(str_repeat('left-thumb-updated', 10))],
        ];
        $tamperedNamePayload = $this->signedDevicePayload([
            'employee_number' => $employee->employee_number,
            'fingerprint_data' => base64_encode(str_repeat('tampered-finger-name', 10)),
            'finger_name' => 'Left Thumb',
            'device_serial' => $device->serial_number,
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'device_id' => (string) $device->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => now()->toIso8601String(),
            'nonce' => 'finger-slot-tampered-name',
        ], 'POST', '/api/fingerprint-register-temp');
        $tamperedNamePayload['finger_name'] = 'Right Thumb';
        $tamperedNameResponse = (new BiometricController())->registerFingerprintTemp(
            Request::create('/api/fingerprint-register-temp', 'POST', $tamperedNamePayload)
        );
        $this->assertSame(401, $tamperedNameResponse->getStatusCode(), $tamperedNameResponse->content());

        foreach ($registrations as $index => $registration) {
            $payload = $this->signedDevicePayload([
                'employee_number' => $employee->employee_number,
                'fingerprint_data' => $registration['fingerprint_data'],
                'finger_name' => $registration['finger_name'],
                'device_serial' => $device->serial_number,
                'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
                'device_id' => (string) $device->id,
                'mac_address' => 'AA:BB:CC:DD:EE:FF',
                'timestamp' => now()->toIso8601String(),
                'nonce' => 'finger-slot-enrollment-' . $index,
            ], 'POST', '/api/fingerprint-register-temp');

            $response = (new BiometricController())->registerFingerprintTemp(
                Request::create('/api/fingerprint-register-temp', 'POST', $payload)
            );

            $this->assertSame(200, $response->getStatusCode(), $response->content());
        }

        $savedTemplates = json_decode($employee->fresh()->fingerprint_template, true);
        $this->assertSame(2, count($savedTemplates['templates']));
        $this->assertEqualsCanonicalizing(
            ['Left Thumb', 'Right Thumb'],
            array_column($savedTemplates['templates'], 'finger_name')
        );
        $this->assertSame(
            2,
            cache()->get('fingerprint_temp_' . $employee->employee_number)['fingerprint_count']
        );
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
            'api_secret' => self::DEVICE_SECRET,
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
        ], 'GET', '/api/biometric/templates');
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

        $replayResponse = (new BiometricController())->getFingerprintTemplates(
            Request::create('/api/biometric/templates', 'GET', $deviceIdentity)
        );
        $this->assertSame(401, $replayResponse->getStatusCode(), $replayResponse->content());

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
        ], 'POST', '/api/biometric/time-clock');
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
        $invalidPayload['signature'] = $this->legacySignature($invalidPayload);
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
        $validPayload['signature'] = $this->legacySignature($validPayload);
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
            'api_secret' => self::DEVICE_SECRET,
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

        $suspensionQuery = $this->signedDevicePayload([
            'device_serial' => $device->serial_number,
            'wifi_mac' => 'AA:BB:CC:DD:EE:FF',
            'device_id' => (string) $device->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'timestamp' => now()->toIso8601String(),
            'nonce' => 'suspension-query-nonce',
            'start_date' => '2026-09-28',
            'end_date' => '2026-09-28',
        ], 'GET', '/api/biometric/suspensions');
        $suspensionsResponse = (new BiometricController())->getAttendanceSuspensions(
            Request::create('/api/biometric/suspensions', 'GET', $suspensionQuery)
        );

        $this->assertSame(200, $suspensionsResponse->getStatusCode());
        $suspensionDates = json_decode($suspensionsResponse->content(), true)['dates'];
        $this->assertContains('2026-09-28', $suspensionDates, json_encode($suspensionDates));

        $payload = $this->signedDevicePayload([
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
        ], 'POST', '/api/biometric/time-clock');

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
        $payload = $this->signedDevicePayload($payload, 'POST', '/api/biometric/time-clock');

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
