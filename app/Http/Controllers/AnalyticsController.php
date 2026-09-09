<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAnalytics();
        $filters = $this->filters($request);
        return view('analytics.index', array_merge($filters, ['analytics' => $this->buildAnalytics($filters)]));
    }

    public function data(Request $request)
    {
        $this->authorizeAnalytics();
        return response()->json($this->buildAnalytics($this->filters($request)));
    }

    private function buildAnalytics(array $filters): array
    {
        $start = $filters['start'];
        $end = $filters['end'];
        $branchId = $filters['branchId'];

        $attendance = AttendanceLog::whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();
        $activeEmployees = \App\Models\EmployeeProfile::whereHas('user', fn ($q) => $q->where('is_active', true))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count();

        $labels = [];
        $present = [];
        $late = [];
        $absent = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $day = $attendance->filter(fn ($log) => $log->attendance_date->isSameDay($date));
            $presentCount = $day->whereNotNull('am_in')->count();
            $labels[] = $date->format('M d');
            $present[] = $presentCount;
            $late[] = $day->where('late_minutes', '>', 0)->count();
            $absent[] = max(0, $activeEmployees - $presentCount);
        }

        $payroll = PayrollEntry::whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $leaves = LeaveRequest::whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->when($branchId, fn ($q) => $q->whereHas('employeeProfile', fn ($q) => $q->where('branch_id', $branchId)))
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'attendance' => [
                'labels' => $labels, 'present' => $present, 'late' => $late, 'absent' => $absent,
                'total_present' => $attendance->whereNotNull('am_in')->count(),
                'total_late' => $attendance->where('late_minutes', '>', 0)->count(),
                'total_overtime' => round($attendance->sum('overtime_hours'), 2),
            ],
            'payroll' => [
                'gross' => round((float) $payroll->sum('gross_pay'), 2),
                'deductions' => round((float) $payroll->sum('total_deductions'), 2),
                'net' => round((float) $payroll->sum('net_pay'), 2),
            ],
            'leaves' => [
                'approved' => (int) ($leaves['approved'] ?? 0),
                'pending' => (int) ($leaves['pending'] ?? 0),
                'rejected' => (int) ($leaves['rejected'] ?? 0),
            ],
        ];
    }

    private function filters(Request $request): array
    {
        $end = $request->filled('end') ? Carbon::parse($request->end) : today();
        $start = $request->filled('start') ? Carbon::parse($request->start) : $end->copy()->subDays(6);
        if ($start->gt($end)) [$start, $end] = [$end, $start];
        if ($start->diffInDays($end) > 90) $start = $end->copy()->subDays(90);

        $branchId = null;
        if (Auth::user()->isFinanceOfficer()) {
            $branchId = Auth::user()->getFinanceProfile()?->branch_id;
        }
        return compact('start', 'end', 'branchId');
    }

    private function authorizeAnalytics(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'finance_officer', 'finance_head'], true), 403);
    }
}
