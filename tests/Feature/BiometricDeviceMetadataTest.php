<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BiometricController;
use App\Models\Branch;
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
            'location' => 'Ground Floor',
            'device_id' => 1,
        ]);
    }
}
