<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\Branch;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    private function authorizeReports(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'finance_officer', 'finance_head'], true), 403);
    }

    private function getBranchFilter()
    {
        $user = Auth::user();
        if ($user->isFinanceOfficer()) {
            $profile = $user->getFinanceProfile();
            return $profile->branch_id ?? null;
        }
        return null;
    }
    
    public function monthlyReport(Request $request)
    {
        $this->authorizeReports();
        $branchId = $this->getBranchFilter();
        
        $entries = PayrollEntry::with('employeeProfile')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereMonth('created_at', $request->month ?? now()->month)
            ->whereYear('created_at', $request->year ?? now()->year)
            ->get();
        
        // Generate PDF
        $pdf = Pdf::loadView('reports.monthly-payroll', [
            'entries' => $entries,
            'month' => $request->month ?? now()->month,
            'year' => $request->year ?? now()->year,
            'branchName' => $branchId ? (Branch::find($branchId)->branch_name ?? 'Branch') : 'All Branches'
        ]);
        
        return $pdf->download('payroll-report-' . ($request->year ?? now()->year) . '-' . ($request->month ?? now()->month) . '.pdf');
    }
    
    public function contributionReport(Request $request)
    {
        $this->authorizeReports();
        $branchId = $this->getBranchFilter();
        
        $entries = PayrollEntry::with('employeeProfile')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereMonth('created_at', $request->month ?? now()->month)
            ->whereYear('created_at', $request->year ?? now()->year)
            ->get();
        
        $pdf = Pdf::loadView('reports.contributions', [
            'entries' => $entries,
            'month' => $request->month ?? now()->month,
            'year' => $request->year ?? now()->year,
            'branchName' => $branchId ? (Branch::find($branchId)->branch_name ?? 'Branch') : 'All Branches'
        ]);
        
        return $pdf->download('contributions-report-' . ($request->year ?? now()->year) . '-' . ($request->month ?? now()->month) . '.pdf');
    }
    
    public function taxReport(Request $request)
    {
        $this->authorizeReports();
        $branchId = $this->getBranchFilter();
        
        $entries = PayrollEntry::with('employeeProfile')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereMonth('created_at', $request->month ?? now()->month)
            ->whereYear('created_at', $request->year ?? now()->year)
            ->get();
        
        $pdf = Pdf::loadView('reports.tax', [
            'entries' => $entries,
            'month' => $request->month ?? now()->month,
            'year' => $request->year ?? now()->year,
            'branchName' => $branchId ? (Branch::find($branchId)->branch_name ?? 'Branch') : 'All Branches'
        ]);
        
        return $pdf->download('tax-report-' . ($request->year ?? now()->year) . '-' . ($request->month ?? now()->month) . '.pdf');
    }

    public function bulkPayslips($periodId)
    {
        $this->authorizeReports();
        $period = PayrollPeriod::findOrFail($periodId);
        $entries = PayrollEntry::with(['employeeProfile', 'payrollPeriod', 'branch'])
            ->where('payroll_period_id', $period->id)
            ->when($this->getBranchFilter(), fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->get();

        return Pdf::loadView('pdf.bulk-payslips', compact('entries'))->download('bulk-payslips-' . $period->period_code . '.pdf');
    }

    public function viewPayslip($entryId)
    {
        $this->authorizeReports();
        $entry = PayrollEntry::with(['employeeProfile', 'payrollPeriod', 'branch'])->findOrFail($entryId);
        return view('admin.payroll.payslip', compact('entry'));
    }

    public function birAnnual(Request $request)
    {
        $this->authorizeReports();
        $year = (int) ($request->year ?? now()->year);
        $branchId = $this->getBranchFilter();
        $entries = PayrollEntry::with('employeeProfile')
            ->whereHas('payrollPeriod', fn ($q) => $q->whereYear('payment_date', $year))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('employee_profile_id');

        $pdf = Pdf::loadView('reports.bir-annual', compact('entries', 'year'));
        return $pdf->download('bir-annual-compensation-' . $year . '.pdf');
    }
}
