<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\CalendarEvent;
use App\Models\CashCharge;
use App\Models\EmployeeProfile;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin' || $user->role === 'branch_head') {
            if ($user->role === 'admin' && $user->admin_type === 'super_admin') {
                // Super admin sees all leave requests from ACTIVE employees only
                $leaves = LeaveRequest::with('employeeProfile')
                    ->whereHas('employeeProfile.user', function($q) {
                        $q->where('is_active', true);
                    })
                    ->orderByRaw("CASE WHEN status IN ('pending', 'pending_system_admin') THEN 0 ELSE 1 END")
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->paginate(20);
            } elseif (($user->role === 'admin' && $user->admin_type === 'branch_admin') || $user->role === 'branch_head') {
                // Branch admin/Branch Head only sees leave requests from their branch ACTIVE employees
                $adminProfile = $user->role === 'branch_head' ? $user->getBranchHeadProfile() : $user->getAdminProfile();
                $branchId = $adminProfile->branch_id ?? null;
                $leaves = LeaveRequest::with('employeeProfile')
                    ->whereHas('employeeProfile', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->whereHas('employeeProfile.user', function($q) {
                        $q->where('is_active', true);
                    })
                    ->orderByRaw("CASE WHEN status IN ('pending', 'pending_system_admin') THEN 0 ELSE 1 END")
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->paginate(20);
            } else {
                // Fallback - show all for regular admins
                $leaves = LeaveRequest::with('employeeProfile')
                    ->whereHas('employeeProfile.user', function($q) {
                        $q->where('is_active', true);
                    })
                    ->latest()->paginate(20);
            }
        } elseif ($user->role === 'finance_head') {
            $profile = $user->getFinanceProfile();
            $branchId = $profile->branch_id ?? null;
            $leaves = LeaveRequest::with('employeeProfile')
                ->when($branchId, function ($query) use ($branchId) {
                    $query->whereHas('employeeProfile', function ($profileQuery) use ($branchId) {
                        $profileQuery->where('branch_id', $branchId);
                    });
                })
                ->whereHas('employeeProfile.user', function($q) {
                    $q->where('is_active', true);
                })
                ->latest()->paginate(20);
        } elseif ($user->role === 'finance_officer') {
            $leaves = LeaveRequest::with('employeeProfile')
                ->where('employee_id', $user->id)
                ->latest()->paginate(20);
        } else {
            $profile = $user->getEmployeeProfile();
            if (!$profile) {
                return redirect('/dashboard')->with('error', 'Profile not found');
            }
            $leaves = LeaveRequest::with('employeeProfile')
                ->where('employee_profile_id', $profile->id)
                ->latest()->paginate(20);
        }
        
        return view('leave.index', compact('leaves'));
    }
    
    public function create()
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['employee', 'finance_officer']) && !$this->isBranchHead($user)) {
            return redirect('/leave')->with('error', 'Only employees, finance officers, and Branch Heads can file leave');
        }
        
        $profile = $this->getLeaveProfile($user);
        if (!$profile) {
            return redirect('/leave')->with('error', 'Unable to locate your leave profile.');
        }
        
        $leaveBalance = $this->getLeaveBalance($profile);

        $events = CalendarEvent::query();

        if ($profile->branch_id) {
            $events->where(function ($query) use ($profile) {
                $query->where('branch_id', $profile->branch_id)
                      ->orWhereNull('branch_id');
            });
        }

        $calendarEvents = $this->appendConfiguredHolidays($events->orderBy('event_date')->get());
        $activeLeaves = LeaveRequest::where('employee_profile_id', $profile->id)
            ->whereIn('status', ['pending', 'pending_system_admin', 'approved'])
            ->orderBy('start_date')
            ->get();
        $approvedLeaves = $activeLeaves->where('status', 'approved')->values();

        return view('leave.create', compact('leaveBalance', 'calendarEvents', 'activeLeaves', 'approvedLeaves', 'profile'));
    }

    private function appendConfiguredHolidays($events)
    {
        $events = collect($events);
        $now = Carbon::now(config('app.timezone'));
        $years = [$now->year, $now->copy()->addYear()->year];
        $existingDates = $events->where('event_type', 'holiday')
            ->map(fn ($event) => $event->event_date->toDateString())
            ->all();

        foreach ($years as $year) {
            foreach (config('philippine_holidays.recurring', []) as $monthDay) {
                $date = Carbon::createFromFormat('Y-m-d', $year . '-' . $monthDay, config('app.timezone'));
                if (in_array($date->toDateString(), $existingDates, true)) {
                    continue;
                }

                $details = config('philippine_holidays.recurring_details.' . $monthDay, []);
                $events->push(new CalendarEvent([
                    'title' => $details['title'] ?? 'Philippine Holiday',
                    'description' => $details['description'] ?? 'National holiday in the Philippines.',
                    'event_date' => $date,
                    'event_type' => 'holiday',
                    'branch_id' => null,
                ]));
                $existingDates[] = $date->toDateString();
            }

            foreach (config('philippine_holidays.dates.' . $year, []) as $dateString) {
                if (in_array($dateString, $existingDates, true)) {
                    continue;
                }

                $details = config('philippine_holidays.date_details.' . $dateString, []);
                $events->push(new CalendarEvent([
                    'title' => $details['title'] ?? 'Philippine Holiday',
                    'description' => $details['description'] ?? 'National holiday in the Philippines.',
                    'event_date' => Carbon::parse($dateString, config('app.timezone')),
                    'event_type' => 'holiday',
                    'branch_id' => null,
                ]));
                $existingDates[] = $dateString;
            }
        }

        return $events->sortBy('event_date')->values();
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['employee', 'finance_officer']) && !$this->isBranchHead($user)) {
            return redirect('/leave')->with('error', 'Only employees, finance officers, and Branch Heads can file leave');
        }
        
        $request->validate([
            'leave_type' => 'required|in:sick,vacation,emergency,birthday,maternity,paternity,service_incentive',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|min:5',
        ]);
        
        $profile = $this->getLeaveProfile($user);
        if (!$profile) {
            return redirect('/leave')->with('error', 'Unable to locate your leave profile.');
        }
        
        $start = \Carbon\Carbon::parse($request->start_date);
        $end = \Carbon\Carbon::parse($request->end_date);

        $hasOverlappingLeave = LeaveRequest::where('employee_profile_id', $profile->id)
            ->whereIn('status', ['pending', 'pending_system_admin', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($hasOverlappingLeave) {
            return back()->withInput()->withErrors([
                'start_date' => 'You already have a pending or approved leave request overlapping these dates.',
            ]);
        }

        $requestedDays = $start->diffInDays($end) + 1;
        $days = app(\App\Services\WorkingDayService::class)->countWorkingDays(
            $start,
            $end,
            $profile->branch_id
        );
        
        // Check leave balance
        $leaveBalance = $this->getLeaveBalance($profile);
        
        $available = 0;
        if ($request->leave_type == 'sick') {
            $available = $leaveBalance->getAvailableSickLeave();
        } elseif ($request->leave_type == 'vacation') {
            $available = $leaveBalance->getAvailableVacationLeave();
        } elseif ($request->leave_type == 'emergency') {
            $available = $leaveBalance->getAvailableEmergencyLeave();
        } elseif ($request->leave_type == 'birthday') {
            $available = $leaveBalance->getAvailableBirthdayLeave();
        } elseif ($request->leave_type == 'maternity') {
            $available = $leaveBalance->getAvailableMaternityLeave();
        } elseif ($request->leave_type == 'paternity') {
            $available = $leaveBalance->getAvailablePaternityLeave();
        } elseif ($request->leave_type == 'service_incentive') {
            $available = $leaveBalance->getAvailableServiceIncentiveLeave();
        }
        
        $isLeaveWithoutPay = $this->isNewHire($profile)
            || $days > $available
            || ($available <= 0 && $requestedDays > 0);

        // Leave requests may still be filed after the balance is exhausted; the unpaid status is
        // reflected in DTR totals instead of rejecting the request outright.
        LeaveRequest::create([
            'employee_id' => $profile->user_id,
            'employee_profile_id' => $profile->id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
            'is_absent' => $isLeaveWithoutPay,
        ]);

        $this->notifyBranchReviewers($profile, $request->leave_type);
        
        return redirect()->route('leave.index')->with('success', 'Leave request submitted');
    }
    
    protected function getLeaveProfile($user)
    {
        if ($user->role === 'employee') {
            return $user->getEmployeeProfile();
        }

        if (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            $financeProfile = $user->getFinanceProfile();
            if (!$financeProfile) {
                return null;
            }

            $profile = $financeProfile->employeeProfile;
            if (!$profile) {
                $profile = EmployeeProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'branch_id' => $financeProfile->branch_id,
                        'employee_number' => $financeProfile->employee_number ?: 'FIN-' . $user->id,
                        'first_name' => $financeProfile->first_name,
                        'last_name' => $financeProfile->last_name,
                        'position' => $financeProfile->position ?: 'Finance Officer',
                        'date_hired' => $financeProfile->date_hired ?: now()->toDateString(),
                        'status' => $financeProfile->status ?: 'New Hire',
                        'basic_salary' => $financeProfile->basic_salary ?? 0,
                    ]
                );
                $financeProfile->update(['employee_profile_id' => $profile->id]);
            }

            $syncDateHired = $financeProfile->date_hired ?: $profile->date_hired;
            $syncData = [
                'first_name' => $financeProfile->first_name,
                'last_name' => $financeProfile->last_name,
                'position' => $financeProfile->position ?: $profile->position,
                'status' => $financeProfile->status ?: $profile->status ?: 'New Hire',
                'date_hired' => $syncDateHired,
                'branch_id' => $financeProfile->branch_id ?? $profile->branch_id,
            ];

            $profileDateHired = $profile->date_hired;
            if ($profileDateHired instanceof \DateTimeInterface) {
                $profileDateHired = $profileDateHired->format('Y-m-d');
            }

            $syncDateHiredFormatted = $syncDateHired;
            if ($syncDateHiredFormatted instanceof \DateTimeInterface) {
                $syncDateHiredFormatted = $syncDateHiredFormatted->format('Y-m-d');
            }

            if ($profile->first_name !== $syncData['first_name'] || $profile->last_name !== $syncData['last_name'] || $profile->position !== $syncData['position'] || $profile->status !== $syncData['status'] || (string) $profileDateHired !== (string) $syncDateHiredFormatted || $profile->branch_id !== $syncData['branch_id']) {
                $profile->update($syncData);
            }

            return $profile->fresh();
        }

        if ($this->isBranchHead($user)) {
            $adminProfile = $user->getAdminProfile();
            $profile = $adminProfile?->employeeProfile;

            if (!$profile) {
                $profile = EmployeeProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'branch_id' => $adminProfile->branch_id,
                        'employee_number' => $adminProfile->employee_number ?: 'BH-' . $user->id,
                        'first_name' => $adminProfile->first_name,
                        'last_name' => $adminProfile->last_name,
                        'position' => $adminProfile->position,
                        'date_hired' => $adminProfile->date_hired ?: now()->toDateString(),
                        'status' => 'Branch Head',
                        'basic_salary' => $adminProfile->basic_salary ?? 0,
                    ]
                );
                $adminProfile->update(['employee_profile_id' => $profile->id]);
            }

            return $profile;
        }

        return null;
    }

    public function refreshLeaveBalanceForProfile($profile): LeaveBalance
    {
        $entitlements = $this->getLeaveEntitlements($profile);

        $leaveBalance = LeaveBalance::firstOrCreate(
            [
                'employee_profile_id' => $profile->id,
                'year' => date('Y'),
            ],
            array_merge($entitlements, [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
                'service_incentive_leave_used' => 0,
            ])
        );

        if ($leaveBalance->wasRecentlyCreated) {
            $leaveBalance->fill($entitlements);
            $leaveBalance->save();
        }

        return $leaveBalance->fresh();
    }

    protected function getLeaveBalance($profile)
    {
        return $this->refreshLeaveBalanceForProfile($profile);
    }

    protected function getLeaveEntitlements($profile)
    {
        $status = $this->normalizeEmploymentStatus($profile->status ?? 'New Hire');

        $credits = match ($status) {
            'Branch Head' => 10,
            'New Hire' => 0,
            'Regular' => 3,
            '1 Year of Service', '1-2 Years in Service' => 6,
            '3+ Years of Service', '3+ Year of Service' => 7,
            default => 0,
        };

        $hasParentalLeave = in_array($status, [
            'Branch Head',
            '3+ Years of Service',
            '3+ Year of Service',
        ], true);
        $parentalCredits = $status === 'Branch Head' ? 10 : 7;

        return [
            'sick_leave_total' => $credits,
            'vacation_leave_total' => $credits,
            'emergency_leave_total' => $credits,
            'birthday_leave_total' => 1,
            'cash_charge_total' => 0,
            'maternity_leave_total' => $hasParentalLeave ? $parentalCredits : 0,
            'paternity_leave_total' => $hasParentalLeave ? $parentalCredits : 0,
            'service_incentive_leave_total' => 5,
        ];
    }

    public function manageCredits()
    {
        $this->ensureSuperAdminAccess();

        $employees = EmployeeProfile::with(['user', 'branch'])
            ->whereHas('user', function ($userQuery) {
                $userQuery->where('is_active', true)
                    ->where(function ($roleQuery) {
                        $roleQuery->where('role', 'employee')
                            ->orWhere(function ($adminQuery) {
                                $adminQuery->where('role', 'admin')
                                    ->where('admin_type', 'branch_admin');
                            })
                            ->orWhereIn('role', ['finance_officer', 'finance_head'])
                            ->orWhere('role', 'branch_head');
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        foreach ($employees as $employee) {
            $employee->leaveBalance = LeaveBalance::firstOrCreate(
                [
                    'employee_profile_id' => $employee->id,
                    'year' => now()->year,
                ],
                array_merge($this->getLeaveEntitlements($employee), [
                    'sick_leave_used' => 0,
                    'vacation_leave_used' => 0,
                    'emergency_leave_used' => 0,
                    'birthday_leave_used' => 0,
                    'cash_charge_used' => 0,
                    'maternity_leave_used' => 0,
                    'paternity_leave_used' => 0,
                    'service_incentive_leave_used' => 0,
                ])
            );
        }

        $groupedEmployees = $employees
            ->groupBy(fn ($employee) => $employee->branch?->branch_name ?? 'Unassigned')
            ->map(function ($branchEmployees) {
                return $branchEmployees->groupBy(function ($employee) {
                    $user = $employee->user;

                    if ($user && in_array($user->role, ['finance_officer', 'finance_head'], true)) {
                        return 'Finance Officer';
                    }

                    if ($user && $user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin') {
                        return 'Branch Admin';
                    }

                    return 'Employee';
                });
            });

        return view('admin.leave-credits', compact('employees', 'groupedEmployees'));
    }

    private function attachRemainingCashChargeBalances($charges)
    {
        $approvedChargeIds = $charges
            ->where('status', 'approved')
            ->pluck('id');

        $deductionsByCharge = $approvedChargeIds->isEmpty()
            ? collect()
            : PayrollEntry::query()
                ->selectRaw('cash_charge_id, SUM(cash_charge_deduction) as total_deducted')
                ->whereIn('cash_charge_id', $approvedChargeIds)
                ->where('status', 'approved')
                ->whereHas('payrollPeriod', function ($query) {
                    $query->where('status', 'completed')
                        ->whereNotNull('approved_at')
                        ->whereNotNull('hr_approved_at')
                        ->whereNotNull('branch_approved_at')
                        ->where('admin_approval_stage', 'bh_approved')
                        ->whereNull('correction_stage');
                })
                ->groupBy('cash_charge_id')
                ->pluck('total_deducted', 'cash_charge_id');

        return $charges->each(function ($charge) use ($deductionsByCharge) {
            $deducted = (float) ($deductionsByCharge[$charge->id] ?? 0);
            $charge->setAttribute('remaining_balance', round(max(0, (float) $charge->amount - $deducted), 2));
        });
    }

    public function manageCashCharges()
    {
        $user = Auth::user();
        $scope = request()->query('scope', 'all');
        $employeeProfile = EmployeeProfile::where('user_id', $user?->id)->first();

        $isAllowed = $user && (
            $user->role === 'employee'
            || ($user->role === 'admin' && in_array($user->admin_type ?? '', ['super_admin', 'branch_admin'], true))
            || in_array($user->role, ['finance_officer', 'finance_head'], true)
            || $user->role === 'branch_head'
        );

        abort_unless($isAllowed, 403, 'You do not have access to cash charges.');

        if ($user->role === 'employee' || $scope === 'my') {
            $allMyCharges = CashCharge::with(['employeeProfile.user', 'employeeProfile.branch', 'requester', 'approver'])
                ->when($employeeProfile, function ($query, $profile) {
                    $query->where('employee_profile_id', $profile->id);
                }, function ($query) use ($user) {
                    $query->where('requested_by', $user->id);
                })
                ->orderByDesc('created_at')
                ->get();

            $archivedMyCharges = $allMyCharges
                ->filter(function ($charge) use ($user) {
                    return $charge->status === 'archived'
                        && $charge->archived_by_role === $this->resolveArchivedByRole($user);
                })
                ->values();
            $myCharges = $allMyCharges->where('status', '!=', 'archived')->values();

            $latestPendingIds = $myCharges
                ->filter(fn ($charge) => in_array($charge->status, ['pending_branch_admin', 'pending_super_admin'], true))
                ->groupBy('employee_profile_id')
                ->map(function ($charges) {
                    return $charges
                        ->sort(function ($left, $right) {
                            $leftCreated = $left->created_at?->timestamp ?? 0;
                            $rightCreated = $right->created_at?->timestamp ?? 0;

                            if ($leftCreated === $rightCreated) {
                                return ($right->id ?? 0) <=> ($left->id ?? 0);
                            }

                            return $rightCreated <=> $leftCreated;
                        })
                        ->first()
                        ->id;
                })
                ->values()
                ->all();

            $myCharges = $myCharges->filter(fn ($charge) =>
                !in_array($charge->status, ['pending_branch_admin', 'pending_super_admin'], true)
                || in_array($charge->id, $latestPendingIds, true)
            )->values();
            $myCharges = $this->attachRemainingCashChargeBalances($myCharges);

            return view('admin.cash-charges', [
                'employees' => collect(),
                'groupedEmployees' => collect(),
                'cashCharges' => $myCharges,
                'myCharges' => $myCharges,
                'archivedMyCharges' => $archivedMyCharges,
            ]);
        }

        $employees = EmployeeProfile::with(['user', 'branch'])
            ->whereHas('user', function ($userQuery) {
                $userQuery->where('is_active', true)
                    ->where(function ($roleQuery) {
                        $roleQuery->where('role', 'employee')
                            ->orWhere(function ($adminQuery) {
                                $adminQuery->where('role', 'admin')
                                    ->where('admin_type', 'branch_admin');
                            })
                            ->orWhereIn('role', ['finance_officer', 'finance_head'])
                            ->orWhere('role', 'branch_head');
                    });
            })
            ->when($user->role === 'branch_head', function ($query) use ($user) {
                $branch = $user->getBranchHeadProfile();
                return $query->where('branch_id', $branch?->branch_id ?? 0);
            })
            ->when($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin', function ($query) use ($user) {
                $adminProfile = $user->getAdminProfile();
                return $query->where('branch_id', $adminProfile?->branch_id ?? 0);
            })
            ->when($user->role === 'finance_head', function ($query) use ($user) {
                $profile = $user->getFinanceProfile();
                return $query->where('branch_id', $profile?->branch_id ?? 0);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        foreach ($employees as $employee) {
            $employee->leaveBalance = LeaveBalance::firstOrCreate(
                [
                    'employee_profile_id' => $employee->id,
                    'year' => now()->year,
                ],
                array_merge($this->getLeaveEntitlements($employee), [
                    'sick_leave_used' => 0,
                    'vacation_leave_used' => 0,
                    'emergency_leave_used' => 0,
                    'birthday_leave_used' => 0,
                    'cash_charge_used' => 0,
                    'maternity_leave_used' => 0,
                    'paternity_leave_used' => 0,
                    'service_incentive_leave_used' => 0,
                ])
            );
        }

        $allMyCharges = CashCharge::with(['employeeProfile.user', 'employeeProfile.branch', 'requester', 'approver'])
            ->when($user->role === 'employee', function ($query) use ($employeeProfile) {
                if ($employeeProfile) {
                    $query->where('employee_profile_id', $employeeProfile->id);
                } else {
                    $query->where('requested_by', auth()->id());
                }
            })
            ->when($user->role === 'branch_head', function ($query) use ($user) {
                $branch = $user->getBranchHeadProfile();
                return $query->where('branch_id', $branch?->branch_id ?? 0);
            })
            ->when($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin', function ($query) use ($user) {
                $adminProfile = $user->getAdminProfile();
                return $query->where('branch_id', $adminProfile?->branch_id ?? 0);
            })
            ->when(in_array($user->role, ['finance_head', 'finance_officer'], true), function ($query) use ($user) {
                $profile = $user->getFinanceProfile();
                return $query->where('branch_id', $profile?->branch_id ?? 0);
            })
            ->orderByDesc('created_at')
            ->get();

        $archivedMyCharges = $allMyCharges
            ->filter(function ($charge) use ($user) {
                if (! $user || ! ($user->role === 'admin' && ($user->admin_type ?? '') === 'super_admin')) {
                    return false;
                }

                return $charge->status === 'archived'
                    && $charge->archived_by_role === 'super_admin';
            })
            ->values();

        $myCharges = $allMyCharges->where('status', '!=', 'archived')->values();

        $latestPendingIds = $myCharges
            ->filter(fn ($charge) => in_array($charge->status, ['pending', 'pending_branch_admin', 'pending_super_admin'], true))
            ->groupBy('employee_profile_id')
            ->map(function ($charges) {
                return $charges
                    ->sort(function ($left, $right) {
                        $leftCreated = $left->created_at?->timestamp ?? 0;
                        $rightCreated = $right->created_at?->timestamp ?? 0;

                        if ($leftCreated === $rightCreated) {
                            return ($right->id ?? 0) <=> ($left->id ?? 0);
                        }

                        return $rightCreated <=> $leftCreated;
                    })
                    ->first()
                    ->id;
            })
            ->values()
            ->all();

        $myCharges = $myCharges->filter(fn ($charge) =>
            !in_array($charge->status, ['pending', 'pending_branch_admin', 'pending_super_admin'], true)
            || in_array($charge->id, $latestPendingIds, true)
        )->values();

        $allCashCharges = CashCharge::with(['employeeProfile.user', 'employeeProfile.branch', 'requester', 'approver'])
            ->when($user->role === 'employee', function ($query) use ($user, $employeeProfile) {
                return $query->where(function ($subQuery) use ($user, $employeeProfile) {
                    $subQuery->where('requested_by', $user->id);

                    if ($employeeProfile) {
                        $subQuery->orWhere('employee_profile_id', $employeeProfile->id);
                    }
                });
            })
            ->when($user->role === 'branch_head', function ($query) use ($user) {
                $branch = $user->getBranchHeadProfile();
                return $query->where('branch_id', $branch?->branch_id ?? 0);
            })
            ->when($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin', function ($query) use ($user) {
                $adminProfile = $user->getAdminProfile();
                return $query->where('branch_id', $adminProfile?->branch_id ?? 0);
            })
            ->when($user->role === 'finance_head', function ($query) use ($user) {
                $profile = $user->getFinanceProfile();
                return $query->where('branch_id', $profile?->branch_id ?? 0);
            })
            ->when($user->role === 'finance_officer', function ($query) use ($user) {
                $profile = $user->getFinanceProfile();
                return $query->where('branch_id', $profile?->branch_id ?? 0);
            })
            ->orderByDesc('created_at')
            ->get();

        $archivedCashCharges = $allCashCharges
            ->filter(function ($charge) use ($user) {
                if (! $user || ! ($user->role === 'admin' && ($user->admin_type ?? '') === 'super_admin')) {
                    return false;
                }

                return $charge->status === 'archived'
                    && $charge->archived_by_role === 'super_admin';
            })
            ->values();

        $cashCharges = $allCashCharges->where('status', '!=', 'archived')->values();

        $latestCashChargeIds = $cashCharges
            ->filter(fn ($charge) => in_array($charge->status, ['pending', 'pending_branch_admin', 'pending_super_admin'], true))
            ->groupBy('employee_profile_id')
            ->map(function ($charges) {
                return $charges
                    ->sort(function ($left, $right) {
                        $leftCreated = $left->created_at?->timestamp ?? 0;
                        $rightCreated = $right->created_at?->timestamp ?? 0;

                        if ($leftCreated === $rightCreated) {
                            return ($right->id ?? 0) <=> ($left->id ?? 0);
                        }

                        return $rightCreated <=> $leftCreated;
                    })
                    ->first()
                    ->id;
            })
            ->values()
            ->all();

        $cashCharges = $cashCharges->filter(fn ($charge) =>
            !in_array($charge->status, ['pending', 'pending_branch_admin', 'pending_super_admin'], true)
            || in_array($charge->id, $latestCashChargeIds, true)
        )->values();

        $groupedEmployees = $employees
            ->groupBy(fn ($employee) => $employee->branch?->branch_name ?? 'Unassigned')
            ->map(function ($branchEmployees) {
                return $branchEmployees->groupBy(function ($employee) {
                    $user = $employee->user;

                    if ($user && in_array($user->role, ['finance_officer', 'finance_head'], true)) {
                        return 'Finance Officer';
                    }

                    if ($user && $user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin') {
                        return 'Branch Admin';
                    }

                    return 'Employee';
                });
            });

        return view('admin.cash-charges', compact('employees', 'groupedEmployees', 'cashCharges', 'archivedCashCharges', 'myCharges', 'archivedMyCharges'));
    }

    public function storeCashCharge(Request $request)
    {
        $user = Auth::user();

        $isAllowed = $user && (
            ($user->role === 'admin' && in_array($user->admin_type ?? '', ['super_admin', 'branch_admin'], true))
            || $user->role === 'finance_head'
            || $user->role === 'branch_head'
        );

        abort_unless($isAllowed, 403, 'You do not have access to cash charges.');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employee_profiles,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:50000'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'evidence' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $employee = EmployeeProfile::with('branch')->findOrFail($validated['employee_id']);

        if ($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin') {
            $adminProfile = $user->getAdminProfile();
            abort_if($employee->branch_id !== ($adminProfile?->branch_id ?? 0), 403, 'You can only add charges for your branch employees.');
        }

        if ($user->role === 'finance_head') {
            $profile = $user->getFinanceProfile();
            abort_if($employee->branch_id !== ($profile?->branch_id ?? 0), 403, 'You can only add charges for your branch employees.');
        }

        if ($user->role === 'branch_head') {
            $profile = $user->getBranchHeadProfile();
            abort_if($employee->branch_id !== ($profile?->branch_id ?? 0), 403, 'You can only add charges for your branch employees.');
        }

        $evidencePath = $request->file('evidence')?->store('cash-charge-evidence', 'local');

        $charge = CashCharge::create([
            'employee_profile_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'amount' => (float) $validated['amount'],
            'reason' => $validated['reason'],
            'evidence_path' => $evidencePath,
            'requested_by' => $user->id,
            'status' => $user->role === 'admin' && ($user->admin_type ?? '') === 'super_admin'
                ? 'pending_super_admin'
                : 'pending',
        ]);

        return back()->with('success', 'Cash charge request submitted and awaiting Super Admin approval.');
    }

    public function cashChargeEvidence(CashCharge $cashCharge)
    {
        $user = Auth::user();
        $profile = $user->getEmployeeProfile();

        $canView = ($user->role === 'admin' && ($user->admin_type ?? '') === 'super_admin')
            || ($user->role === 'admin' && ($user->admin_type ?? '') === 'branch_admin'
                && $cashCharge->branch_id === ($user->getAdminProfile()?->branch_id ?? 0))
            || ($user->role === 'branch_head'
                && $cashCharge->branch_id === ($user->getBranchHeadProfile()?->branch_id ?? 0))
            || ($user->role === 'finance_head'
                && $cashCharge->branch_id === ($user->getFinanceProfile()?->branch_id ?? 0))
            || ($user->role === 'finance_officer'
                && $cashCharge->branch_id === ($user->getFinanceProfile()?->branch_id ?? 0))
            || (in_array($user->role, ['employee', 'finance_officer'], true)
                && ($cashCharge->requested_by === $user->id
                    || ($profile && $cashCharge->employee_profile_id === $profile->id)));

        abort_unless($canView, 403, 'You do not have access to this cash charge evidence.');
        abort_unless($cashCharge->evidence_path && Storage::disk('local')->exists($cashCharge->evidence_path), 404);

        return response()->file(Storage::disk('local')->path($cashCharge->evidence_path), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approveCashCharge(Request $request, CashCharge $cashCharge)
    {
        $user = Auth::user();
        $decision = $request->input('decision');
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($user->role === 'admin' && $user->admin_type === 'branch_admin') {
            abort_unless($cashCharge->branch_id === ($user->getAdminProfile()?->branch_id ?? 0), 403, 'You can only review charges for your branch.');
            abort_unless(in_array($cashCharge->status, ['pending', 'pending_branch_admin'], true), 403, 'This request is not pending branch approval.');

            if ($validated['decision'] === 'approve') {
                $cashCharge->status = 'pending_super_admin';
                $cashCharge->approved_by = $user->id;
                $cashCharge->approved_at = now();
                $cashCharge->save();

                return back()->with('success', 'Cash charge adjustment sent to Super Admin for final approval.');
            }

            $cashCharge->status = 'rejected';
            $cashCharge->approved_by = $user->id;
            $cashCharge->approved_at = now();
            $cashCharge->reason = $validated['reason'] ?? $cashCharge->reason;
            $cashCharge->save();

            return back()->with('success', 'Cash charge adjustment request was rejected by the Branch Admin.');
        }

        abort_unless($user->role === 'admin' && $user->admin_type === 'super_admin', 403, 'Only the Super Admin can final approve cash charges.');
        abort_unless($cashCharge->status === 'pending_super_admin', 403, 'This request is not waiting for final approval.');

        if ($validated['decision'] === 'approve') {
            $cashCharge->status = 'approved';
            $cashCharge->approved_by = $user->id;
            $cashCharge->approved_at = now();
            $cashCharge->save();

            $balance = LeaveBalance::firstOrCreate(
                ['employee_profile_id' => $cashCharge->employee_profile_id, 'year' => now()->year],
                [
                    'sick_leave_used' => 0,
                    'vacation_leave_used' => 0,
                    'emergency_leave_used' => 0,
                    'birthday_leave_used' => 0,
                    'cash_charge_used' => 0,
                    'maternity_leave_used' => 0,
                    'paternity_leave_used' => 0,
                ]
            );

            $balance->cash_charge_total = (float) $cashCharge->amount;
            $balance->save();

            return back()->with('success', 'Cash charge adjustment approved successfully.');
        }

        $cashCharge->status = 'rejected';
        $cashCharge->approved_by = $user->id;
        $cashCharge->approved_at = now();
        $cashCharge->reason = $validated['reason'] ?? $cashCharge->reason;
        $cashCharge->save();

        return back()->with('success', 'Cash charge adjustment request was rejected by the Super Admin.');
    }

    private function resolveArchivedByRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->role === 'employee') {
            return 'employee';
        }

        if ($user->role === 'admin') {
            return match ($user->admin_type ?? '') {
                'super_admin' => 'super_admin',
                'branch_admin' => 'branch_admin',
                default => null,
            };
        }

        if ($user->role === 'finance_head') {
            return 'finance_head';
        }

        if ($user->role === 'finance_officer') {
            return 'finance_officer';
        }

        if ($user->role === 'branch_head') {
            return 'branch_head';
        }

        return null;
    }

    public function archiveCashCharge(CashCharge $cashCharge)
    {
        $user = Auth::user();
        abort_unless($user, 403, 'You must be logged in to archive cash charges.');

        $canManage = $user->role === 'admin'
            && ($user->admin_type ?? '') === 'super_admin';

        abort_unless($canManage, 403, 'Only the Super Admin can archive cash charges.');

        if ($cashCharge->status === 'archived') {
            return back()->with('warning', 'This cash charge is already archived.');
        }

        if ($cashCharge->archived_by_role && $cashCharge->archived_by_role !== 'super_admin') {
            return back()->with('warning', 'This cash charge was archived by another role and cannot be managed here.');
        }

        $cashCharge->archived_from_status = $cashCharge->status;
        $cashCharge->archived_by_role = 'super_admin';
        $cashCharge->status = 'archived';
        $cashCharge->save();

        return back()->with('success', 'Cash charge has been moved to Archives.');
    }

    public function unarchiveCashCharge(CashCharge $cashCharge)
    {
        $user = Auth::user();
        abort_unless($user, 403, 'You must be logged in to restore cash charges.');

        $canManage = $user->role === 'admin'
            && ($user->admin_type ?? '') === 'super_admin';

        abort_unless($canManage, 403, 'Only the Super Admin can restore cash charges.');

        if ($cashCharge->status !== 'archived') {
            return back()->with('warning', 'This cash charge is not archived.');
        }

        if ($cashCharge->archived_by_role && $cashCharge->archived_by_role !== 'super_admin') {
            return back()->with('warning', 'This cash charge was archived by another role and cannot be restored here.');
        }

        $cashCharge->status = $cashCharge->archived_from_status ?? 'approved';
        $cashCharge->archived_from_status = null;
        $cashCharge->archived_by_role = null;
        $cashCharge->save();

        return back()->with('success', 'Cash charge has been restored from Archives.');
    }

    public function requestCashChargeBalanceUpdate(Request $request, EmployeeProfile $employee)
    {
        $user = Auth::user();
        abort_unless($user && $employee->user_id === $user->id, 403, 'You can only request updates for your own balance.');

        $request->validate([
            'cash_charge_total' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'installment_per_cutoff' => ['nullable', 'numeric', 'min:0', 'max:50000'],
        ]);

        $cashChargeTotal = (float) ($request->input('cash_charge_total') ?? 0);
        $installmentPerCutoff = (float) ($request->input('installment_per_cutoff') ?? 0);

        if ($request->filled('cash_charge_total')) {
            $cashChargeTotal = (float) $request->input('cash_charge_total');
        }

        if ($request->filled('installment_per_cutoff')) {
            $installmentPerCutoff = (float) $request->input('installment_per_cutoff');
        }

        if (! $request->filled('cash_charge_total') && $request->filled('installment_per_cutoff')) {
            $existingBalance = LeaveBalance::where('employee_profile_id', $employee->id)
                ->where('year', now()->year)
                ->value('cash_charge_total');

            $cashChargeTotal = $existingBalance !== null
                ? (float) $existingBalance
                : ($installmentPerCutoff > 0 ? $installmentPerCutoff * 2 : 0);
        }

        abort_if($cashChargeTotal <= 0, 422, 'The total charge balance must be greater than zero.');

        CashCharge::create([
            'employee_profile_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'amount' => $cashChargeTotal,
            'installment_per_cutoff' => $installmentPerCutoff > 0 ? $installmentPerCutoff : ($cashChargeTotal > 0 ? $cashChargeTotal / 2 : 0),
            'reason' => 'Cash charge balance adjustment request pending approval.',
            'requested_by' => $user->id,
            'status' => 'pending_branch_admin',
        ]);

        return back()->with('success', 'Your cash charge balance update has been submitted and is awaiting Branch Admin and Super Admin approval.');
    }

    public function updateCashCharges(Request $request, EmployeeProfile $employee)
    {
        $user = Auth::user();
        $isAllowed = $user && (
            ($user->role === 'admin' && in_array($user->admin_type ?? '', ['super_admin', 'branch_admin'], true))
            || $user->role === 'finance_head'
            || $user->role === 'branch_head'
        );

        abort_unless($isAllowed, 403, 'You do not have access to cash charges.');

        $validated = $request->validate([
            'cash_charge_total' => ['required', 'numeric', 'min:0', 'max:365'],
        ]);

        $leaveBalance = LeaveBalance::firstOrCreate(
            [
                'employee_profile_id' => $employee->id,
                'year' => now()->year,
            ],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
                'service_incentive_leave_used' => 0,
            ]
        );

        $leaveBalance->cash_charge_total = (float) $validated['cash_charge_total'];
        $leaveBalance->save();

        return back()->with('success', 'Cash charges updated for ' . $employee->first_name . ' ' . $employee->last_name . '.');
    }

    public function updateCredits(Request $request, EmployeeProfile $employee)
    {
        $this->ensureSuperAdminAccess();

        $validated = $request->validate([
            'sick_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
            'vacation_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
            'emergency_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
            'birthday_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
            'maternity_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
            'paternity_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
            'service_incentive_leave_total' => ['required', 'numeric', 'min:0', 'max:365'],
        ]);

        $leaveBalance = LeaveBalance::firstOrCreate(
            [
                'employee_profile_id' => $employee->id,
                'year' => now()->year,
            ],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
                'service_incentive_leave_used' => 0,
            ]
        );

        $updatedCredits = [];

        foreach (['sick_leave_total', 'vacation_leave_total', 'emergency_leave_total', 'birthday_leave_total', 'maternity_leave_total', 'paternity_leave_total', 'service_incentive_leave_total'] as $field) {
            $value = (float) $validated[$field];
            $updatedCredits[$this->formatLeaveTypeLabel($field)] = $value;
            $leaveBalance->{$field} = $value;
        }

        $leaveBalance->save();

        $employeeUser = $employee->user;

        if ($employeeUser) {
            try {
                Mail::to($employeeUser->email)->send(new \App\Mail\LeaveCreditsUpdated($employee, $updatedCredits));
            } catch (\Exception $e) {
                \Log::warning('Failed to send leave credits updated email: ' . $e->getMessage());
            }

            $employeeUser->notify(new \App\Notifications\SystemNotification(
                'Leave credits updated',
                'Your leave credits have been updated by the administrator.',
                'leave_credits_updated',
                route('dashboard')
            ));
        }

        return back()->with('success', 'Leave credits updated for ' . $employee->first_name . ' ' . $employee->last_name . '.');
    }

    public function resetUsedCredits(EmployeeProfile $employee)
    {
        $this->ensureSuperAdminAccess();

        $leaveBalance = LeaveBalance::firstOrCreate(
            [
                'employee_profile_id' => $employee->id,
                'year' => now()->year,
            ],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
                'service_incentive_leave_used' => 0,
            ]
        );

        foreach ([
            'sick_leave_used',
            'vacation_leave_used',
            'emergency_leave_used',
            'birthday_leave_used',
            'maternity_leave_used',
            'paternity_leave_used',
            'service_incentive_leave_used',
        ] as $field) {
            $leaveBalance->{$field} = 0;
        }

        $leaveBalance->save();

        return back()->with('success', 'Used leave credits reset for ' . $employee->first_name . ' ' . $employee->last_name . '.');
    }

    protected function formatLeaveTypeLabel(string $field): string
    {
        return match ($field) {
            'sick_leave_total' => 'Sick Leave',
            'vacation_leave_total' => 'Vacation Leave',
            'emergency_leave_total' => 'Emergency Leave',
            'birthday_leave_total' => 'Birthday Leave',
            'maternity_leave_total' => 'Maternity Leave',
            'paternity_leave_total' => 'Paternity Leave',
            'service_incentive_leave_total' => 'Service Incentive Leave',
            default => ucfirst(str_replace('_total', '', $field)),
        };
    }

    protected function ensureSuperAdminAccess()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin' || $user->admin_type !== 'super_admin') {
            abort(403, 'Only the Super Admin can manage leave credits.');
        }
    }

    protected function isNewHire($profile)
    {
        return in_array($this->normalizeEmploymentStatus($profile->status ?? 'New Hire'), ['New Hire'], true);
    }

    protected function isBranchHead($user)
    {
        return ($user->role === 'admin' && $user->admin_type === 'branch_admin') || $user->role === 'branch_head';
    }

    protected function isBranchHeadLeave($leave)
    {
        return ($leave->employeeProfile?->user?->role === 'admin'
            && $leave->employeeProfile?->user?->admin_type === 'branch_admin')
            || $leave->employeeProfile?->user?->role === 'branch_head';
    }

    protected function normalizeEmploymentStatus($status)
    {
        $normalized = trim((string) $status);

        return match ($normalized) {
            'New Hire' => 'New Hire',
            'Regular' => 'Regular',
            '1-2 Years in Service' => '1 Year of Service',
            '3+ Year of Service' => '3+ Years of Service',
            default => $normalized,
        };
    }
    
    public function approve($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'branch_head') {
            abort(403);
        }
        
        $leave = LeaveRequest::with('employeeProfile')->findOrFail($id);

        if ($this->isBranchHeadLeave($leave) && $user->admin_type !== 'super_admin') {
            return back()->with('error', 'Only the System Administrator can approve a Branch Head leave.');
        }
        
        // Check if branch admin can only approve leaves from their branch
        if ($user->isBranchAdmin()) {
            $adminProfile = $user->isBranchHead() ? $user->getBranchHeadProfile() : $user->getAdminProfile();
            if (!$adminProfile || $leave->employeeProfile->branch_id !== $adminProfile->branch_id) {
                abort(403, 'You can only approve leave requests from your branch.');
            }
        }
        
        if ($user->isBranchAdmin() && !$user->isSuperAdmin()) {
            if ($leave->status !== 'pending') {
                return back()->with('error', 'Only pending leave requests can be approved by the Branch Head.');
            }

            $leave->update([
                'status' => 'pending_system_admin',
                'branch_approved_by' => $user->id,
                'branch_approved_at' => now(),
            ]);

            $this->notifySystemReviewers($leave);

            return back()->with('success', 'Leave approved by Branch Head and forwarded to the System Administrator.');
        }

        if ($user->admin_type !== 'super_admin' || !in_array($leave->status, ['pending', 'pending_system_admin'], true)) {
            return back()->with('error', 'This leave is not ready for System Administrator approval.');
        }

        $isNewHireLeave = $this->isNewHire($leave->employeeProfile);
        $isUnpaidLeave = (bool) $leave->is_absent || $isNewHireLeave;

        // New hires and exhausted leave balances are treated as unpaid leave in DTR totals.
        if (!$isNewHireLeave && !$leave->is_absent) {
            $leaveBalance = $this->getLeaveBalance($leave->employeeProfile);

            if ($leave->leave_type == 'sick') {
                $leaveBalance->sick_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'vacation') {
                $leaveBalance->vacation_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'emergency') {
                $leaveBalance->emergency_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'birthday') {
                $leaveBalance->birthday_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'maternity') {
                $leaveBalance->maternity_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'paternity') {
                $leaveBalance->paternity_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'service_incentive') {
                $leaveBalance->service_incentive_leave_used += $leave->total_days;
            }
            $leaveBalance->save();
        }
        
        $leave->update([
            'status' => 'approved',
            'is_absent' => $isUnpaidLeave,
            'system_admin_approved_by' => $user->id,
            'system_admin_approved_at' => now(),
            'approved_at' => now(),
        ]);
        
        // Send email notification to employee
        try {
            Mail::to($leave->employeeProfile->user->email)->send(new \App\Mail\LeaveApproved($leave, $leave->employeeProfile));
        } catch (\Exception $e) {
            \Log::warning('Failed to send leave approval email: ' . $e->getMessage());
        }

        $leave->employeeProfile->user->notify(new SystemNotification(
            'Leave approved',
            'Your ' . ucfirst($leave->leave_type) . ' leave request was approved.',
            'leave_approved',
            route('calendar.calendar')
        ));
        
        return back()->with('success', 'Leave approved by System Administrator. The employee was notified by email.');
    }
    
    public function reject($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'branch_head') {
            abort(403);
        }
        
        $leave = LeaveRequest::with('employeeProfile')->findOrFail($id);

        if ($this->isBranchHeadLeave($leave) && $user->admin_type !== 'super_admin') {
            return back()->with('error', 'Only the System Administrator can reject a Branch Head leave.');
        }
        
        // Check if branch admin can only reject leaves from their branch
        if ($user->isBranchAdmin()) {
            $adminProfile = $user->isBranchHead() ? $user->getBranchHeadProfile() : $user->getAdminProfile();
            if (!$adminProfile || $leave->employeeProfile->branch_id !== $adminProfile->branch_id) {
                abort(403, 'You can only reject leave requests from your branch.');
            }
        }
        
        $leave->update(['status' => 'rejected']);
        
        // Send email notification to employee
        try {
            Mail::to($leave->employeeProfile->user->email)->send(new \App\Mail\LeaveRejected($leave, $leave->employeeProfile));
        } catch (\Exception $e) {
            \Log::warning('Failed to send leave rejection email: ' . $e->getMessage());
        }

        $leave->employeeProfile->user->notify(new SystemNotification(
            'Leave rejected',
            'Your ' . ucfirst($leave->leave_type) . ' leave request was rejected.',
            'leave_rejected',
            route('leave.index')
        ));
        
        return back()->with('success', 'Leave rejected');
    }

    private function notifyBranchReviewers(EmployeeProfile $profile, string $leaveType): void
    {
        $recipients = User::where(function ($query) use ($profile) {
                $query->where(function ($branchQuery) use ($profile) {
                    $branchQuery->where('role', 'branch_head')
                        ->whereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $profile->branch_id));
                })->orWhere(function ($branchQuery) use ($profile) {
                    $branchQuery->where('role', 'admin')
                        ->where('admin_type', 'branch_admin')
                        ->where(function ($userQuery) use ($profile) {
                            $userQuery->where('branch_id', $profile->branch_id)
                                ->orWhereHas('profile', fn ($profileQuery) => $profileQuery->where('branch_id', $profile->branch_id));
                        });
                });
            })
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new SystemNotification(
                'Leave request needs review',
                'A ' . ucfirst($leaveType) . ' leave request is waiting for Branch Head review.',
                'leave_pending_branch',
                route('leave.index')
            ));
        }
    }

    private function notifySystemReviewers(LeaveRequest $leave): void
    {
        User::where('role', 'admin')
            ->where(function ($query) {
                $query->where('admin_type', 'super_admin')->orWhereNull('admin_type');
            })
            ->where('is_active', true)
            ->get()
            ->each(fn ($recipient) => $recipient->notify(new SystemNotification(
                'Leave request needs final approval',
                'A leave request has been approved by the Branch Head and is waiting for final review.',
                'leave_pending_system',
                route('leave.index')
            )));
    }
}
