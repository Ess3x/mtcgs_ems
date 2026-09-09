<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Services\PayrollService;
use App\Services\PayrollComputationService;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\PayrollProcessed;

class PayrollController extends Controller
{
    protected $payrollService;
    protected $payrollComputationService;
    
    public function __construct(PayrollService $payrollService, PayrollComputationService $payrollComputationService)
    {
        $this->payrollService = $payrollService;
        $this->payrollComputationService = $payrollComputationService;
        $this->middleware('auth');
    }
    
    private function checkAdmin()
    {
        if (Auth::user()->role !== 'admin' && !in_array(Auth::user()->role, ['finance_officer', 'finance_head'], true)) {
            abort(403, 'Unauthorized');
        }
    }
    
    // List all payroll periods
    public function periods()
    {
        $this->checkAdmin();
        $periods = PayrollPeriod::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.payroll.periods', compact('periods'));
    }
    
    // Create new payroll period
    public function createPeriod(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'finance_head'], true)) {
            abort(403, 'Only the Finance Head can create a payroll period.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'period_type' => 'required|in:semi_monthly,monthly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'payment_date' => 'required|date',
        ]);

        if ($request->period_type === 'semi_monthly') {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $isFirstCutoff = $startDate->day === 1 && $endDate->day === 15;
            $isSecondCutoff = $startDate->day === 16 && $endDate->isLastOfMonth();

            if (!$isFirstCutoff && !$isSecondCutoff) {
                return back()->withInput()->withErrors([
                    'end_date' => 'Semi-monthly payroll must cover days 1-15 or 16 through the last day of the month.',
                ]);
            }
        }

        $branch = Branch::findOrFail($request->branch_id);
        $periodCode = 'PRD-' . Carbon::parse($request->start_date)->format('Ymd') . '-' . Carbon::parse($request->end_date)->format('Ymd') . '-' . $branch->branch_code;
        
        $payrollPeriod = PayrollPeriod::create([
            'name' => $request->name,
            'branch_id' => $branch->id,
            'period_code' => $periodCode,
            'period_type' => $request->period_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'cutoff_date' => $request->end_date,
            'payment_date' => $request->payment_date,
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);
        
        $createdCount = $this->payrollComputationService->processPayrollPeriodFromDTRs($payrollPeriod);

        return redirect()->route('admin.payroll.entries', $payrollPeriod->id)
            ->with('success', "Payroll period created and {$createdCount} approved DTR payroll entr" . ($createdCount === 1 ? 'y' : 'ies') . ' generated.');
    }
    
    // Process payroll
    public function process($periodId)
    {
        abort_unless($this->isFinanceHead(), 403, 'Only the Finance Head can send payroll to HR.');

        $period = PayrollPeriod::with(['approvedBy', 'hrApprovedBy', 'branchApprovedBy'])->findOrFail($periodId);
        if ($period->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved payroll periods can be submitted.');
        }
        
        $result = $this->payrollService->processPayroll($periodId);
        
        if ($result['success']) {
            User::query()
                ->where('role', 'admin')
                ->where(function ($query) {
                    $query->where('admin_type', 'super_admin')
                        ->orWhereNull('admin_type');
                })
                ->get()
                ->each(function (User $hrUser) use ($period) {
                    $hrUser->notify(new SystemNotification(
                        'Payroll submitted for HR review',
                        'Payroll ' . $period->period_code . ' is ready for HR review before branch submission.',
                        'payroll_hr_review',
                        route('admin.payroll.entries', $period->id)
                    ));
                });

            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }

    public function submitByHrToBranch($periodId)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403, 'Only HR can submit payroll to the Branch Head.');

        $period = PayrollPeriod::findOrFail($periodId);
        if ($period->status !== 'completed' || $period->branch_submitted_at
            || in_array($period->admin_approval_stage, ['bh_approved'], true)) {
            return back()->with('error', 'Payroll must be completed by the Finance Head and reviewed by HR first.');
        }

        $period->update([
            'admin_approval_stage' => 'hr_fd',
            'correction_stage' => null,
            'correction_reason' => null,
            'correction_returned_by' => null,
            'correction_returned_at' => null,
            'hr_approved_by' => $period->hr_approved_by ?: Auth::id(),
            'hr_approved_at' => $period->hr_approved_at ?: now(),
            'branch_submitted_at' => now(),
        ]);
        $period->entries()->where('correction_stage', 'hr_review')->update([
            'correction_stage' => null,
            'correction_reason' => null,
            'correction_returned_by' => null,
            'correction_returned_at' => null,
        ]);
        User::query()
            ->where('role', 'admin')
            ->where('admin_type', 'branch_admin')
            ->where('branch_id', $period->branch_id)
            ->get()
            ->each(function (User $branchAdmin) use ($period) {
                $branchAdmin->notify(new SystemNotification(
                    'Payroll submitted for branch review',
                    'Payroll ' . $period->period_code . ' was submitted for your branch.',
                    'payroll_branch_review',
                    route('admin.payroll.entries', $period->id)
                ));
            });

        return back()->with('success', 'Payroll submitted to the Branch Head for approval.');
    }
    
    // View payroll entries
    public function viewEntries($periodId)
    {
        $this->checkAdmin();
        
        $period = PayrollPeriod::with('approvedBy')->findOrFail($periodId);
        if (Auth::user()->role === 'finance_officer' && !$period->branch_approved_at) {
            return redirect()->route('admin.payroll.periods')
                ->with('error', 'Payroll is waiting for Branch Head approval before Finance Officer processing.');
        }
        if ((Auth::user()->isBranchAdmin() || Auth::user()->role === 'finance_officer')
            && Auth::user()->getEffectiveBranchId() !== $period->branch_id) {
            abort(403, 'You can only view payroll for your assigned branch.');
        }
        $entriesQuery = PayrollEntry::with(['employee', 'dtr'])->where('payroll_period_id', $periodId);
        if (Auth::user()->role === 'finance_officer') {
            $entriesQuery->whereNull('correction_stage');
        } elseif (Auth::user()->isBranchAdmin()) {
            $entriesQuery->where(function ($query) {
                $query->whereNull('correction_stage')
                    ->orWhereNotIn('correction_stage', ['hr_review', 'fh_correction']);
            });
        } elseif (Auth::user()->isSuperAdmin()) {
            $entriesQuery->where(function ($query) {
                $query->whereNull('correction_stage')
                    ->orWhere('correction_stage', '!=', 'fh_correction');
            });
        }
        $entries = $entriesQuery->get();

        foreach ($entries as $entry) {
            if (!$entry->dtr) {
                continue;
            }

            $calculation = $this->payrollComputationService->computePayrollForDTR($entry->dtr);
            $dtrBreakdown = $entry->dtr->getCalculationBreakdown();
            $entryBreakdown = is_string($entry->payroll_breakdown)
                ? (json_decode($entry->payroll_breakdown, true) ?? [])
                : (array) ($entry->payroll_breakdown ?? []);
            $dailyRate = (float) ($calculation['daily_rate'] ?? $entryBreakdown['daily_rate'] ?? 0);
            $leaveWithoutPayDeduction = (int) ($dtrBreakdown['leave_without_pay'] ?? 0) * $dailyRate;
            $earlyOutDeduction = (float) ($entryBreakdown['early_out_deduction'] ?? 0);

            $entry->days_present = (int) ($dtrBreakdown['days_present'] ?? 0);
            $entry->days_absent = (int) ($dtrBreakdown['days_absent'] ?? 0);
            $entry->overtime_hours = $calculation['overtime_hours'] ?? 0;
            $entry->overtime_pay = $calculation['overtime_pay'] ?? 0;
            $entry->absent_deduction = (float) ($calculation['absent_deduction'] ?? 0);
            $entry->gross_pay = (float) $entry->basic_pay + (float) $entry->overtime_pay;
            $entry->total_deductions = (float) $entry->sss_contribution
                + (float) $entry->philhealth_contribution
                + (float) $entry->pagibig_contribution
                + (float) $entry->withholding_tax
                + (float) ($entry->cash_advance_deduction ?? 0)
                + (float) ($entry->late_deduction ?? 0)
                + (float) $entry->absent_deduction
                + $leaveWithoutPayDeduction
                + $earlyOutDeduction;
            $entry->net_pay = (float) $entry->gross_pay - (float) $entry->total_deductions;
            $entryBreakdown['days_present'] = $entry->days_present;
            $entryBreakdown['days_absent'] = $entry->days_absent;
            $entryBreakdown['daily_rate'] = $dailyRate;
            $entryBreakdown['absent_deduction'] = number_format($entry->absent_deduction, 2, '.', '');
            $entryBreakdown['overtime_pay'] = number_format($entry->overtime_pay, 2, '.', '');
            $entryBreakdown['gross_pay'] = number_format($entry->gross_pay, 2, '.', '');
            $entryBreakdown['total_deductions'] = number_format($entry->total_deductions, 2, '.', '');
            $entryBreakdown['net_pay'] = number_format($entry->net_pay, 2, '.', '');
            $entry->payroll_breakdown = json_encode($entryBreakdown);
            $entry->save();
        }
        
        $summary = [
            'total_employees' => $entries->count(),
            'total_gross' => $entries->sum('gross_pay'),
            'total_deductions' => $entries->sum('total_deductions'),
            'total_net' => $entries->sum('net_pay'),
        ];
        
        return view('admin.payroll.entries', compact('period', 'entries', 'summary'));
    }

    public function downloadPayroll($periodId)
    {
        $this->checkAdmin();

        $period = PayrollPeriod::with(['approvedBy', 'hrApprovedBy', 'branchApprovedBy'])->findOrFail($periodId);
        if ((Auth::user()->isBranchAdmin() || Auth::user()->role === 'finance_officer')
            && Auth::user()->getEffectiveBranchId() !== $period->branch_id) {
            abort(403, 'You can only download payroll for your assigned branch.');
        }
        $entries = PayrollEntry::with(['employee', 'payrollPeriod.approvedBy'])
            ->where('payroll_period_id', $period->id)
            ->get();
        $approvalDate = $period->approved_at
            ?? $entries->where('status', 'approved')->max('updated_at');

        $hrApprovalDate = $period->hr_approved_at;
        $financeHeadProfile = $period->approvedBy?->getFinanceProfile();
        $financeHeadSignature = null;
        if ($financeHeadProfile?->signature_path && Storage::disk('public')->exists($financeHeadProfile->signature_path)) {
            $financeHeadSignature = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($financeHeadProfile->signature_path));
        }
        $hrProfile = $period->hrApprovedBy?->getAdminProfile();
        $hrSignature = null;
        if ($hrProfile?->signature_path && Storage::disk('public')->exists($hrProfile->signature_path)) {
            $hrSignature = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($hrProfile->signature_path));
        }
        $branchProfile = $period->branchApprovedBy?->getAdminProfile();
        $branchSignature = null;
        if ($branchProfile?->signature_path && Storage::disk('public')->exists($branchProfile->signature_path)) {
            $branchSignature = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($branchProfile->signature_path));
        }

        $pdf = Pdf::loadView('pdf.payroll-report', compact('period', 'entries', 'approvalDate', 'hrApprovalDate', 'financeHeadSignature', 'hrSignature', 'branchSignature'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('payroll-' . $period->period_code . '.pdf');
    }
    
    // Generate payslip PDF
    public function generatePayslip($entryId)
    {
        $entry = PayrollEntry::with(['employee', 'employeeProfile.branch', 'payrollPeriod', 'branch', 'dtr'])->findOrFail($entryId);

        $this->syncEntryFromCurrentDtr($entry);
        $breakdown = is_string($entry->payroll_breakdown)
            ? (json_decode($entry->payroll_breakdown, true) ?? [])
            : (array) ($entry->payroll_breakdown ?? []);

        $dtrStats = [
            'days_present' => (int) ($breakdown['days_present'] ?? $entry->days_present ?? 0),
            'days_absent' => (int) ($breakdown['days_absent'] ?? $entry->days_absent ?? 0),
            'late_minutes' => (int) ($breakdown['late_minutes'] ?? ($entry->dtr?->getTotalLateMinutes() ?? 0)),
            'early_out_minutes' => (int) ($breakdown['early_out_minutes'] ?? ($entry->dtr?->getTotalEarlyOutMinutes() ?? 0)),
            'paid_leave' => (int) ($breakdown['paid_leave'] ?? 0),
            'leave_without_pay' => (int) ($breakdown['leave_without_pay'] ?? 0),
        ];

        // Check authorization
        $user = Auth::user();
        if ($user->role === 'finance_officer' && $user->getEffectiveBranchId() !== $entry->payrollPeriod->branch_id) {
            abort(403, 'You can only generate payslips for your assigned branch.');
        }
        if ($user->role === 'employee' && ($entry->employee->user_id !== $user->id || !$entry->payslip_sent_at)) {
            abort(403);
        }

        $signatures = $this->getPayslipSignatures($entry, $user);
        $pdf = Pdf::loadView('pdf.payslip', array_merge(['entry' => $entry, 'dtrStats' => $dtrStats, 'logo' => $this->getPayslipLogo()], $signatures));

        return $pdf->download('payslip-' . $entry->employee->employee_number . '-' . $entry->payrollPeriod->period_code . '.pdf');
    }

    public function submitPayslip($entryId)
    {
        abort_unless($this->isFinanceOfficer(), 403, 'Only the Finance Officer can send payroll and payslips to employees.');

        $entry = PayrollEntry::with(['employeeProfile.user', 'employee', 'payrollPeriod', 'branch', 'dtr'])->findOrFail($entryId);
        if (!$entry->payrollPeriod?->finance_submitted_at) {
            return back()->with('error', 'Payroll must be submitted by the Branch Head to Finance first.');
        }
        if ($entry->status !== 'approved') {
            return back()->with('error', 'Only approved payslips can be submitted.');
        }

        $recipient = $entry->employeeProfile?->user?->email;
        if (!$recipient) {
            return back()->with('error', 'This employee does not have an email address.');
        }

        $this->syncEntryFromCurrentDtr($entry);
        $signatures = $this->getPayslipSignatures($entry, Auth::user());
        $pdfContents = Pdf::loadView('pdf.payslip', array_merge(['entry' => $entry, 'logo' => $this->getPayslipLogo()], $signatures))->output();
        Mail::to($recipient)->send(new PayrollProcessed($entry, $pdfContents));

        $entry->forceFill([
            'payslip_sent_at' => now(),
            'payslip_sent_to' => $recipient,
        ])->save();

        $entry->employeeProfile?->user?->notify(new SystemNotification(
            'Payroll and payslip sent',
            'Your payroll and payslip for ' . $entry->payrollPeriod->period_code . ' were sent by Finance.',
            'payroll_processed',
            route('employee.payslips')
        ));

        return back()->with('success', 'Payslip submitted successfully to ' . $recipient . '.');
    }
    
    // View single payslip
    public function viewPayslip($entryId)
    {
        $entry = PayrollEntry::with(['employee', 'employeeProfile.branch', 'payrollPeriod', 'branch', 'dtr'])->findOrFail($entryId);

        $this->syncEntryFromCurrentDtr($entry);
        $breakdown = is_string($entry->payroll_breakdown)
            ? (json_decode($entry->payroll_breakdown, true) ?? [])
            : (array) ($entry->payroll_breakdown ?? []);

        $dtrStats = [
            'days_present' => (int) ($breakdown['days_present'] ?? $entry->days_present ?? 0),
            'days_absent' => (int) ($breakdown['days_absent'] ?? $entry->days_absent ?? 0),
            'late_minutes' => (int) ($breakdown['late_minutes'] ?? ($entry->dtr?->getTotalLateMinutes() ?? 0)),
            'early_out_minutes' => (int) ($breakdown['early_out_minutes'] ?? ($entry->dtr?->getTotalEarlyOutMinutes() ?? 0)),
            'paid_leave' => (int) ($breakdown['paid_leave'] ?? 0),
            'leave_without_pay' => (int) ($breakdown['leave_without_pay'] ?? 0),
        ];

        $user = Auth::user();
        if ($user->role === 'employee' && ($entry->employee->user_id !== $user->id || !$entry->payslip_sent_at)) {
            abort(403);
        }

        $signatures = $this->getPayslipSignatures($entry, $user);

        return view('admin.payroll.payslip', array_merge(compact('entry', 'dtrStats'), $signatures));
    }

    protected function getPayslipSignatures(PayrollEntry $entry, User $actor): array
    {
        $period = $entry->payrollPeriod;
        $financeOfficer = $actor->role === 'finance_officer'
            ? $actor
            : User::where('role', 'finance_officer')
                ->where('branch_id', $period?->branch_id)
                ->first();
        $profiles = [
            'employeeSignature' => $entry->employeeProfile,
            'financeOfficerSignature' => $financeOfficer?->getFinanceProfile(),
            'branchHeadSignature' => $period?->branchApprovedBy?->getAdminProfile(),
        ];

        return collect($profiles)->mapWithKeys(function ($profile, $key) {
            $path = $profile?->signature_path;
            $value = $path && Storage::disk('public')->exists($path)
                ? 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($path))
                : null;

            return [$key => $value];
        })->all();
    }

    protected function getPayslipLogo(): ?string
    {
        $path = public_path('images/logo.jpg');

        return is_file($path)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($path))
            : null;
    }

    protected function syncEntryFromCurrentDtr(PayrollEntry $entry): array
    {
        $dtr = $entry->dtr;

        if ($dtr) {
            $dtr->calculateTotals();
            $dtr->save();

            $breakdown = $dtr->getCalculationBreakdown();
            $daysPresent = (int) ($breakdown['days_present'] ?? $dtr->getDaysPresent());
            $daysAbsent = (int) ($breakdown['days_absent'] ?? $dtr->getDaysAbsent());
            $paidLeave = (int) ($breakdown['paid_leave'] ?? 0);
            $leaveWithoutPay = (int) ($breakdown['leave_without_pay'] ?? 0);
            $lateMinutes = (int) $dtr->getTotalLateMinutes();
            $earlyOutMinutes = (int) $dtr->getTotalEarlyOutMinutes();

            $serializedBreakdown = is_string($entry->payroll_breakdown)
                ? json_decode($entry->payroll_breakdown, true) ?? []
                : (array) ($entry->payroll_breakdown ?? []);

            $serializedBreakdown['days_present'] = $daysPresent;
            $serializedBreakdown['days_absent'] = $daysAbsent;
            $serializedBreakdown['paid_leave'] = $paidLeave;
            $serializedBreakdown['leave_without_pay'] = $leaveWithoutPay;
            $serializedBreakdown['late_minutes'] = $lateMinutes;
            $serializedBreakdown['early_out_minutes'] = $earlyOutMinutes;

            $entry->days_present = $daysPresent;
            $entry->days_absent = $daysAbsent;
            $entry->payroll_breakdown = json_encode($serializedBreakdown);
            $entry->save();

            return $serializedBreakdown;
        }

        return [
            'days_present' => (int) ($entry->days_present ?? 0),
            'days_absent' => (int) ($entry->days_absent ?? 0),
            'paid_leave' => 0,
            'leave_without_pay' => 0,
            'late_minutes' => 0,
            'early_out_minutes' => 0,
        ];
    }

    // Edit payroll entry for finance review
    public function editEntry($entryId)
    {
        $this->checkAdmin();

        $entry = PayrollEntry::with(['employee', 'payrollPeriod', 'branch', 'dtr'])->findOrFail($entryId);
        $breakdown = $this->syncEntryFromCurrentDtr($entry);
        $dtr = $entry->dtr;

        $dtrStats = [
            'days_present' => $breakdown['days_present'] ?? 0,
            'days_absent' => $breakdown['days_absent'] ?? 0,
            'late_minutes' => $dtr ? $dtr->getTotalLateMinutes() : 0,
            'early_out_minutes' => $dtr ? $dtr->getTotalEarlyOutMinutes() : 0,
            'paid_leave' => $breakdown['paid_leave'] ?? 0,
            'leave_without_pay' => $breakdown['leave_without_pay'] ?? 0,
        ];

        if ($dtr) {
            $entry->days_present = $dtrStats['days_present'];
            $entry->days_absent = $dtrStats['days_absent'];
        }

        $isReadOnly = $entry->payrollPeriod?->status !== 'draft'
            && !($entry->correction_stage === 'fh_correction' && $this->isFinanceCorrectionOwner())
            && !$this->isFinanceHead();

        $isFinanceHead = $this->isFinanceHead();
        return view('admin.payroll.edit-entry', compact('entry', 'dtrStats', 'isReadOnly', 'isFinanceHead'));
    }

    // Update payroll entry with deductions and status
    public function updateEntry(Request $request, $entryId)
    {
        $this->checkAdmin();

        $entry = PayrollEntry::with(['employee', 'payrollPeriod', 'branch', 'dtr'])->findOrFail($entryId);
        $canCorrectReturnedEntry = $entry->correction_stage === 'fh_correction' && $this->isFinanceCorrectionOwner();
        $isFinanceHead = $this->isFinanceHead();
        if ($entry->payrollPeriod?->status !== 'draft' && !$canCorrectReturnedEntry && !$isFinanceHead) {
            return redirect()->route('admin.payroll.entries', $entry->payroll_period_id)
                ->with('error', 'Approved payroll entries are view-only. Return the payroll to Draft before editing.');
        }
        $dtrBreakdown = $this->syncEntryFromCurrentDtr($entry);
        $dtr = $entry->dtr;
        $dtrStats = [
            'days_present' => $dtrBreakdown['days_present'] ?? 0,
            'days_absent' => $dtrBreakdown['days_absent'] ?? 0,
            'late_minutes' => $dtr ? $dtr->getTotalLateMinutes() : 0,
            'early_out_minutes' => $dtr ? $dtr->getTotalEarlyOutMinutes() : 0,
            'paid_leave' => $dtrBreakdown['paid_leave'] ?? 0,
            'leave_without_pay' => $dtrBreakdown['leave_without_pay'] ?? 0,
        ];

        $validated = $request->validate([
            'basic_pay' => 'nullable|numeric|min:0',
            'days_present' => 'nullable|integer|min:0',
            'days_absent' => 'nullable|integer|min:0',
            'paid_leave' => 'nullable|integer|min:0',
            'leave_without_pay' => 'nullable|integer|min:0',
            'late_minutes' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'absent_deduction' => 'nullable|numeric|min:0',
            'early_out_deduction' => 'nullable|numeric|min:0',
            'leave_without_pay_amount' => 'nullable|numeric|min:0',
            'sss_contribution' => 'required|numeric|min:0',
            'philhealth_contribution' => 'required|numeric|min:0',
            'pagibig_contribution' => 'required|numeric|min:0',
            'late_deduction' => 'nullable|numeric|min:0',
            'withholding_tax' => 'required|numeric|min:0',
            'cash_advance_deduction' => 'nullable|numeric|min:0',
            'status' => 'required|in:calculated,approved',
        ]);

        $lateMinutes = (float) ($validated['late_minutes'] ?? ($dtrStats['late_minutes'] ?? 0));
        $earlyOutMinutes = (float) ($dtrStats['early_out_minutes'] ?? 0);
        $computedLateDeduction = $lateMinutes * 0.48;
        $computedEarlyOutDeduction = 0;
        $breakdown = is_string($entry->payroll_breakdown) ? json_decode($entry->payroll_breakdown, true) ?? [] : (array) $entry->payroll_breakdown;
        $workingDays = max(1, $dtr?->getWorkingDays() ?? 0);
        $canonicalDailyRate = $entry->basic_pay > 0 ? (float) $entry->basic_pay / $workingDays : 0;
        $storedDailyRate = isset($breakdown['daily_rate']) && $entry->status === 'approved'
            ? (float) $breakdown['daily_rate']
            : $canonicalDailyRate;
        $storedCashAdvance = (float) ($entry->cash_advance_deduction ?? 0) > 0
            ? (float) $entry->cash_advance_deduction
            : (float) ($breakdown['cash_advance_deduction'] ?? 0);
        $isApprovedEntry = $entry->status === 'approved' && $entry->payrollPeriod?->status === 'approved';
        if ($isApprovedEntry && $validated['status'] === 'approved') {
            $validated['daily_rate'] = $storedDailyRate;
            $validated['days_present'] = $entry->days_present;
            $validated['days_absent'] = $entry->days_absent;
            $validated['paid_leave'] = $breakdown['paid_leave'] ?? 0;
            $validated['leave_without_pay'] = $breakdown['leave_without_pay'] ?? 0;
            $validated['sss_contribution'] = $entry->sss_contribution;
            $validated['philhealth_contribution'] = $entry->philhealth_contribution;
            $validated['pagibig_contribution'] = $entry->pagibig_contribution;
            $validated['withholding_tax'] = $entry->withholding_tax;
            $validated['cash_advance_deduction'] = $storedCashAdvance;
        }
        $dailyRate = $isFinanceHead && array_key_exists('daily_rate', $validated)
            ? (float) $validated['daily_rate']
            : ($validated['status'] === 'approved'
            ? $storedDailyRate
            : (float) ($validated['daily_rate'] ?? $storedDailyRate));
        $computedEarlyOutDeduction = $dailyRate > 0
            ? min($dailyRate, $dailyRate * ($earlyOutMinutes / (8 * 60)))
            : 0;
        // Attendance totals are always authoritative; do not reuse submitted or stale payroll values.
        $paidLeaveDays = (int) ($dtrStats['paid_leave'] ?? 0);
        $leaveWithoutPayDays = (int) ($dtrStats['leave_without_pay'] ?? 0);
        $leaveWithoutPayDeduction = $leaveWithoutPayDays * $dailyRate;
        $entry->days_present = (int) ($dtrStats['days_present'] ?? 0);
        $entry->days_absent = (int) ($dtrStats['days_absent'] ?? 0);
        $entry->sss_contribution = $validated['sss_contribution'];
        $entry->philhealth_contribution = $validated['philhealth_contribution'];
        $entry->pagibig_contribution = $validated['pagibig_contribution'];
        $entry->cash_advance_deduction = $validated['cash_advance_deduction'] ?? ($entry->cash_advance_deduction ?? 0);
        $entry->late_deduction = $isFinanceHead && array_key_exists('late_deduction', $validated)
            ? (float) $validated['late_deduction']
            : (isset($validated['late_deduction']) && $validated['late_deduction'] !== '' ? (float) $validated['late_deduction'] : $computedLateDeduction);
        $entry->absent_deduction = $isFinanceHead && array_key_exists('absent_deduction', $validated)
            ? (float) $validated['absent_deduction']
            : (float) $entry->days_absent * $dailyRate;
        $entry->withholding_tax = $validated['withholding_tax'];
        $validatedEarlyOutDeduction = $isFinanceHead && array_key_exists('early_out_deduction', $validated)
            ? (float) $validated['early_out_deduction']
            : $computedEarlyOutDeduction;
        $leaveWithoutPayDeduction = $isFinanceHead && array_key_exists('leave_without_pay_amount', $validated)
            ? (float) $validated['leave_without_pay_amount']
            : $leaveWithoutPayDeduction;

        $entry->basic_pay = $isFinanceHead && array_key_exists('basic_pay', $validated)
            ? (float) $validated['basic_pay']
            : (float) ($entry->basic_pay ?: ($entry->employeeProfile?->basic_salary ?? 0));

        $breakdown['days_present'] = $entry->days_present;
        $breakdown['days_absent'] = $entry->days_absent;
        $breakdown['paid_leave'] = $paidLeaveDays;
        $breakdown['leave_without_pay'] = $leaveWithoutPayDays;
        $breakdown['late_minutes'] = $validated['late_minutes'] ?? ($breakdown['late_minutes'] ?? 0);
        $breakdown['cash_advance_deduction'] = $entry->cash_advance_deduction ?? 0;
        $breakdown['daily_rate'] = $dailyRate;
        $breakdown['total_daily_rate'] = ($entry->days_present ?? 0) * $dailyRate;
        $breakdown['early_out_deduction'] = $validatedEarlyOutDeduction;
        $entry->payroll_breakdown = json_encode($breakdown);

        $entry->gross_pay = $entry->basic_pay + ($entry->overtime_pay ?? 0);
        $entry->total_deductions = $entry->sss_contribution + $entry->philhealth_contribution + $entry->pagibig_contribution + $entry->withholding_tax + ($entry->cash_advance_deduction ?? 0) + $entry->late_deduction + $entry->absent_deduction + $leaveWithoutPayDeduction + $validatedEarlyOutDeduction;
        $entry->net_pay = $entry->gross_pay - $entry->total_deductions;
        $entry->status = $validated['status'];
        $entry->save();

        if ($canCorrectReturnedEntry) {
            $entry->update([
                'correction_stage' => null,
                'correction_reason' => null,
                'correction_returned_by' => null,
                'correction_returned_at' => null,
                'payslip_sent_at' => null,
                'payslip_sent_to' => null,
            ]);
        }

        return redirect()->route('admin.payroll.entries', $entry->payroll_period_id)
            ->with('success', 'Payroll entry updated successfully.');
    }

    public function returnPeriodToBranchHead(Request $request, $periodId)
    {
        abort_unless($this->isFinanceOfficer(), 403);

        $validated = $request->validate(['reason' => 'required|string|max:2000']);
        $period = PayrollPeriod::findOrFail($periodId);
        $this->markPeriodForCorrection($period, 'bh_review', $validated['reason']);
        $this->notifyCorrectionRecipients($period, 'bh_review', 'All payroll and payslips were returned to BH for review.');

        return back()->with('success', 'All payroll and payslips were returned to the Branch Head for review.');
    }

    public function returnEntriesToBranchHead(Request $request, $periodId)
    {
        abort_unless($this->isFinanceOfficer(), 403);

        $validated = $request->validate([
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'integer|exists:payroll_entries,id',
            'reason' => 'required|string|max:2000',
        ]);
        $period = PayrollPeriod::findOrFail($periodId);
        $this->markEntriesForCorrection($period, $validated['entry_ids'], 'bh_review', $validated['reason']);
        $this->notifyCorrectionRecipients($period, 'bh_review', 'Selected payroll entries were returned to BH for review.');

        return back()->with('success', 'The selected payslips were returned to the Branch Head for review.');
    }

    public function returnPeriodToHr(Request $request, $periodId)
    {
        abort_unless(Auth::user()->isBranchAdmin(), 403);

        $validated = $request->validate(['reason' => 'required|string|max:2000']);
        $period = PayrollPeriod::findOrFail($periodId);
        $this->ensureBranchPeriod($period);
        $this->markPeriodForCorrection($period, 'hr_review', $validated['reason']);
        $this->notifyCorrectionRecipients($period, 'hr_review', 'All payroll and payslips were returned to HR for review.');

        return back()->with('success', 'All payroll and payslips were returned to HR for review.');
    }

    public function returnEntriesToHr(Request $request, $periodId)
    {
        abort_unless(Auth::user()->isBranchAdmin(), 403);

        $validated = $request->validate([
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'integer|exists:payroll_entries,id',
            'reason' => 'required|string|max:2000',
        ]);
        $period = PayrollPeriod::findOrFail($periodId);
        $this->ensureBranchPeriod($period);
        $this->markEntriesForCorrection($period, $validated['entry_ids'], 'hr_review', $validated['reason']);
        $this->notifyCorrectionRecipients($period, 'hr_review', 'Selected payroll entries were returned to HR for review.');

        return back()->with('success', 'The selected payslips were returned to HR for review.');
    }

    public function returnPeriodToFinanceHead(Request $request, $periodId)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $validated = $request->validate(['reason' => 'required|string|max:2000']);
        $period = PayrollPeriod::findOrFail($periodId);
        $this->markPeriodForCorrection($period, 'fh_correction', $validated['reason']);
        $this->notifyCorrectionRecipients($period, 'fh_correction', 'All payroll and payslips were returned to FH for computation correction.');

        return back()->with('success', 'All payroll and payslips were returned to the Finance Head for correction.');
    }

    public function returnEntriesToFinanceHead(Request $request, $periodId)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'integer|exists:payroll_entries,id',
            'reason' => 'required|string|max:2000',
        ]);
        $period = PayrollPeriod::findOrFail($periodId);
        $this->markEntriesForCorrection($period, $validated['entry_ids'], 'fh_correction', $validated['reason']);
        $this->notifyCorrectionRecipients($period, 'fh_correction', 'Selected payroll entries were returned to FH for computation correction.');

        return back()->with('success', 'The selected payslips were returned to the Finance Head for correction.');
    }

    public function resubmitCorrectedPeriod(Request $request, $periodId)
    {
        abort_unless($this->isFinanceHead(), 403);

        $validated = $request->validate(['reason' => 'nullable|string|max:2000']);
        $period = PayrollPeriod::findOrFail($periodId);
        $period->update([
            'correction_stage' => 'hr_review',
            'correction_reason' => $validated['reason'] ?? 'Correction completed by Finance Head.',
            'correction_returned_by' => Auth::id(),
            'correction_returned_at' => now(),
        ]);
        $period->entries()->where('correction_stage', 'fh_correction')->update([
            'correction_stage' => 'hr_review',
            'correction_reason' => $validated['reason'] ?? 'Correction completed by Finance Head.',
            'correction_returned_by' => Auth::id(),
            'correction_returned_at' => now(),
        ]);
        $this->notifyCorrectionRecipients($period, 'hr_review', 'Corrected payroll is ready for HR review.');

        return back()->with('success', 'Corrected payroll was sent back to HR for review.');
    }

    private function markPeriodForCorrection(PayrollPeriod $period, string $stage, string $reason): void
    {
        $period->update([
            'correction_stage' => $stage,
            'correction_reason' => $reason,
            'correction_returned_by' => Auth::id(),
            'correction_returned_at' => now(),
            'branch_submitted_at' => in_array($stage, ['bh_review', 'hr_review'], true) ? null : $period->branch_submitted_at,
            'branch_approved_by' => null,
            'branch_approved_at' => null,
            'finance_submitted_by' => null,
            'finance_submitted_at' => null,
            'admin_approval_stage' => $stage === 'hr_review' ? 'hr_fd' : ($stage === 'fh_correction' ? null : $period->admin_approval_stage),
        ]);
        $period->entries()->update([
            'correction_stage' => $stage,
            'correction_reason' => $reason,
            'correction_returned_by' => Auth::id(),
            'correction_returned_at' => now(),
            'payslip_sent_at' => null,
            'payslip_sent_to' => null,
        ]);
    }

    private function markEntriesForCorrection(PayrollPeriod $period, array $entryIds, string $stage, string $reason): void
    {
        $period->entries()->whereIn('id', $entryIds)->update([
            'correction_stage' => $stage,
            'correction_reason' => $reason,
            'correction_returned_by' => Auth::id(),
            'correction_returned_at' => now(),
            'payslip_sent_at' => null,
            'payslip_sent_to' => null,
        ]);
        $period->update([
            'correction_stage' => $stage,
            'correction_reason' => $reason,
            'correction_returned_by' => Auth::id(),
            'correction_returned_at' => now(),
        ]);
    }

    private function ensureBranchPeriod(PayrollPeriod $period): void
    {
        if (Auth::user()->getEffectiveBranchId() !== $period->branch_id) {
            abort(403, 'You can only review payroll for your assigned branch.');
        }
    }

    private function isFinanceCorrectionOwner(): bool
    {
        return $this->isFinanceHead();
    }

    private function isFinanceOfficer(): bool
    {
        return Auth::user()->role === 'finance_officer';
    }

    private function isFinanceHead(): bool
    {
        return Auth::user()->role === 'finance_head';
    }

    private function notifyCorrectionRecipients(PayrollPeriod $period, string $stage, string $message): void
    {
        $url = route('admin.payroll.entries', $period->id);
        $recipients = User::where('role', 'admin')
            ->where('admin_type', 'branch_admin')
            ->where('branch_id', $period->branch_id)
            ->get();

        if ($stage === 'bh_review') {
            $recipients = $recipients->merge(User::where('role', 'admin')
                ->where('admin_type', 'branch_admin')
                ->where('branch_id', $period->branch_id)
                ->get());
        } elseif ($stage === 'hr_review') {
            $recipients = $recipients->merge(User::where('role', 'admin')
                ->where(function ($query) {
                    $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
                })->get());
        } elseif ($stage === 'fh_correction') {
            $recipients = $recipients->merge(User::where('role', 'finance_head')->get());
        }

        $recipients->unique('id')->each(fn (User $recipient) => $recipient->notify(new SystemNotification(
            'Payroll correction required',
            $message . ' Payroll: ' . $period->period_code,
            'payroll_correction_' . $stage,
            $url
        )));
    }

    // Approve payroll
    public function approve($periodId)
    {
        $this->checkAdmin();
        
        $period = PayrollPeriod::findOrFail($periodId);
        if (in_array($period->status, ['approved', 'rejected'], true)) {
            return redirect()->back()->with('error', 'This payroll period has already been finalized.');
        }

        $period->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        $period->entries()->update(['status' => 'approved']);
        
        return redirect()->back()->with('success', 'Payroll approved successfully');
    }

    // Return approved payroll to draft for re-checking
    public function recheck($periodId)
    {
        $this->checkAdmin();

        $period = PayrollPeriod::findOrFail($periodId);
        $period->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'admin_approval_stage' => null,
            'hr_approved_by' => null,
            'hr_approved_at' => null,
            'branch_submitted_at' => null,
            'branch_approved_by' => null,
            'branch_approved_at' => null,
            'finance_submitted_by' => null,
            'finance_submitted_at' => null,
        ]);
        $period->entries()->update(['status' => 'calculated']);

        return redirect()->back()->with('success', 'Payroll returned to Draft for re-checking.');
    }

    // Return finalized payroll to Draft (System Administrator only)
    public function returnToDraft($periodId)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only the System Administrator can return payroll to Draft.');
        }

        $period = PayrollPeriod::findOrFail($periodId);
        if (!in_array($period->status, ['approved', 'completed'], true)) {
            return redirect()->back()->with('error', 'Only approved or completed payroll periods can be returned.');
        }
        if ($period->branch_submitted_at) {
            return redirect()->back()->with('error', 'This payroll has already been submitted to the Branch Head and cannot be returned.');
        }

        $period->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'admin_approval_stage' => null,
            'hr_approved_by' => null,
            'hr_approved_at' => null,
            'branch_submitted_at' => null,
            'branch_approved_by' => null,
            'branch_approved_at' => null,
            'finance_submitted_by' => null,
            'finance_submitted_at' => null,
        ]);
        $period->entries()->update(['status' => 'calculated']);

        return redirect()->back()->with('success', 'Payroll returned to Draft successfully.');
    }

    // Toggle the System Administrator approval stage.
    public function toggleAdminApprovalStage($periodId)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only the System Administrator can change this approval stage.');
        }

        $period = PayrollPeriod::findOrFail($periodId);
        if (!in_array($period->status, ['approved', 'completed'], true)) {
            return redirect()->back()->with('error', 'Only submitted payroll periods can change approval stage.');
        }

        $isHrApproval = $period->admin_approval_stage !== 'hr_fd';
        $period->update([
            'admin_approval_stage' => $isHrApproval ? 'hr_fd' : 'fd',
            'hr_approved_by' => $isHrApproval ? Auth::id() : null,
            'hr_approved_at' => $isHrApproval ? now() : null,
            'branch_submitted_at' => null,
            'branch_approved_by' => null,
            'branch_approved_at' => null,
            'finance_submitted_by' => null,
            'finance_submitted_at' => null,
        ]);

        return redirect()->back()->with('success', 'Payroll approval stage updated successfully.');
    }

    public function approveByBranchHead($periodId)
    {
        $user = Auth::user();
        if (!$user->isBranchAdmin()) {
            abort(403, 'Only the Branch Head can approve this payroll.');
        }

        $period = PayrollPeriod::findOrFail($periodId);
        if ($user->getEffectiveBranchId() !== $period->branch_id) {
            abort(403, 'You can only approve payroll for your assigned branch.');
        }
        if ($period->status !== 'completed' || $period->admin_approval_stage !== 'hr_fd' || !$period->branch_submitted_at) {
            return redirect()->back()->with('error', 'Payroll must be approved by HR and submitted to the Branch Head first.');
        }

        $period->update([
            'branch_approved_by' => $user->id,
            'branch_approved_at' => now(),
            'admin_approval_stage' => 'bh_approved',
        ]);

        return redirect()->back()->with('success', 'Payroll approved by Branch Head. Submit it to the Finance Officer when ready.');
    }

    public function submitByBranchToFinance($periodId)
    {
        $user = Auth::user();
        if (!$user->isBranchAdmin()) {
            abort(403, 'Only the Branch Head can submit this payroll to Finance.');
        }

        $period = PayrollPeriod::findOrFail($periodId);
        if ($user->getEffectiveBranchId() !== $period->branch_id) {
            abort(403, 'You can only submit payroll for your assigned branch.');
        }
        if ($period->status !== 'completed' || $period->admin_approval_stage !== 'bh_approved' || !$period->branch_approved_at) {
            return redirect()->back()->with('error', 'Payroll must be approved by the Branch Head before Finance submission.');
        }

        $period->update([
            'finance_submitted_by' => $user->id,
            'finance_submitted_at' => now(),
        ]);

        User::query()
            ->where('role', 'finance_officer')
            ->where('branch_id', $period->branch_id)
            ->get()
            ->each(function (User $financeOfficer) use ($period) {
                $financeOfficer->notify(new SystemNotification(
                    'Payroll submitted by Branch Head',
                    'Payroll ' . $period->period_code . ' is ready for Finance Officer payslip processing for all employees.',
                    'payroll_fo_review',
                    route('admin.payroll.entries', $period->id)
                ));
            });

        return redirect()->back()->with('success', 'Payroll submitted to the Finance Officer for payslip processing.');
    }
    
    // Employee gets their payslips
    public function myPayslips()
    {
        $user = Auth::user();

        $payslips = PayrollEntry::with('payrollPeriod')
            ->whereHas('employeeProfile', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereIn('status', ['calculated', 'approved'])
            ->whereNotNull('payslip_sent_at')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('employee.payslips', compact('payslips'));
    }
}
