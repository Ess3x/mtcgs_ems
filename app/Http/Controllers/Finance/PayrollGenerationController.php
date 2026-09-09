<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\DTR;
use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Services\PayrollComputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollGenerationController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollComputationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Show DTRs ready for payroll generation
     */
    public function readyDTRs()
    {
        $user = Auth::user();

        // Check authorization - Finance Officer or Admin
        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $approvedDTRs = DTR::where('status', 'approved')
            ->whereDoesntHave('payrollEntry')
            ->with('employeeProfile')
            ->whereHas('employeeProfile.user', function($q) {
                $q->where('is_active', true);
            });

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $ownEmployeeId = $financeProfile?->employee_profile_id;

            if ($user->isFinanceHead() && $financeProfile?->branch_id) {
                $approvedDTRs->whereHas('employeeProfile', function ($query) use ($financeProfile) {
                    $query->where('branch_id', $financeProfile->branch_id);
                });
            } elseif (!$ownEmployeeId) {
                $approvedDTRs->whereRaw('0 = 1');
            } else {
                $approvedDTRs->where('employee_profile_id', $ownEmployeeId);
            }
        }

        $approvedDTRs = $approvedDTRs
            ->orderBy('period_end', 'desc')
            ->paginate(20);

        // Compute payroll preview for each approved DTR
        $dtrCalculations = [];
        foreach ($approvedDTRs as $dtr) {
            $dtrCalculations[$dtr->id] = $this->payrollService->computePayrollForDTR($dtr);
        }

        // Get available payroll periods
        $payrollPeriods = PayrollPeriod::where('status', '!=', 'closed')
            ->orderBy('end_date', 'desc')
            ->get();

        return view('finance.payroll-generation.ready-dtrs', compact('approvedDTRs', 'payrollPeriods', 'dtrCalculations'));
    }

    public function reviewDTR($dtrId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $dtr = DTR::with('employeeProfile')->findOrFail($dtrId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            if (!$financeProfile || $dtr->employee_profile_id !== $financeProfile->employee_profile_id) {
                return redirect('/dashboard')->with('error', 'You can only review your own DTR records.');
            }
        }

        $computation = $this->payrollService->computePayrollForDTR($dtr);

        return view('finance.payroll-generation.review-dtr', compact('dtr', 'computation'));
    }

    /**
     * Generate payroll entry from a specific DTR
     */
    public function generateFromDTR(Request $request, $dtrId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $dtr = DTR::findOrFail($dtrId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $isOutsideBranch = $financeProfile && $dtr->employeeProfile?->branch_id !== $financeProfile->branch_id;
            if (!$financeProfile || (!$user->isFinanceHead() && $dtr->employee_profile_id !== $financeProfile->employee_profile_id) || $isOutsideBranch) {
                return redirect('/dashboard')->with('error', 'You can only generate payroll from DTRs in your assigned branch.');
            }
        }

        // Check if DTR is approved
        if ($dtr->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved DTRs can be converted to payroll');
        }

        // Validate payroll period
        $payrollPeriod = PayrollPeriod::findOrFail($request->payroll_period_id);

        try {
            // Generate payroll entry
            $payrollEntry = $this->payrollService->generatePayrollFromDTR($dtr, $payrollPeriod);

            return redirect()->route('finance.payroll.entry', $payrollEntry->id)
                ->with('success', 'Payroll entry generated from DTR successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    /**
     * Generate payroll for entire period from approved DTRs
     */
    public function generateForPeriod(Request $request, $periodId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $payrollPeriod = PayrollPeriod::findOrFail($periodId);

        try {
            // Process all approved DTRs for this period
            $createdCount = $this->payrollService->processPayrollPeriodFromDTRs($payrollPeriod);

            if ($createdCount === 0) {
                return redirect()->route('admin.payroll.entries', ['periodId' => $periodId])
                    ->with('error', 'No approved DTRs match this payroll period and branch. Please select the correct branch or period.');
            }

            return redirect()->route('admin.payroll.entries', ['periodId' => $periodId])
                ->with('success', "Generated {$createdCount} payroll entries from approved DTRs");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate payroll entries: ' . $e->getMessage());
        }
    }

    /**
     * View and edit payroll entry before finalization
     */
    public function viewEntry($entryId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $payrollEntry = PayrollEntry::findOrFail($entryId);

        if ($user->isFinanceOfficer()) {
            $financeProfile = $user->getFinanceProfile();
            $isOutsideBranch = $financeProfile && $payrollEntry->employeeProfile?->branch_id !== $financeProfile->branch_id;
            if (!$financeProfile || (!$user->isFinanceHead() && $payrollEntry->employee_profile_id !== $financeProfile->employee_profile_id) || $isOutsideBranch) {
                return redirect('/dashboard')->with('error', 'You can only view payroll entries in your assigned branch.');
            }
        }

        // Parse breakdown JSON
        $breakdown = $payrollEntry->payroll_breakdown ? json_decode($payrollEntry->payroll_breakdown, true) : [];

        return view('finance.payroll-generation.view-entry', compact('payrollEntry', 'breakdown'));
    }

    /**
     * Update payroll entry (adjust deductions if needed)
     */
    public function updateEntry(Request $request, $entryId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $payrollEntry = PayrollEntry::findOrFail($entryId);

        // Only allow updates in draft status
        if ($payrollEntry->status !== 'draft') {
            return redirect()->back()->with('error', 'Can only edit draft payroll entries');
        }

        $validated = $request->validate([
            'sss_contribution' => 'nullable|numeric|min:0',
            'philhealth_contribution' => 'nullable|numeric|min:0',
            'pagibig_contribution' => 'nullable|numeric|min:0',
            'withholding_tax' => 'nullable|numeric|min:0',
            'cash_advance_deduction' => 'nullable|numeric|min:0',
        ]);

        // Update values
        $payrollEntry->sss_contribution = $validated['sss_contribution'] ?? $payrollEntry->sss_contribution;
        $payrollEntry->philhealth_contribution = $validated['philhealth_contribution'] ?? $payrollEntry->philhealth_contribution;
        $payrollEntry->pagibig_contribution = $validated['pagibig_contribution'] ?? $payrollEntry->pagibig_contribution;
        $payrollEntry->withholding_tax = $validated['withholding_tax'] ?? $payrollEntry->withholding_tax;
        $payrollEntry->cash_advance_deduction = $validated['cash_advance_deduction'] ?? ($payrollEntry->cash_advance_deduction ?? 0);

        // Recalculate gross and net using the DTR-based payroll values only
        $payrollEntry->gross_pay = $payrollEntry->basic_pay + $payrollEntry->overtime_pay;

        $payrollEntry->total_deductions = $payrollEntry->sss_contribution + $payrollEntry->philhealth_contribution
                                          + $payrollEntry->pagibig_contribution + $payrollEntry->withholding_tax
                                          + ($payrollEntry->cash_advance_deduction ?? 0)
                                          + $payrollEntry->late_deduction + $payrollEntry->absent_deduction;

        $payrollEntry->net_pay = $payrollEntry->gross_pay - $payrollEntry->total_deductions;

        $payrollEntry->save();

        return redirect()->back()->with('success', 'Payroll entry updated successfully');
    }

    /**
     * Finalize payroll entry (convert to processed)
     */
    public function finalizeEntry($entryId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $payrollEntry = PayrollEntry::findOrFail($entryId);

        if ($payrollEntry->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft entries can be finalized');
        }

        $payrollEntry->update(['status' => 'processed']);

        return redirect()->back()->with('success', 'Payroll entry finalized and ready for approval');
    }

    /**
     * View payroll summary before submission
     */
    public function summaryByPeriod($periodId)
    {
        $user = Auth::user();

        if (!$user->isFinanceOfficer() && !$user->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access');
        }

        $payrollPeriod = PayrollPeriod::findOrFail($periodId);

        $payrollEntries = PayrollEntry::where('payroll_period_id', $periodId)
            ->with('employeeProfile')
            ->orderBy('status')
            ->get();

        $stats = [
            'total_employees' => $payrollEntries->count(),
            'draft_count' => $payrollEntries->where('status', 'draft')->count(),
            'processed_count' => $payrollEntries->where('status', 'processed')->count(),
            'approved_count' => $payrollEntries->where('status', 'approved')->count(),
            'total_gross' => $payrollEntries->sum('gross_pay'),
            'total_deductions' => $payrollEntries->sum('total_deductions'),
            'total_net' => $payrollEntries->sum('net_pay'),
            'total_sss' => $payrollEntries->sum('sss_contribution'),
            'total_philhealth' => $payrollEntries->sum('philhealth_contribution'),
            'total_pagibig' => $payrollEntries->sum('pagibig_contribution'),
            'total_tax' => $payrollEntries->sum('withholding_tax'),
        ];

        return view('finance.payroll-generation.summary', compact('payrollPeriod', 'payrollEntries', 'stats'));
    }
}
