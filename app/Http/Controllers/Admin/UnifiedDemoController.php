<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;

class UnifiedDemoController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        return view('admin.unified-demo', compact('branches'));
    }

    public function simulateAttendance(Request $request)
    {
        // Simulate attendance recording
        $data = [
            'success' => true,
            'message' => 'Attendance recorded successfully (Demo)',
            'data' => [
                'employee_name' => $request->employee_name ?? 'Demo Employee',
                'employee_number' => $request->employee_number ?? 'DEMO001',
                'branch' => $request->branch_name ?? 'Demo Branch',
                'attendance_type' => $request->attendance_type ?? 'am_in',
                'timestamp' => now()->format('M d, Y h:i A'),
                'fingerprint_matched' => true
            ]
        ];

        return response()->json($data);
    }

    public function simulateNotification(Request $request)
    {
        // Simulate notification sending
        $data = [
            'success' => true,
            'message' => 'Notification sent successfully (Demo)',
            'data' => [
                'employee_name' => $request->employee_name ?? 'Demo Employee',
                'employee_number' => $request->employee_number ?? 'DEMO001',
                'notification_type' => $request->notification_type ?? 'clock_in',
                'email_sent' => $request->send_email ?? false,
                'realtime_sent' => $request->send_realtime ?? false,
                'timestamp' => now()->format('M d, Y h:i A')
            ]
        ];

        return response()->json($data);
    }
}
