<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:biometric_scanner,kiosk,computer',
            'branch_id' => 'nullable|exists:branches,id',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        Device::create([
            'mac_address' => Device::normalizeMacAddress($request->mac_address),
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'branch_id' => $request->branch_id,
            'location' => $request->location,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.devices.index')->with('success', 'Device registered successfully.');
    }

    public function edit(Device $device)
    {
        $this->authorizeDeviceManagement();

        $branches = Branch::orderBy('branch_name')->get();

        return view('admin.devices.edit', compact('device', 'branches'));
    }

    public function update(Request $request, Device $device)
    {
        $this->authorizeDeviceManagement();

        $request->validate([
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:biometric_scanner,kiosk,computer',
            'branch_id' => 'nullable|exists:branches,id',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        $device->update([
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'branch_id' => $request->branch_id,
            'location' => $request->location,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.devices.index')->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $this->authorizeDeviceManagement();

        $device->delete();

        return redirect()->route('admin.devices.index')->with('success', 'Device removed successfully.');
    }
}
