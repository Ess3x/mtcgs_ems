<?php

namespace App\Http\Controllers;

use App\Models\CashAdvanceApplication;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashAdvanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $scope = request()->query('scope');
        $isApplicantView = $user->role === 'employee'
            || ($scope === 'my' && ($user->role === 'finance_officer'
                || ($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin')));
        abort_unless(!$isApplicantView || $user->canApplyForCashAdvance(), 403, 'Cash advance is only available to eligible employees.');

        $query = CashAdvanceApplication::with('employeeProfile')->latest();

        if ($user->role === 'employee'
            || ($user->role === 'finance_officer' && $scope === 'my')
            || ($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin' && $scope === 'my')) {
            $profile = $user->role === 'employee'
                ? $user->getEmployeeProfile()
                : ($user->role === 'finance_officer'
                    ? $user->getFinanceProfile()?->employeeProfile
                    : $user->getAdminProfile()?->employeeProfile);
            abort_unless($profile, 403);
            $query->where('employee_profile_id', $profile->id);
        } elseif ($user->role === 'finance_officer') {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('branch_id', $user->getEffectiveBranchId()))
                ->where('status', 'pending_fo');
        } elseif ($user->isBranchAdmin()) {
            $query->whereHas('employeeProfile', fn ($q) => $q->where('branch_id', $user->getEffectiveBranchId()))
                ->whereIn('status', ['pending_fo', 'pending_bh']);
        } elseif ($user->isSuperAdmin()) {
            $query->where('status', 'pending_hr');
        } elseif ($user->isFinanceHead()) {
            $query->where('status', 'pending_fh');
        } else {
            abort(403);
        }

        $applications = $query->paginate(15);
        $cashAdvanceAvailableByEmployee = [];
        $applicationEmployeeIds = $applications->getCollection()->pluck('employee_profile_id')->filter()->unique();
        $activeCashAdvancesByEmployee = CashAdvanceApplication::whereIn('employee_profile_id', $applicationEmployeeIds)
            ->whereIn('status', ['approved', 'deducting'])
            ->get()
            ->groupBy('employee_profile_id');

        foreach ($applicationEmployeeIds as $applicationEmployeeId) {
            $activeBalance = $activeCashAdvancesByEmployee->get($applicationEmployeeId, collect())->sum(function ($activeCashAdvance) {
                $amount = (float) ($activeCashAdvance->approved_amount ?: $activeCashAdvance->requested_amount);
                $installment = (float) ($activeCashAdvance->installment_amount ?: ($amount / max(1, $activeCashAdvance->installments)));

                return max(0, $amount - ($installment * (int) $activeCashAdvance->deducted_installments));
            });
            $cashAdvanceAvailableByEmployee[$applicationEmployeeId] = max(0, 1000 - $activeBalance);
        }

        $profile = ($user->role === 'employee'
            || ($user->role === 'finance_officer' && $scope === 'my')
            || ($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin' && $scope === 'my'))
            ? ($user->role === 'employee'
                ? $user->getEmployeeProfile()
                : ($user->role === 'finance_officer'
                    ? $user->getFinanceProfile()?->employeeProfile
                    : $user->getAdminProfile()?->employeeProfile))
            : null;
        $remainingCashAdvanceBalance = 0;
        $totalCashAdvances = 0;

        if ($profile) {
            $activeCashAdvances = CashAdvanceApplication::where('employee_profile_id', $profile->id)
                ->whereIn('status', ['approved', 'deducting'])
                ->get();

            $remainingCashAdvanceBalance = $activeCashAdvances->sum(function ($activeCashAdvance) {
                $amount = (float) ($activeCashAdvance->approved_amount ?: $activeCashAdvance->requested_amount);
                $installment = (float) ($activeCashAdvance->installment_amount ?: ($amount / max(1, $activeCashAdvance->installments)));

                return max(0, $amount - ($installment * (int) $activeCashAdvance->deducted_installments));
            });
            $totalCashAdvances = $activeCashAdvances->sum(fn ($activeCashAdvance) =>
                (float) ($activeCashAdvance->approved_amount ?: $activeCashAdvance->requested_amount)
            );
        }

        $availableCashAdvanceBalance = max(0, 1000 - $remainingCashAdvanceBalance);

        return view('cash-advances.index', compact('applications', 'profile', 'totalCashAdvances', 'remainingCashAdvanceBalance', 'availableCashAdvanceBalance', 'cashAdvanceAvailableByEmployee'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isBranchAdminApplicant = $user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin';
        abort_unless(in_array($user->role, ['employee', 'finance_officer'], true) || $isBranchAdminApplicant, 403);
        $profile = $user->role === 'employee'
            ? $user->getEmployeeProfile()
            : ($user->role === 'finance_officer'
                ? $user->getFinanceProfile()?->employeeProfile
                : $user->getAdminProfile()?->employeeProfile);
        abort_unless($profile, 403);
        abort_unless($isBranchAdminApplicant || $user->canApplyForCashAdvance(), 403, 'Cash advance is only available to eligible employees.');

        $validated = $request->validate([
            'requested_amount' => 'required|numeric|min:1|max:1000',
            'purpose' => 'required|string|max:2000',
        ]);

        abort_unless(Auth::user()->is_active, 403, 'Your account is inactive.');
        $employmentStatus = strtolower((string) $profile->status);
        abort_if(in_array($employmentStatus, ['inactive', 'resigned', 'terminated'], true), 422, 'Only active employees may apply.');

        $activeCashAdvances = CashAdvanceApplication::where('employee_profile_id', $profile->id)
            ->whereIn('status', ['approved', 'deducting'])
            ->get();

        $remainingBalance = $activeCashAdvances->sum(function ($activeCashAdvance) {
            $amount = (float) ($activeCashAdvance->approved_amount ?: $activeCashAdvance->requested_amount);
            $installment = (float) ($activeCashAdvance->installment_amount ?: ($amount / max(1, $activeCashAdvance->installments)));

            return max(0, $amount - ($installment * (int) $activeCashAdvance->deducted_installments));
        });

        $availableApplicationBalance = max(0, 1000 - $remainingBalance);
        abort_if((float) $validated['requested_amount'] > $availableApplicationBalance, 422, 'The requested amount exceeds your available cash advance balance of ₱' . number_format($availableApplicationBalance, 2) . '.');

        $installments = 2;

        $monthsOfService = $profile->date_hired ? $profile->date_hired->diffInMonths(now()) : 0;
        $category = $monthsOfService < 3
            ? 'new_hire_bh_fh_required'
            : ($monthsOfService < 12 ? 'regular' : 'long_service');

        $initialStatus = $isBranchAdminApplicant
            ? 'pending_hr'
            : ($user->role === 'finance_officer' ? 'pending_bh' : 'pending_fo');
        $application = CashAdvanceApplication::create([
            'employee_profile_id' => $profile->id,
            'requested_amount' => $validated['requested_amount'],
            'installments' => $installments,
            'installment_amount' => round($validated['requested_amount'] / $installments, 2),
            'purpose' => $validated['purpose'],
            'eligibility_category' => $category,
            'status' => $initialStatus,
        ]);

        if ($user->role === 'finance_officer') {
            $this->notifyRole('admin', $profile->branch_id, 'Cash advance application for Branch Admin review', $profile, $application, 'pending_bh');
        } elseif ($isBranchAdminApplicant) {
            $this->notifyRole('hr', null, 'Cash advance application for Super Admin review', $profile, $application, 'pending_hr');
        } else {
            $this->notifyRole('finance_officer', $profile->branch_id, 'Cash advance application for FO review', $profile, $application, 'pending_fo');
        }

        $message = $isBranchAdminApplicant
            ? 'Cash advance application submitted to the Super Admin.'
            : ($user->role === 'finance_officer'
                ? 'Cash advance application submitted to the Branch Admin.'
                : 'Cash advance application submitted to the Finance Officer.');

        return back()->with('success', $message);
    }

    public function reviewByFo(Request $request, CashAdvanceApplication $application)
    {
        $this->authorizeBranchFinance($application, 'finance_officer');
        $validated = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'nullable|string|max:2000']);
        $this->transition($application, $validated['decision'] === 'approve' ? 'pending_bh' : 'rejected', 'fo_reviewed_by', 'fo_reviewed_at', $validated['reason']);
        if ($validated['decision'] === 'approve') {
            $this->notifyRole('admin', $application->employeeProfile->branch_id, 'Cash advance ready for BH review', $application->employeeProfile, $application, 'pending_bh');
        } else {
            $this->notifyEmployee($application, 'Cash advance application rejected by FO.');
        }
        return back()->with('success', 'Finance Officer review saved.');
    }

    public function reviewByBh(Request $request, CashAdvanceApplication $application)
    {
        $this->authorizeBranchFinance($application, 'branch_admin');
        $validated = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'nullable|string|max:2000']);
        $this->transition($application, $validated['decision'] === 'approve' ? 'pending_hr' : 'rejected', 'bh_reviewed_by', 'bh_reviewed_at', $validated['reason']);
        if ($validated['decision'] === 'approve') {
            $this->notifyRole('hr', null, 'Cash advance ready for HR approval', $application->employeeProfile, $application, 'pending_hr');
        } else {
            $this->notifyEmployee($application, 'Cash advance application rejected by BH.');
        }
        return back()->with('success', 'Branch Head review saved.');
    }

    public function reviewByHr(Request $request, CashAdvanceApplication $application)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_unless($application->status === 'pending_hr', 422);
        $validated = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'nullable|string|max:2000']);
        $this->transition($application, $validated['decision'] === 'approve' ? 'pending_fh' : 'rejected', 'hr_reviewed_by', 'hr_reviewed_at', $validated['reason']);
        if ($validated['decision'] === 'approve') {
            $this->notifyRole('finance_head', null, 'Cash advance ready for FH final approval', $application->employeeProfile, $application, 'pending_fh');
        } else {
            $this->notifyEmployee($application, 'Cash advance application rejected by HR.');
        }
        return back()->with('success', 'HR approval saved.');
    }

    public function reviewByFh(Request $request, CashAdvanceApplication $application)
    {
        abort_unless(Auth::user()->isFinanceHead(), 403);
        abort_unless($application->status === 'pending_fh', 422);
        $validated = $request->validate([
            'decision' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:2000',
        ]);
        if ($validated['decision'] === 'approve') {
            $amount = $application->requested_amount;
            $installments = $application->installments;
            $application->update([
                'approved_amount' => $amount,
                'installment_amount' => round($amount / $installments, 2),
            ]);
        }
        $this->transition($application, $validated['decision'] === 'approve' ? 'approved' : 'rejected', 'fh_reviewed_by', 'fh_reviewed_at', $validated['reason']);
        if ($validated['decision'] === 'approve') {
            $this->notifyEmployee($application, 'Cash advance approved by FH. It is now available for payroll deduction.');
        } else {
            $this->notifyEmployee($application, 'Cash advance application rejected by FH.');
        }
        return back()->with('success', 'Finance Head decision saved.');
    }

    private function transition(CashAdvanceApplication $application, string $status, string $userField, string $dateField, ?string $reason): void
    {
        $application->update([
            'status' => $status,
            $userField => Auth::id(),
            $dateField => now(),
            'rejection_reason' => $status === 'rejected' ? ($reason ?: 'Rejected during approval review.') : null,
        ]);
    }

    private function authorizeBranchFinance(CashAdvanceApplication $application, string $role): void
    {
        $user = Auth::user();
        $authorized = $role === 'branch_admin'
            ? $user->isBranchAdmin()
            : $user->role === $role;
        abort_unless($authorized, 403);
        abort_unless($application->employeeProfile?->branch_id === $user->getEffectiveBranchId(), 403);
        $expected = $role === 'finance_officer' ? 'pending_fo' : 'pending_bh';
        abort_unless($application->status === $expected, 422);
    }

    private function notifyRole(string $role, ?int $branchId, string $title, EmployeeProfile $profile, CashAdvanceApplication $application, string $type): void
    {
        $users = User::query();
        if ($role === 'hr') {
            $users->where('role', 'admin')->where(function ($query) {
                $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
            });
        } else {
            $users->where('role', $role);
        }
        $users = $users->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $users->each(fn (User $user) => $user->notify(new SystemNotification($title, 'Cash advance for ' . $profile->first_name . ' ' . $profile->last_name . ' requires action.', 'cash_advance_' . $type, route('cash-advances.index'))));
    }

    private function notifyEmployee(CashAdvanceApplication $application, string $message): void
    {
        $application->employeeProfile?->user?->notify(new SystemNotification('Cash advance update', $message, 'cash_advance_update', route('cash-advances.index')));
    }
}
