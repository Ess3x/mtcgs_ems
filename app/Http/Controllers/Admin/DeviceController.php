<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    private function authorizeDeviceManagement(): void
    {
        $user = Auth::user();
        
        // Only super admins can manage biometric devices
        if (!$user || $user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            abort(403, 'Only super administrators can manage biometric devices.');
        }
    }

    public function index()
    {
        $this->authorizeDeviceManagement();

        $devices = Device::with('branch')->orderBy('device_name')->get();
        $branches = Branch::orderBy('branch_name')->get();

        return view('admin.devices.index', compact('devices', 'branches'));
    }

    public function statusData()
    {
        $this->authorizeDeviceManagement();

        return response()->json([
            'success' => true,
            'devices' => Device::query()
                ->orderBy('device_name')
                ->get(['id', 'status', 'last_used_at', 'updated_at'])
                ->map(fn (Device $device) => [
                    'id' => $device->id,
                    'status' => $device->status,
                    'last_used_at' => $device->last_used_at?->format('M d, Y h:i A') ?? 'Never',
                    'updated_at' => $device->updated_at?->toIso8601String(),
                ])
                ->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function create()
    {
        $this->authorizeDeviceManagement();

        $branches = Branch::orderBy('branch_name')->get();

        return view('admin.devices.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorizeDeviceManagement();

        $request->validate([
            'mac_address' => ['required', 'string', 'max:20', 'regex:/^(?:[A-Fa-f0-9]{2}[:-]?){5}[A-Fa-f0-9]{2}$/', 'unique:devices,mac_address'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:devices,serial_number'],
            'wifi_mac_address' => ['nullable', 'string', 'max:20', 'regex:/^(?:[A-Fa-f0-9]{2}[:-]?){5}[A-Fa-f0-9]{2}$/'],
            'laptop_mac_address' => ['nullable', 'string', 'max:100'],
            'allowed_mac_addresses' => ['nullable', 'array'],
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:biometric_scanner,kiosk,computer',
            'branch_id' => 'nullable|exists:branches,id',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:1|max:10000',
            'location_check_enabled' => 'sometimes|boolean',
            'status' => 'required|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        $allowedMacs = $request->input('allowed_mac_addresses', []);
        $normalizedAllowedMacs = [];
        foreach ((array) $allowedMacs as $mac) {
            $normalized = Device::normalizeMacAddress($mac);
            if ($normalized !== '') {
                $normalizedAllowedMacs[] = $normalized;
            }
        }
        $normalizedAllowedMacs = array_values(array_unique($normalizedAllowedMacs));

        $device = Device::create([
            'mac_address' => Device::normalizeMacAddress($request->mac_address),
            'serial_number' => Device::normalizeSerialNumber($request->serial_number),
            'wifi_mac_address' => Device::normalizeMacAddress($request->mac_address),
            'laptop_mac_address' => $request->filled('laptop_mac_address') ? trim($request->laptop_mac_address) : null,
            'allowed_mac_addresses' => $normalizedAllowedMacs ? json_encode($normalizedAllowedMacs) : null,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'branch_id' => $request->branch_id,
            'location' => $request->location,
            'address' => $request->address,
            'latitude' => $request->filled('latitude') ? $request->latitude : null,
            'longitude' => $request->filled('longitude') ? $request->longitude : null,
            'radius_meters' => $request->input('radius_meters', 100),
            'location_check_enabled' => $request->boolean('location_check_enabled'),
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully.',
                'device' => $device->load('branch'),
            ], 201);
        }

        return redirect()->route('admin.devices.index')->with('success', 'Device registered successfully.');
    }

    public function edit(Device $device)
    {
        $this->authorizeDeviceManagement();

        $branches = Branch::orderBy('branch_name')->get();

        return view('admin.devices.edit', compact('device', 'branches'));
    }

    public function show(Device $device)
    {
        $this->authorizeDeviceManagement();

        $device->load('branch');

        return view('admin.devices.show', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $this->authorizeDeviceManagement();

        $request->validate([
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:biometric_scanner,kiosk,computer',
            'branch_id' => 'nullable|exists:branches,id',
            'mac_address' => ['required', 'string', 'max:20', 'regex:/^(?:[A-Fa-f0-9]{2}[:-]?){5}[A-Fa-f0-9]{2}$/', Rule::unique('devices', 'mac_address')->ignore($device->id)],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('devices', 'serial_number')->ignore($device->id)],
            'wifi_mac_address' => ['nullable', 'string', 'max:20', 'regex:/^(?:[A-Fa-f0-9]{2}[:-]?){5}[A-Fa-f0-9]{2}$/'],
            'laptop_mac_address' => ['nullable', 'string', 'max:100'],
            'allowed_mac_addresses' => ['nullable', 'array'],
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:1|max:10000',
            'location_check_enabled' => 'sometimes|boolean',
            'status' => 'required|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        $allowedMacs = $request->input('allowed_mac_addresses', []);
        $normalizedAllowedMacs = [];
        foreach ((array) $allowedMacs as $mac) {
            $normalized = Device::normalizeMacAddress($mac);
            if ($normalized !== '') {
                $normalizedAllowedMacs[] = $normalized;
            }
        }
        $normalizedAllowedMacs = array_values(array_unique($normalizedAllowedMacs));

        $device->update([
            'mac_address' => Device::normalizeMacAddress($request->mac_address),
            'serial_number' => $request->filled('serial_number') ? Device::normalizeSerialNumber($request->serial_number) : $device->serial_number,
            'wifi_mac_address' => Device::normalizeMacAddress($request->mac_address),
            'laptop_mac_address' => $request->filled('laptop_mac_address') ? trim($request->laptop_mac_address) : $device->laptop_mac_address,
            'allowed_mac_addresses' => $normalizedAllowedMacs ? json_encode($normalizedAllowedMacs) : null,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'branch_id' => $request->branch_id,
            'location' => $request->location,
            'address' => $request->address,
            'latitude' => $request->filled('latitude') ? $request->latitude : null,
            'longitude' => $request->filled('longitude') ? $request->longitude : null,
            'radius_meters' => $request->input('radius_meters', $device->radius_meters ?? 100),
            'location_check_enabled' => $request->boolean('location_check_enabled'),
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device updated successfully.',
                'device' => $device->fresh()->load('branch'),
            ]);
        }

        return redirect()->route('admin.devices.index')->with('success', 'Device updated successfully.');
    }

    public function branchDetails(Request $request, Branch $branch)
    {
        $this->authorizeDeviceManagement();

        return response()->json([
            'success' => true,
            'branch' => $branch->only(['id', 'branch_code', 'branch_name', 'address']),
        ]);
    }

    public function updateStatus(Request $request, Device $device)
    {
        $this->authorizeDeviceManagement();

        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $device->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device status updated.',
                'device' => $device->fresh()->load('branch'),
            ]);
        }

        return back()->with('success', 'Device status updated.');
    }

    public function destroy(Request $request, Device $device)
    {
        $this->authorizeDeviceManagement();

        $device->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device removed successfully.',
            ]);
        }

        return redirect()->route('admin.devices.index')->with('success', 'Device removed successfully.');
    }
}
