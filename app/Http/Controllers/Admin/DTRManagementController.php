<?php

namespace App\Http\Controllers\Admin;

use App\Models\DTR;
use App\Models\EmployeeProfile;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DTRManagementController
{
    /**
     * Show DTR management dashboard for admins
     */
    public function index()
    {
        $user = Auth::user();

        // Check authorization - only admins and finance officers can access
        if (!$user->isAdmin() && !$user->isFinanceOfficer()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $pendingQuery = DTR::query()->with('employeeProfile');
        // Keep HR-approved DTRs visible to the Finance Head while they await computation.
        $approvedStatuses = ($user->isBranchAdmin() || $user->isSuperAdmin() || $user->isFinanceHead())
            ? ['approved', 'pending_finance_head']
            : ['approved'];
        $approvedQuery = DTR::whereIn('status', $approvedStatuses)->with(['employeeProfile.branch', 'payrollEntry']);

        if ($user->isBranchAdmin()) {
            $pendingQuery->where('status', 'submitted');
            $branchId = $user->getEffectiveBranchId();
            if ($branchId) {
                $pendingQuery->whereHas('employeeProfile', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
                $approvedQuery->whereHas('employeeProfile', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            } else {
                $pendingQuery->whereRaw('0 = 1');
                $approvedQuery->whereRaw('0 = 1');
            }
        } elseif ($user->isSuperAdmin()) {
            $pendingQuery->where('status', 'pending_system_admin');
        } elseif ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $ownEmployeeId = $financeProfile?->employee_profile_id;
            $branchId = $financeProfile?->branch_id ?? $user->branch_id ?? 1;

            if ($user->isFinanceHead()) {
                $pendingQuery->whereRaw('0 = 1');
                $pendingQuery->whereHas('employeeProfile', function ($query) use ($financeProfile) {
                    $query->where('branch_id', $financeProfile?->branch_id ?? Auth::user()->branch_id ?? 1);
                });
                $approvedQuery->whereHas('employeeProfile', function ($query) use ($financeProfile) {
                    $query->where('branch_id', $financeProfile?->branch_id ?? Auth::user()->branch_id ?? 1);
                });
            } elseif (!$ownEmployeeId) {
                $pendingQuery->whereRaw('0 = 1');
                $approvedQuery->whereRaw('0 = 1');
            } else {
                $pendingQuery->where('employee_profile_id', $ownEmployeeId)->where('status', 'approved');
                $approvedQuery->where('employee_profile_id', $ownEmployeeId)->where('status', 'approved');
            }
        }

        // Get all DTRs with pending approval (for Branch Head / admins)
        $pendingDTRs = $pendingQuery
            ->orderBy('period_end', 'desc')
            ->paginate(20, ['*'], 'pending_dtr_page');

        // Get approved DTRs ready for payroll (for finance officers)
        $approvedDTRs = $approvedQuery
            ->orderBy('period_end', 'desc')
            ->paginate(20, ['*'], 'approved_dtr_page');

        $totalDTRsQuery = DTR::query();
        $approvedDTRsCountQuery = DTR::whereIn('status', $approvedStatuses);
        $pendingCountQuery = DTR::query();

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if ($branchId) {
                $totalDTRsQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId));
                $approvedDTRsCountQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId));
                $pendingCountQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId))->where('status', 'submitted');
            } else {
                $totalDTRsQuery->whereRaw('0 = 1');
                $approvedDTRsCountQuery->whereRaw('0 = 1');
                $pendingCountQuery->whereRaw('0 = 1');
            }
        } elseif ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $ownEmployeeId = $financeProfile?->employee_profile_id;
            $branchId = $financeProfile?->branch_id ?? $user->branch_id ?? 1;
            if ($user->isFinanceHead()) {
                $totalDTRsQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId));
                $approvedDTRsCountQuery->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId));
                $pendingCountQuery->whereRaw('0 = 1');
            } elseif ($ownEmployeeId) {
                $totalDTRsQuery->where('employee_profile_id', $ownEmployeeId);
                $approvedDTRsCountQuery->where('employee_profile_id', $ownEmployeeId);
                $pendingCountQuery->where('employee_profile_id', $ownEmployeeId)->where('status', 'approved');
            } else {
                $totalDTRsQuery->whereRaw('0 = 1');
                $approvedDTRsCountQuery->whereRaw('0 = 1');
                $pendingCountQuery->whereRaw('0 = 1');
            }
        }

        // Get DTR stats
        $totalDTRs = $totalDTRsQuery->count();
        $approvedDTRsCount = $approvedDTRsCountQuery->count();
        $pendingCount = $user->isBranchAdmin()
            ? $pendingCountQuery->count()
            : ($user->isFinanceOfficer() ? $pendingCountQuery->count() : DTR::where('status', 'pending_system_admin')->count());
        $rejectedDTRs = $user->isFinanceOfficer() ? 0 : DTR::where('status', 'rejected')->count();

        $canApprove = $user->isAdmin() && ($user->isSuperAdmin() || $user->isBranchAdmin());

        return view('admin.dtr.index', compact(
            'pendingDTRs',
            'approvedDTRs',
            'totalDTRs',
            'approvedDTRsCount',
            'pendingCount',
            'rejectedDTRs',
            'canApprove'
        ));
    }

    public function exportSubmittedExcel()
    {
        $user = Auth::user();

        if (!$user->isBranchAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Only Branch Heads and administrators can export submitted DTRs.');
        }

        $query = DTR::with(['employeeProfile.branch', 'employeeProfile.shift'])
            ->orderBy('period_start')
            ->orderBy('employee_profile_id');

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if ($branchId) {
                $query->where('status', 'submitted')
                    ->whereHas('employeeProfile', fn ($profileQuery) => $profileQuery->where('branch_id', $branchId));
            } else {
                $query->whereRaw('0 = 1');
            }
        } elseif ($user->isSuperAdmin()) {
            $query->where('status', 'pending_system_admin');
        } else {
            $query->whereRaw('0 = 1');
        }

        $dtrs = $query->get();
        $workingDayService = app(\App\Services\WorkingDayService::class);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DTR Table');

        $sheet->setCellValue('A1', 'MOTHER THERESA COLEGIO GROUP OF SCHOOLS - DTR TABLE');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $periodHeaders = [];
        $firstDtr = $dtrs->first();

        if ($firstDtr) {
            $current = $firstDtr->period_start->copy();
            $end = $firstDtr->period_end->copy();

            while ($current <= $end) {
                if (!in_array($current->dayOfWeek, [0, 6], true)) {
                    $periodHeaders[] = $current->copy();
                }
                $current->addDay();
            }
        }

        $sheet->setCellValue('A3', 'Employee');
        $sheet->setCellValue('B3', 'Employee No.');

        $columnIndex = 3;
        foreach ($periodHeaders as $headerDate) {
            $startColumn = Coordinate::stringFromColumnIndex($columnIndex);
            $endColumn = Coordinate::stringFromColumnIndex($columnIndex + 1);

            $sheet->mergeCells($startColumn . '3:' . $endColumn . '3');
            $sheet->setCellValue($startColumn . '3', $headerDate->format('m-d-Y'));
            $sheet->setCellValue($startColumn . '4', 'TIME-IN');
            $sheet->setCellValue($endColumn . '4', 'TIME-OUT');

            $columnIndex += 2;
        }

        $lastHeaderColumn = Coordinate::stringFromColumnIndex($columnIndex - 1);
        $sheet->mergeCells('A1:' . $lastHeaderColumn . '1');

        $sheet->getStyle('A3:' . $lastHeaderColumn . '4')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $lastHeaderColumn . '4')->getFill()
            ->setFillType('solid')
            ->getStartColor()
            ->setRGB('D9EAF7');
        $sheet->getStyle('A3:' . $lastHeaderColumn . '4')->getAlignment()->setWrapText(true);

        $row = 5;
        $redFontCells = [];
        $summaryRows = [];

        foreach ($dtrs as $dtr) {
            $employee = $dtr->employeeProfile;
            $logsByDate = $dtr->attendanceLogs()->keyBy(function ($log) {
                return $log->attendance_date->format('Y-m-d');
            });

            $approvedLeaves = LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $dtr->period_end)
                ->whereDate('end_date', '>=', $dtr->period_start)
                ->get();

            $approvedLeaveByDate = [];
            foreach ($approvedLeaves as $leave) {
                $current = $leave->start_date->copy();
                $end = $leave->end_date->copy();
                while ($current <= $end) {
                    $approvedLeaveByDate[$current->format('Y-m-d')] = $leave;
                    $current->addDay();
                }
            }

            $sheet->setCellValue('A' . $row, $employee?->first_name . ' ' . $employee?->last_name);
            $sheet->setCellValue('B' . $row, $employee?->employee_number);

            $breakdown = $dtr->getCalculationBreakdown();
            $earlyOutMinutes = $logsByDate->sum(function ($log) {
                if (!$log->pm_out) {
                    return 0;
                }

                $scheduledEnd = $log->pm_out->copy()->setTime(17, 0, 0);

                return $log->pm_out->lt($scheduledEnd)
                    ? (int) $log->pm_out->diffInMinutes($scheduledEnd)
                    : 0;
            });
            $summaryRows[] = [
                'employee' => $employee?->first_name . ' ' . $employee?->last_name,
                'days_present' => $breakdown['days_present'],
                'days_absent' => $breakdown['days_absent'],
                'late_minutes' => (int) $dtr->late_minutes,
                'early_out_minutes' => $earlyOutMinutes,
            ];

            $columnIndex = 3;
            foreach ($periodHeaders as $headerDate) {
                $dateStr = $headerDate->format('Y-m-d');
                $log = $logsByDate->get($dateStr);
                $approvedLeave = $approvedLeaveByDate[$dateStr] ?? null;
                $startColumn = Coordinate::stringFromColumnIndex($columnIndex);
                $endColumn = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $branchId = $employee?->branch_id;
                $isHoliday = $workingDayService->isHoliday($headerDate, $branchId);
                $isSuspension = $workingDayService->isSuspension($headerDate, $branchId);
                $hasAttendanceTime = $log && ($log->am_in || $log->am_out || $log->pm_in || $log->pm_out);

                if ($approvedLeave) {
                    $sheet->mergeCells($startColumn . $row . ':' . $endColumn . $row);
                    $sheet->setCellValue($startColumn . $row, (bool) $approvedLeave->is_absent ? 'Leave Without Pay' : 'Paid Leave');
                } elseif ($hasAttendanceTime) {
                    $sheet->setCellValue($startColumn . $row, $log->am_in ? $log->am_in->format('h:i A') : '--');
                    $sheet->setCellValue($endColumn . $row, $log->pm_out ? $log->pm_out->format('h:i A') : '--');

                    $isLate = (int) ($log->late_minutes ?? 0) > 0;
                    $isEarlyOut = $log->pm_out && $log->pm_out->lt($headerDate->copy()->setTime(17, 0, 0));

                    if ($isLate) {
                        $redFontCells[] = $startColumn . $row;
                    }

                    if ($isEarlyOut) {
                        $redFontCells[] = $endColumn . $row;
                    }
                } else {
                    $sheet->mergeCells($startColumn . $row . ':' . $endColumn . $row);
                    $status = $isSuspension
                        ? 'Suspension'
                        : ($isHoliday ? 'Holiday' : ($headerDate->isPast() ? 'Absent' : 'Pending'));
                    $sheet->setCellValue($startColumn . $row, $status);
                }

                $columnIndex += 2;
            }

            $row++;
        }

        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A3:' . $lastColumn . $lastRow)
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '7F8C8D'],
                    ],
                ],
            ]);

        $sheet->getStyle('A3:' . $lastColumn . '4')
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAF7'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

        for ($row = 5; $row <= $lastRow; $row++) {
            for ($column = 'A'; $column <= $lastColumn; $column++) {
                $cell = $sheet->getCell($column . $row);
                $value = $cell->getValue();

                if (!is_string($value)) {
                    continue;
                }

                $style = $sheet->getStyle($column . $row);

                if (str_contains($value, 'Leave Without Pay')) {
                    $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $style->getFill()->getStartColor()->setRGB('5CCB5C');
                    $style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                } elseif (str_contains($value, 'Paid Leave')) {
                    $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $style->getFill()->getStartColor()->setRGB('00B0F0');
                    $style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                } elseif (str_contains($value, 'Holiday')) {
                    $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $style->getFill()->getStartColor()->setRGB('FFFF00');
                    $style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                } elseif (str_contains($value, 'Suspension')) {
                    $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $style->getFill()->getStartColor()->setRGB('FF0000');
                    $style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                } elseif (str_contains($value, 'Absent')) {
                    $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $style->getFill()->getStartColor()->setRGB('FF0000');
                    $style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                }
            }
        }

        $sheet->getStyle('A5:' . $lastColumn . $lastRow)->getFont()->setBold(true);

        foreach (array_unique($redFontCells) as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => 'FF0000'],
                ],
            ]);
        }

        $summaryTitleRow = $lastRow + 2;
        $summaryHeaderRow = $summaryTitleRow + 1;
        $summaryFirstDataRow = $summaryHeaderRow + 1;
        $summaryTotalRow = $summaryFirstDataRow + count($summaryRows);
        $sheet->mergeCells('A' . $summaryTitleRow . ':E' . $summaryTitleRow);
        $sheet->setCellValue('A' . $summaryTitleRow, 'DTR SUMMARY BY EMPLOYEE');
        $sheet->getStyle('A' . $summaryTitleRow . ':E' . $summaryTitleRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => ['horizontal' => 'center'],
        ]);

        $summaryHeaders = ['Employee', 'Days Present', 'Total Absent', 'Total Late (min)', 'Total Early Out (min)'];
        foreach ($summaryHeaders as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . $summaryHeaderRow, $header);
        }
        $sheet->getStyle('A' . $summaryHeaderRow . ':E' . $summaryHeaderRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAF7'],
            ],
            'alignment' => ['horizontal' => 'center', 'wrapText' => true],
        ]);

        $grandTotals = ['days_present' => 0, 'days_absent' => 0, 'late_minutes' => 0, 'early_out_minutes' => 0];
        foreach ($summaryRows as $index => $summary) {
            $summaryRow = $summaryFirstDataRow + $index;
            $sheet->fromArray([
                $summary['employee'],
                $summary['days_present'],
                $summary['days_absent'],
                $summary['late_minutes'],
                $summary['early_out_minutes'],
            ], null, 'A' . $summaryRow);

            foreach ($grandTotals as $key => $total) {
                $grandTotals[$key] += $summary[$key];
            }
        }

        $sheet->fromArray([
            'Grand Total',
            $grandTotals['days_present'],
            $grandTotals['days_absent'],
            $grandTotals['late_minutes'],
            $grandTotals['early_out_minutes'],
        ], null, 'A' . $summaryTotalRow);
        $sheet->getStyle('A' . $summaryTotalRow . ':E' . $summaryTotalRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $summaryTitleRow . ':E' . $summaryTotalRow)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('7F8C8D'));
        $sheet->getStyle('B' . $summaryFirstDataRow . ':E' . $summaryTotalRow)
            ->getAlignment()->setHorizontal('center');
        $sheet->getRowDimension($summaryHeaderRow)->setRowHeight(30);
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A5');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'submitted-dtrs-' . now()->format('Ymd-His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function submitAllToHr()
    {
        $user = Auth::user();
        abort_unless($user->isBranchAdmin(), 403, 'Only the Branch Head can submit DTRs to HR.');

        $branchId = $user->getEffectiveBranchId();
        abort_unless($branchId, 422, 'Branch is not assigned.');

        $dtrs = DTR::with('employeeProfile')
            ->where('status', 'submitted')
            ->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId))
            ->get();

        if ($dtrs->isEmpty()) {
            return back()->with('error', 'There are no submitted DTRs ready to send to HR.');
        }

        $dtrs->each(function (DTR $dtr) {
            $dtr->update([
                'status' => 'pending_system_admin',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
            $this->notifySystemReviewers($dtr);
        });

        return back()->with('success', $dtrs->count() . ' DTR(s) and the combined attendance report were submitted to HR for review.');
    }

    public function submitAllToFinanceHead()
    {
        $user = Auth::user();
        abort_unless($user->isSuperAdmin(), 403, 'Only HR can submit DTRs to the Finance Head.');

        $dtrs = DTR::with('employeeProfile')
            ->where('status', 'pending_system_admin')
            ->get();

        if ($dtrs->isEmpty()) {
            return back()->with('error', 'There are no DTRs awaiting HR review.');
        }

        $dtrs->each(function (DTR $dtr) {
            $dtr->update(['status' => 'pending_finance_head']);
        });

        $dtrs->each(fn (DTR $dtr) => $this->notifyFinanceHeads($dtr));

        return back()->with('success', $dtrs->count() . ' DTR(s) and the combined Excel report were submitted to the Finance Head.');
    }

    public function returnAllToBranchHead()
    {
        $user = Auth::user();
        abort_unless($user->isSuperAdmin(), 403, 'Only HR can return DTRs to the Branch Head.');

        $dtrs = DTR::with('employeeProfile')
            ->where('status', 'pending_system_admin')
            ->get();

        if ($dtrs->isEmpty()) {
            return back()->with('error', 'There are no DTRs awaiting HR review to return.');
        }

        $dtrs->each(function (DTR $dtr) {
            $dtr->update([
                'status' => 'submitted',
                'approved_by' => null,
                'approved_at' => null,
            ]);
            $this->notifyBranchReviewers($dtr);
        });

        return back()->with('success', $dtrs->count() . ' DTR(s) were returned to the Branch Head for review.');
    }

    public function computeDtr($dtrId)
    {
        $user = Auth::user();
        abort_unless($user->isFinanceHead(), 403, 'Only the Finance Head can compute this DTR.');

        $dtr = DTR::with('employeeProfile')->findOrFail($dtrId);
        $financeProfile = $user->getFinanceProfile();
        $branchId = $financeProfile?->branch_id ?? $user->branch_id ?? 1;
        abort_unless($financeProfile && $dtr->employeeProfile?->branch_id === $branchId, 403);
        abort_unless($dtr->status === 'pending_finance_head', 422, 'This DTR is not awaiting Finance Head review.');

        $dtr->calculateTotals();
        $dtr->approve($user->id, 'finance_head');
        $dtr->employeeProfile?->user?->notify(new SystemNotification(
            'DTR computed by Finance Head',
            'Your DTR was reviewed and computed by the Finance Head.',
            'dtr_computed',
            route('employee.dtr.show', $dtr->id)
        ));

        return back()->with('success', 'DTR attendance and payroll totals computed successfully.');
    }

    public function attendanceManagementIndex()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            abort_unless($branchId, 422, 'Branch is not assigned.');

            $pendingAdjustments = AttendanceLog::query()
                ->with('employeeProfile.branch')
                ->whereHas('employeeProfile', fn ($query) => $query->where('branch_id', $branchId))
                ->where('override_status', 'pending_branch')
                ->orderBy('attendance_date', 'desc')
                ->orderByDesc('id')
                ->paginate(20);

            $pendingBranchCount = $pendingAdjustments->total();
            $pendingSystemAdminCount = 0;
        } elseif ($user->isSuperAdmin()) {
            $pendingAdjustments = AttendanceLog::query()
                ->with('employeeProfile.branch')
                ->whereIn('override_status', ['pending_branch', 'pending_system_admin'])
                ->orderBy('attendance_date', 'desc')
                ->orderByDesc('id')
                ->paginate(20);

            $pendingBranchCount = AttendanceLog::where('override_status', 'pending_branch')->count();
            $pendingSystemAdminCount = AttendanceLog::where('override_status', 'pending_system_admin')->count();
        } else {
            abort(403, 'You do not have access to attendance management.');
        }

        return view('admin.attendance-management.index', compact(
            'pendingAdjustments',
            'pendingBranchCount',
            'pendingSystemAdminCount'
        ));
    }

    /**
     * Show DTR details for approval
     */
    public function show($dtrId)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isFinanceOfficer()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $branchId = $financeProfile?->branch_id ?? $user->branch_id ?? 1;
            $isOutsideBranch = !$financeProfile || $dtr->employeeProfile?->branch_id !== $branchId;
            $canView = $user->isFinanceHead()
                ? !$isOutsideBranch
                : $dtr->employee_profile_id === $financeProfile?->employee_profile_id;
            if (!$canView) {
                return redirect('/dashboard')->with('error', 'You can only view DTR records in your assigned scope.');
            }
        }
        
        $attendanceLogs = AttendanceLog::where('employee_profile_id', $dtr->employee_profile_id)
            ->whereBetween('attendance_date', [$dtr->period_start, $dtr->period_end])
            ->orderBy('attendance_date')
            ->get();

        $approvedLeaves = \App\Models\LeaveRequest::where('employee_profile_id', $dtr->employee_profile_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dtr->period_end)
            ->whereDate('end_date', '>=', $dtr->period_start)
            ->get();

        $breakdown = $dtr->getCalculationBreakdown();
        $workingDayService = app(\App\Services\WorkingDayService::class);
        $branchId = $dtr->employeeProfile?->branch_id;
        $logsByDate = $attendanceLogs->keyBy(fn ($log) => $log->attendance_date->format('Y-m-d'));
        $totalEarlyOutMinutes = 0;
        $totalHolidays = 0;
        $totalSuspensions = 0;
        $totalSuspendedHours = 0.0;

        foreach ($attendanceLogs as $log) {
            if ($workingDayService->isSuspension($log->attendance_date, $branchId)) {
                continue;
            }

            if ($log->pm_out) {
                $scheduledEnd = $log->pm_out->copy()->setTime(17, 0, 0);
                if ($log->pm_out->lt($scheduledEnd)) {
                    $totalEarlyOutMinutes += (int) $log->pm_out->diffInMinutes($scheduledEnd);
                }
            }
        }

        $currentDate = $dtr->period_start->copy();
        while ($currentDate <= $dtr->period_end) {
            $dateKey = $currentDate->format('Y-m-d');
            $isSuspension = $workingDayService->isSuspension($currentDate, $branchId);
            $totalHolidays += $workingDayService->isHoliday($currentDate, $branchId) ? 1 : 0;

            if ($isSuspension) {
                $totalSuspensions++;
                $log = $logsByDate->get($dateKey);
                $minutes = 0;

                if ($log?->am_in && $log?->am_out) {
                    $minutes += $log->am_in->diffInMinutes($log->am_out);
                }
                if ($log?->pm_in && $log?->pm_out) {
                    $minutes += $log->pm_in->diffInMinutes($log->pm_out);
                }
                if (!$minutes && $log?->am_in && $log?->pm_out) {
                    $minutes = $log->am_in->diffInMinutes($log->pm_out);
                }

                $totalSuspendedHours += $minutes / 60;
            }

            $currentDate->addDay();
        }

        $stats = [
            'total_hours' => $breakdown['total_hours'],
            'total_overtime' => $dtr->getTotalOvertimeHours(),
            'total_late_minutes' => $dtr->getTotalLateMinutes(),
            'total_early_out_minutes' => $totalEarlyOutMinutes,
            'days_present' => $breakdown['days_present'],
            'days_absent' => $breakdown['days_absent'],
            'total_paid_leave' => $breakdown['paid_leave'],
            'total_leave_without_pay' => $breakdown['leave_without_pay'],
            'total_holidays' => $totalHolidays,
            'total_suspensions' => $totalSuspensions,
            'total_half_days' => $breakdown['half_day_days'],
            'total_suspended_hours' => round($totalSuspendedHours, 2),
            'working_days' => max(1, $breakdown['days_present'] + $breakdown['days_absent'] + $breakdown['paid_leave'] + $breakdown['leave_without_pay']),
        ];

        return view('admin.dtr.show', compact('dtr', 'attendanceLogs', 'approvedLeaves', 'stats'));
    }

    /**
     * Approve DTR (Admin only)
     */
    public function approve($dtrId)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Only administrators can approve DTRs');
        }

        if (!$user->isSuperAdmin() && !$user->isBranchAdmin()) {
            return redirect()->back()->with('error', 'Only the Branch Head or System Administrator can approve DTRs');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || !$dtr->employeeProfile || $dtr->employeeProfile->branch_id !== $branchId) {
                return redirect()->back()->with('error', 'You can only approve DTRs from your branch.');
            }

            if ($dtr->status !== 'submitted') {
                return redirect()->back()->with('error', 'This DTR is no longer pending branch approval.');
            }

            $dtr->approve($user->id, 'branch_admin');
            $this->notifySystemReviewers($dtr);
            return redirect()->back()->with('success', 'DTR approved by Branch Head and forwarded to the System Administrator for review.');
        }

        if ($dtr->status !== 'pending_system_admin') {
            return redirect()->back()->with('error', 'This DTR is not awaiting System Administrator review.');
        }

        $dtr->approve($user->id, 'super_admin');
        $this->notifyFinanceHeads($dtr);

        $dtr->employeeProfile?->user?->notify(new SystemNotification(
            'DTR approved',
            'Your DTR has been approved by the System Administrator.',
            'dtr_approved',
            route('employee.dtr.show', $dtr->id)
        ));

        return redirect()->back()->with('success', 'DTR reviewed and approved by the System Administrator.');
    }

    /**
     * Reject DTR with remarks (Admin only)
     */
    public function reject($dtrId)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Only administrators can reject DTRs');
        }

        if (!$user->isSuperAdmin() && !$user->isBranchAdmin()) {
            return redirect()->back()->with('error', 'Only the Branch Head or System Administrator can reject DTRs');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || !$dtr->employeeProfile || $dtr->employeeProfile->branch_id !== $branchId) {
                return redirect()->back()->with('error', 'You can only reject DTRs from your branch.');
            }
        }

        // Reset DTR to draft status for employee to resubmit
        $dtr->update([
            'status' => 'draft',
            'remarks' => request('remarks') ?? 'Rejected by administrator'
        ]);

        $dtr->employeeProfile?->user?->notify(new SystemNotification(
            'DTR rejected',
            'Your DTR was returned for correction. Please review the remarks and submit it again.',
            'dtr_rejected',
            route('employee.dtr.show', $dtr->id)
        ));

        return redirect()->back()->with('success', 'DTR rejected and sent back to employee');
    }

    public function approveAttendanceAdjustment(AttendanceLog $attendance)
    {
        $user = Auth::user();
        if (!$user->isAdmin() || (!$user->isBranchAdmin() && !$user->isSuperAdmin())) {
            abort(403);
        }

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || $attendance->employeeProfile?->branch_id !== $branchId) {
                abort(403);
            }
            if ($attendance->override_status !== 'pending_branch') {
                return back()->with('error', 'This adjustment is not awaiting Branch Head review.');
            }

            $attendance->update(['override_status' => 'pending_system_admin', 'override_reviewed_by' => $user->id, 'override_reviewed_at' => now()]);
            $this->notifyAttendanceSystemReviewers($attendance, 'Attendance adjustment needs final approval', 'attendance_adjustment_pending_system');

            return back()->with('success', 'Adjustment forwarded to Super Admin for final approval.');
        }

        if (!in_array($attendance->override_status, ['pending_system_admin'], true)) {
            return back()->with('error', 'This adjustment is not awaiting final review.');
        }
        if (!$attendance->corrected_time_in && !$attendance->corrected_pm_in && !$attendance->corrected_time_out) {
            return back()->with('error', 'This adjustment has no corrected attendance time and cannot be approved.');
        }

        $updates = [
            'override_status' => 'approved',
            'override_reviewed_by' => $user->id,
            'override_reviewed_at' => now(),
            'status' => 'present',
            'late_minutes' => 0,
        ];
        if ($attendance->corrected_time_in) {
            $updates['am_in'] = $attendance->corrected_time_in;
        }
        if ($attendance->corrected_pm_in) {
            $updates['pm_in'] = $attendance->corrected_pm_in;
        }
        if ($attendance->corrected_time_out) {
            $updates['pm_out'] = $attendance->corrected_time_out;
        }
        $attendance->update($updates);
        $dtr = DTR::where('employee_profile_id', $attendance->employee_profile_id)
            ->whereDate('period_start', '<=', $attendance->attendance_date)
            ->whereDate('period_end', '>=', $attendance->attendance_date)
            ->first();
        if ($dtr) {
            $dtr->calculateTotals()->save();
        }
        $attendance->employeeProfile?->user?->notify(new SystemNotification(
            'Attendance adjustment approved',
            'Your attendance time correction was approved as Present.',
            'attendance_adjustment_approved',
            route('employee.dtr.show', $this->dtrIdForAttendance($attendance))
        ));

        return back()->with('success', 'Attendance adjustment approved as Present.');
    }

    public function rejectAttendanceAdjustment(AttendanceLog $attendance)
    {
        $user = Auth::user();
        if (!$user->isAdmin() || (!$user->isBranchAdmin() && !$user->isSuperAdmin())) {
            abort(403);
        }

        if ($user->isBranchAdmin()) {
            $branchId = $user->getEffectiveBranchId();
            if (!$branchId || $attendance->employeeProfile?->branch_id !== $branchId) {
                abort(403);
            }
            if ($attendance->override_status !== 'pending_branch') {
                return back()->with('error', 'This adjustment is not awaiting Branch Head review.');
            }
        }

        if ($user->isBranchAdmin()) {
            $attendance->update(['override_status' => 'rejected', 'override_reviewed_by' => $user->id, 'override_reviewed_at' => now()]);
            $attendance->employeeProfile?->user?->notify(new SystemNotification(
                'Attendance adjustment rejected',
                'Your request to mark the attendance correction as Present was rejected by the Branch Admin.',
                'attendance_adjustment_rejected',
                route('employee.dtr.show', $this->dtrIdForAttendance($attendance))
            ));

            return back()->with('success', 'Attendance adjustment rejected.');
        }

        if ($attendance->override_status !== 'pending_system_admin') {
            return back()->with('error', 'This adjustment is not awaiting final review.');
        }

        $attendance->update(['override_status' => 'rejected', 'override_reviewed_by' => $user->id, 'override_reviewed_at' => now()]);
        $attendance->employeeProfile?->user?->notify(new SystemNotification(
            'Attendance adjustment rejected',
            'Your request to mark the late time-in as Present was rejected.',
            'attendance_adjustment_rejected',
            route('employee.dtr.show', $this->dtrIdForAttendance($attendance))
        ));

        return back()->with('success', 'Attendance adjustment rejected.');
    }

    private function notifyAttendanceSystemReviewers(AttendanceLog $attendance, string $title, string $type): void
    {
        User::where('role', 'admin')->where(function ($query) {
            $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
        })->where('is_active', true)->get()->each(fn ($recipient) => $recipient->notify(new SystemNotification(
            $title,
            'An attendance adjustment was forwarded for final review.',
            $type,
            route('admin.dtr.show', $this->dtrIdForAttendance($attendance))
        )));
    }

    private function dtrIdForAttendance(AttendanceLog $attendance): int
    {
        return (int) DTR::where('employee_profile_id', $attendance->employee_profile_id)
            ->whereDate('period_start', '<=', $attendance->attendance_date)
            ->whereDate('period_end', '>=', $attendance->attendance_date)
            ->orderByDesc('id')
            ->value('id');
    }

    private function notifySystemReviewers(DTR $dtr): void
    {
        User::where('role', 'admin')
            ->where(function ($query) {
                $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
            })
            ->where('is_active', true)
            ->get()
            ->each(fn ($recipient) => $recipient->notify(new SystemNotification(
                'DTR needs final approval',
                'A DTR has been approved by the Branch Head and is waiting for final review.',
                'dtr_pending_system',
                route('admin.dtr.show', $dtr->id)
            )));
    }

    private function notifyFinanceHeads(DTR $dtr): void
    {
        User::where('role', 'finance_head')
            ->where('is_active', true)
            ->get()
            ->each(fn (User $financeHead) => $financeHead->notify(new SystemNotification(
                'DTR ready for Finance Head computation',
                'A DTR and the combined Excel report were submitted by HR for your attendance review and computation.',
                'dtr_pending_finance_head',
                route('admin.dtr.show', $dtr->id)
            )));
    }

    private function notifyBranchReviewers(DTR $dtr): void
    {
        $branchId = $dtr->employeeProfile?->branch_id;

        if (!$branchId) {
            return;
        }

        User::where(function ($query) use ($branchId) {
                $query->where(function ($branchQuery) use ($branchId) {
                    $branchQuery->where('role', 'branch_head')
                        ->whereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $branchId));
                })->orWhere(function ($branchQuery) use ($branchId) {
                    $branchQuery->where('role', 'admin')
                        ->where('admin_type', 'branch_admin')
                        ->where(function ($userQuery) use ($branchId) {
                            $userQuery->where('branch_id', $branchId)
                                ->orWhereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $branchId));
                        });
                });
            })
            ->where('is_active', true)
            ->get()
            ->each(fn (User $recipient) => $recipient->notify(new SystemNotification(
                'DTR returned for Branch Head review',
                'A DTR was returned by the System Administrator and needs Branch Head review again.',
                'dtr_returned_branch',
                route('admin.dtr.show', $dtr->id)
            )));
    }

    /**
     * Show all DTRs for a specific employee
     */
    public function employeeDTRs($employeeId)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isFinanceOfficer()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $employeeProfile = EmployeeProfile::findOrFail($employeeId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            if (!$financeProfile || $employeeProfile->id !== $financeProfile->employee_profile_id) {
                return redirect('/dashboard')->with('error', 'You can only view your own DTR records.');
            }
        }
        
        $dtrs = DTR::where('employee_profile_id', $employeeId)
            ->orderBy('period_start', 'desc')
            ->paginate(20);

        return view('admin.dtr.employee-dtrs', compact('employeeProfile', 'dtrs'));
    }
}
