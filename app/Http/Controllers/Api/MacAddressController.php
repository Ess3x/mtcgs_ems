<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MacAddressController extends Controller
{
    /**
     * Validate MAC address from C# application
     * 
     * Request: POST /api/validate-mac-address
     * Body: {
     *   "mac_address": "00:1A:2B:3C:4D:5E"
     * }
     */
    public function validateMacAddress(Request $request)
    {
        $request->validate([
            'mac_address' => 'required|string',
        ]);

        $macAddress = Device::normalizeMacAddress($request->mac_address);

        $device = Device::getByMacAddress($macAddress);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'MAC address not registered or inactive',
                'mac_address' => $macAddress,
            ], 401);
        }

        // Update last used timestamp
        $device->updateLastUsed();

        return response()->json([
            'success' => true,
            'message' => 'MAC address validated successfully',
            'device' => [
                'id' => $device->id,
                'mac_address' => $device->mac_address,
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'branch_id' => $device->branch_id,
                'location' => $device->location,
                'status' => $device->status,
            ],
        ]);
    }

    /**
     * Get device info by MAC address
     */
    public function getDeviceInfo(Request $request)
    {
        $request->validate([
            'mac_address' => 'required|string',
        ]);

        $macAddress = Device::normalizeMacAddress($request->mac_address);
        $device = Device::getByMacAddress($macAddress);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'mac_address' => $device->mac_address,
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'location' => $device->location,
                'status' => $device->status,
                'last_used_at' => $device->last_used_at,
            ],
        ]);
    }

    /**
     * Register a new device (Super Admin only)
     */
    public function registerDevice(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Only super administrators can register devices',
            ], 403);
        }

        $request->validate([
            'mac_address' => 'required|string|unique:devices',
            'device_name' => 'required|string',
            'device_type' => 'required|in:biometric_scanner,kiosk,computer',
            'branch_id' => 'nullable|exists:branches,id',
            'location' => 'nullable|string',
        ]);

        $macAddress = Device::normalizeMacAddress($request->mac_address);

        $device = Device::create([
            'mac_address' => $macAddress,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'branch_id' => $request->branch_id,
            'location' => $request->location,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'device' => $device,
        ], 201);
    }

    /**
     * Get all active devices (Super Admin only)
     */
    public function listDevices(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Only super administrators can list devices',
            ], 403);
        }

        $devices = Device::where('status', 'active')
                        ->orderBy('device_name')
                        ->get();

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    /**
     * Update device status (Super Admin only)
     */
    public function updateDeviceStatus(Request $request, Device $device)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Only super administrators can update device status',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        $device->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Device status updated',
            'device' => $device,
        ]);
    }
}
