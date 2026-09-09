<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\CalendarEvent;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
                    ->latest()->paginate(20);
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
                    ->latest()->paginate(20);
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
            'leave_type' => 'required|in:sick,vacation,emergency,maternity,paternity',
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
        } elseif ($request->leave_type == 'maternity') {
            $available = $leaveBalance->getAvailableMaternityLeave();
        } elseif ($request->leave_type == 'paternity') {
            $available = $leaveBalance->getAvailablePaternityLeave();
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
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ])
        );

        $leaveBalance->update($entitlements);

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
            '1-2 Years in Service' => 6,
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
            'maternity_leave_total' => $hasParentalLeave ? $parentalCredits : 0,
            'paternity_leave_total' => $hasParentalLeave ? $parentalCredits : 0,
        ];
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
            } elseif ($leave->leave_type == 'maternity') {
                $leaveBalance->maternity_leave_used += $leave->total_days;
            } elseif ($leave->leave_type == 'paternity') {
                $leaveBalance->paternity_leave_used += $leave->total_days;
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
