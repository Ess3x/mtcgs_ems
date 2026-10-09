<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BiometricDeviceAuthenticator
{
    private const SIGNED_FIELDS = [
        'employee_id',
        'employee_number',
        'device_serial',
        'wifi_mac',
        'device_id',
        'mac_address',
        'timestamp',
        'nonce',
        'action',
        'fingerprint_data',
        'finger_name',
        'branch',
        'attendance_timestamp',
        'fingerprint_scope',
    ];

    public function authenticate(Request $request, bool $requireActive = true): array
    {
        $serial = Device::normalizeSerialNumber($request->input('device_serial'));
        $macAddress = Device::normalizeMacAddress($request->input('mac_address'));
        $deviceId = trim((string) $request->input('device_id'));
        $nonce = trim((string) $request->input('nonce'));
        $timestamp = trim((string) $request->input('timestamp'));

        if ($serial === '' || strlen($macAddress) !== 12 || $nonce === '' || $timestamp === '') {
            return $this->reject('A registered device serial, MAC address, timestamp, and nonce are required.');
        }

        $device = Device::query()
            ->whereRaw('UPPER(TRIM(serial_number)) = ?', [$serial])
            ->first();

        if (!$device || ($requireActive && $device->status !== 'active')) {
            return $this->reject('Device serial is not registered or active.');
        }

        $registeredMac = Device::normalizeMacAddress($device->mac_address);
        if ($registeredMac === '' || !hash_equals($registeredMac, $macAddress)) {
            return $this->reject('The device serial and MAC address do not match the registered device.');
        }

        $wifiMac = Device::normalizeMacAddress($request->input('wifi_mac'));
        if ($wifiMac !== '' && !hash_equals($registeredMac, $wifiMac)) {
            return $this->reject('The Wi-Fi MAC address does not match the registered device MAC address.');
        }

        $registeredDeviceId = trim((string) $device->laptop_mac_address);
        if ($registeredDeviceId !== ''
            && ($deviceId === '' || !hash_equals(strtoupper($registeredDeviceId), strtoupper($deviceId)))) {
            return $this->reject('Device ID does not match the registered device.');
        }

        $secret = (string) $device->api_secret;
        if ($secret === '') {
            return $this->reject('This device has no provisioned credential. Ask a super administrator to provision it.');
        }

        try {
            $requestTimestamp = \Carbon\Carbon::parse($timestamp);
        } catch (\Throwable $exception) {
            return $this->reject('Invalid device request timestamp.');
        }

        if (abs($requestTimestamp->diffInSeconds(now(), false)) > 300) {
            return $this->reject('Device request timestamp is expired or too far in the future.');
        }

        $signature = strtolower(trim((string) $request->input('signature')));
        $expectedSignature = hash_hmac('sha256', $this->signingPayload($request), $secret);
        if ($signature === '' || !hash_equals($expectedSignature, $signature)) {
            return $this->reject('Invalid device credential signature.');
        }

        $nonceKey = 'biometric-device-nonce:' . $device->getKey() . ':' . hash('sha256', $nonce);
        if (!Cache::add($nonceKey, true, now()->addMinutes(5))) {
            return $this->reject('This device request has already been used.');
        }

        return ['device' => $device];
    }

    public function signingPayload(Request $request): string
    {
        $values = [
            strtoupper($request->method()),
            $request->path(),
        ];

        foreach (self::SIGNED_FIELDS as $field) {
            $value = $request->input($field);
            if (in_array($field, ['finger_name', 'fingerprint_scope'], true) && !filled($value)) {
                continue;
            }
            $values[] = is_scalar($value) ? (string) $value : '';
        }

        return implode('|', $values);
    }

    private function reject(string $message): array
    {
        Log::warning('Biometric device request rejected.', ['reason' => $message]);

        return [
            'error' => $message,
            'code' => 401,
        ];
    }
}
