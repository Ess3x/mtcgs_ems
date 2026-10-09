<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $logs = AuditLog::with('user')
            ->where('created_at', '<=', now())
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('record_type'), fn ($query) => $query->where('auditable_type', $request->string('record_type')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $logs->getCollection()->transform(function (AuditLog $log) {
            $values = array_merge($log->old_values ?? [], $log->new_values ?? []);
            $auditable = null;
            if (is_string($log->auditable_type)
                && class_exists($log->auditable_type)
                && is_subclass_of($log->auditable_type, Model::class)) {
                $auditable = $log->auditable;
            }

            if ($auditable instanceof \App\Models\AttendanceLog) {
                $device = $auditable->device;
                $values += array_filter([
                    'device_id' => $auditable->device_id,
                    'device_serial_number' => $auditable->device_serial_number,
                    'location' => $auditable->location,
                    'device_mac_address' => $auditable->device_mac_address,
                    'wifi_mac_address' => $device?->wifi_mac_address,
                    'laptop_mac_address' => $device?->laptop_mac_address,
                ], fn ($value) => $value !== null && $value !== '');
            } elseif ($auditable instanceof \App\Models\Device) {
                $values += array_filter([
                    'device_id' => $auditable->laptop_mac_address,
                    'device_serial_number' => $auditable->serial_number,
                    'location' => $auditable->location,
                    'mac_address' => $auditable->mac_address,
                    'wifi_mac_address' => $auditable->wifi_mac_address,
                ], fn ($value) => $value !== null && $value !== '');
            } elseif ($auditable instanceof \App\Models\DTR) {
                $attendanceLogs = $auditable->attendanceLogs();
                $deviceValues = [];

                foreach ($attendanceLogs as $attendanceLog) {
                    $device = $attendanceLog->device;
                    $deviceValues[] = array_filter([
                        'device_id' => $attendanceLog->device_id ?: $device?->id,
                        'device_serial_number' => $attendanceLog->device_serial_number ?: $device?->serial_number,
                        'location' => $attendanceLog->location ?: $device?->location,
                        'device_mac_address' => $attendanceLog->device_mac_address ?: $device?->mac_address,
                        'wifi_mac_address' => $device?->wifi_mac_address,
                        'laptop_mac_address' => $device?->laptop_mac_address,
                    ], fn ($value) => $value !== null && $value !== '');
                }

                $deviceValues = array_values(array_filter($deviceValues));
                if ($deviceValues !== []) {
                    $values['attendance_devices'] = $deviceValues;
                }
            }

            $log->audit_context = $values;

            return $log;
        });

        $recordTypes = AuditLog::query()
            ->select('auditable_type')
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        return view('admin.audit-logs', [
            'logs' => $logs,
            'users' => \App\Models\User::whereHas('auditLogs')->orderBy('name')->get(['id', 'name']),
            'recordTypes' => $recordTypes,
        ]);
    }
}