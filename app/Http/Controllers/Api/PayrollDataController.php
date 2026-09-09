<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollDataController extends Controller
{
    public function getPayrollData(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'admin' && !in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        }
        
        $periodQuery = PayrollPeriod::orderBy('created_at', 'desc');
        if ($user->isBranchAdmin() || $user->role === 'finance_officer') {
            $periodQuery->where('branch_id', $user->getEffectiveBranchId());
        }
        $periods = $periodQuery->get();
        $isSystemAdmin = $user->isSuperAdmin();
        
        $periodsData = $periods->map(function($period) use ($user, $isSystemAdmin) {
            return [
                'id' => $period->id,
                'name' => $period->name ?? $period->period_code,
                'branch_name' => $period->branch?->branch_name ?? 'N/A',
                'period_code' => $period->period_code,
                'start_date' => date('M d, Y', strtotime($period->start_date)),
                'end_date' => date('M d, Y', strtotime($period->end_date)),
                'payment_date' => date('M d, Y', strtotime($period->payment_date)),
                'status' => $period->status,
                'status_label' => $isSystemAdmin && in_array($period->status, ['approved', 'completed'], true)
                    ? ($period->admin_approval_stage === 'bh_approved' ? 'Approved BH' : ($period->admin_approval_stage === 'hr_fd' ? 'Approved HR/FD' : 'Approved FD'))
                    : ucfirst($period->status),
                'admin_approval_stage' => $period->admin_approval_stage ?: 'fd',
                'can_approve' => $isSystemAdmin || $user->role === 'finance_head',
                'can_process' => $user->role === 'finance_head' && $period->status === 'approved',
                'can_return' => $isSystemAdmin && !$period->branch_submitted_at,
                'can_submit_to_branch' => $isSystemAdmin
                    && $period->status === 'completed'
                    && in_array($period->admin_approval_stage, [null, '', 'fd', 'hr_fd'], true)
                    && !$period->branch_submitted_at,
                'can_branch_approve' => $user->isBranchAdmin()
                    && $period->status === 'completed'
                    && $period->admin_approval_stage === 'hr_fd'
                    && (bool) $period->branch_submitted_at
                    && !$period->branch_approved_at,
                'can_submit_to_finance' => $user->isBranchAdmin()
                    && $period->status === 'completed'
                    && (bool) $period->branch_approved_at
                    && !$period->finance_submitted_at,
                'can_generate_payslip' => $user->role === 'finance_officer' && (bool) $period->finance_submitted_at,
                'can_return_to_bh' => $user->role === 'finance_officer'
                    && (bool) $period->finance_submitted_at
                    && !$period->correction_stage,
                'can_review_bh_return' => $user->isBranchAdmin()
                    && $user->getEffectiveBranchId() === $period->branch_id
                    && $period->correction_stage === 'bh_review',
                'can_review_hr_return' => $isSystemAdmin
                    && $period->correction_stage === 'hr_review',
                'correction_stage' => $period->correction_stage,
                'approval_mode' => ($isSystemAdmin || $user->role === 'finance_head') ? 'switch' : 'button',
                'entries_url' => route('admin.payroll.entries', $period->id),
                'process_url' => route('admin.payroll.process', $period->id),
                'submit_to_branch_url' => route('admin.payroll.submit-to-branch', $period->id),
                'approve_url' => route('admin.payroll.approve', $period->id),
                'recheck_url' => route('admin.payroll.recheck', $period->id),
                'return_url' => route('admin.payroll.return', $period->id),
                'admin_approval_stage_url' => route('admin.payroll.admin-approval-stage', $period->id),
                'branch_approve_url' => route('admin.payroll.branch-approve', $period->id),
                'branch_submit_finance_url' => route('admin.payroll.branch-submit-finance', $period->id),
                'download_url' => route('admin.payroll.download', $period->id),
            ];
        });
        
        return response()->json([
            'success' => true,
            'total_periods' => $periods->count(),
            'completed_periods' => $periods->where('status', 'completed')->count(),
            'processing_periods' => $periods->where('status', 'processing')->count(),
            'draft_periods' => $periods->where('status', 'draft')->count(),
            'periods' => $periodsData,
        ]);
    }
}
