<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\AttendanceLog;
use App\Models\DTR;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\AttendanceRecorded;
use App\Events\AttendanceRecordedEvent;

class AttendanceController extends Controller
{
    public function clockAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'employee_number' => 'required|string',
            'attendance_type' => 'required|in:am_in,am_out,pm_in,pm_out',
            'fingerprint_data' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Normalize employee number and find employee by branch
            $normalizedEmployeeNumber = strtoupper(preg_replace('/[^A-Z0-9]/', '', $request->employee_number));

            $employee = EmployeeProfile::whereRaw(
                    "REPLACE(REPLACE(UPPER(employee_number), '-', ''), ' ', '') = ?",
                    [$normalizedEmployeeNumber]
                )
                ->where('branch_id', $request->branch_id)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found in selected branch'
                ], 404);
            }

            // Check if employee is active
            if ($employee->user && !$employee->user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee account is inactive'
                ], 403);
            }

            $today = Carbon::today();
            $attendanceType = $request->attendance_type;

            // Get or create today's attendance record
            $attendance = AttendanceLog::firstOrNew([
                'employee_profile_id' => $employee->id,
                'attendance_date' => $today
            ]);

            // Check attendance type and update accordingly
            switch ($attendanceType) {
                case 'am_in':
                    if ($attendance->am_in) {
                        return response()->json([
                            'success' => false,
                            'message' => 'AM time-in already recorded for today'
                        ], 400);
                    }
                    $attendance->am_in = now();
                    $message = 'AM Time-in recorded successfully';
                    break;

                case 'am_out':
                    if (!$attendance->am_in) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot record AM time-out without AM time-in'
                        ], 400);
                    }
                    if ($attendance->am_out) {
                        return response()->json([
                            'success' => false,
                            'message' => 'AM time-out already recorded for today'
                        ], 400);
                    }
                    $attendance->am_out = now();
                    $message = 'AM Time-out (Lunch) recorded successfully';
                    break;

                case 'pm_in':
                    if (!$attendance->am_out) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot record PM time-in without AM time-out'
                        ], 400);
                    }
                    if ($attendance->pm_in) {
                        return response()->json([
                            'success' => false,
                            'message' => 'PM time-in already recorded for today'
                        ], 400);
                    }
                    $attendance->pm_in = now();
                    $message = 'PM Time-in recorded successfully';
                    break;

                case 'pm_out':
                    if (!$attendance->pm_in) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot record PM time-out without PM time-in'
                        ], 400);
                    }
                    if ($attendance->pm_out) {
                        return response()->json([
                            'success' => false,
                            'message' => 'PM time-out already recorded for today'
                        ], 400);
                    }
                    $attendance->pm_out = now();
                    $message = 'PM Time-out recorded successfully';
                    break;
            }

            // Calculate late minutes if this is AM time-in
            if ($attendanceType === 'am_in') {
                $scheduledTime = $employee->shift
                    ? Carbon::today()->setTimeFromTimeString($employee->shift->start_time)
                    : Carbon::createFromTime(7, 0, 0);
                $actualTime = Carbon::parse($attendance->am_in);

                if ($actualTime->greaterThanOrEqualTo($scheduledTime)) {
                    $attendance->late_minutes = $actualTime->diffInMinutes($scheduledTime);
                } else {
                    $attendance->late_minutes = 0;
                }
            }

            // Calculate overtime if this is PM time-out
            if ($attendanceType === 'pm_out') {
                $scheduledEndTime = $employee->shift
                    ? Carbon::today()->setTimeFromTimeString($employee->shift->end_time)
                    : Carbon::createFromTime(17, 0, 0);
                $actualEndTime = Carbon::parse($attendance->pm_out);

                if ($actualEndTime->greaterThan($scheduledEndTime)) {
                    $attendance->overtime_hours = $actualEndTime->diffInHours($scheduledEndTime);
                } else {
                    $attendance->overtime_hours = 0;
                }
            }

            // Set status based on attendance
            if ($attendance->am_in && $attendance->am_out && $attendance->pm_in && $attendance->pm_out) {
                $attendance->status = 'Complete';
            } elseif ($attendance->am_in) {
                $attendance->status = 'Present';
            } else {
                $attendance->status = 'Incomplete';
            }

            $attendance->branch_id = $employee->branch_id;
            $attendance->employee_id = $employee->user_id ?? null;
            $attendance->verification_method = 'fingerprint';
            $attendance->save();

            // Generate DTR for current period automatically
            DTR::generateForCurrentPeriod($employee->id, $attendance->attendance_date);

            // Send email notification to employee if they have a user account with email
            if ($employee->user && $employee->user->email) {
                try {
                    Mail::to($employee->user->email)->send(new AttendanceRecorded($attendance, $employee, $attendanceType));
                } catch (\Exception $e) {
                    // Log email sending failure but don't fail the attendance recording
                    \Log::warning('Failed to send attendance notification email: ' . $e->getMessage());
                }
            }

            // Broadcast real-time notification to employee's dashboard
            if ($employee->user_id) {
                broadcast(new AttendanceRecordedEvent($attendance, $employee, $attendanceType));
            }

            // Get branch name for response
            $branch = Branch::find($request->branch_id);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                    'employee_number' => $employee->employee_number,
                    'branch' => $branch ? $branch->branch_name : 'Unknown',
                    'attendance_type' => $attendanceType,
                    'timestamp' => now()->format('M d, Y h:i A')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}