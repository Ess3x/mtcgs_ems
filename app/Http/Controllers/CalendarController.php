<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\LeaveRequest;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'period' => 'nullable|in:yearly,monthly',
            'year' => 'exclude_unless:period,yearly,monthly|required|integer|between:2026,2100',
            'month' => 'exclude_unless:period,monthly|required|integer|between:1,12',
        ]);

        $user = Auth::user();
        $events = collect();

        if ($user->role === 'admin') {
            if ($user->admin_type === 'branch_admin') {
                $adminProfile = $user->getAdminProfile();
                $branchId = $adminProfile->branch_id ?? null;

                $events = CalendarEvent::with(['creator', 'branch'])
                    ->where(function($query) use ($branchId) {
                        $query->where('branch_id', $branchId)
                              ->orWhereNull('branch_id');
                    })
                    ->where(function ($query) use ($user) {
                        $query->where('approval_status', 'approved')
                            ->orWhere(function ($pending) use ($user) {
                                $pending->where('approval_status', 'pending')
                                    ->where('created_by', $user->id);
                            });
                    })
                    ->orderBy('event_date', 'desc')
                    ->get();
            } else {
                $events = CalendarEvent::with(['creator', 'branch'])
                    ->orderBy('event_date', 'desc')
                    ->get();
            }
        } elseif ($user->role === 'finance_head') {
            $financeProfile = $user->getFinanceProfile();
            if ($financeProfile && $financeProfile->branch_id) {
                $events = CalendarEvent::with(['creator', 'branch'])
                    ->where(function($query) use ($financeProfile) {
                        $query->where('branch_id', $financeProfile->branch_id)
                              ->orWhereNull('branch_id');
                    })
                    ->where('approval_status', 'approved')
                    ->orderBy('event_date', 'desc')
                    ->get();
            }
        } elseif ($user->role === 'employee') {
            $employeeProfile = $user->getEmployeeProfile();
            if ($employeeProfile && $employeeProfile->branch_id) {
                $events = CalendarEvent::with(['creator', 'branch'])
                    ->where(function($query) use ($employeeProfile) {
                        $query->where('branch_id', $employeeProfile->branch_id)
                              ->orWhereNull('branch_id');
                    })
                    ->where('approval_status', 'approved')
                    ->orderBy('event_date', 'desc')
                    ->get();
            }
        }

        $events = $this->appendConfiguredHolidays($events);

        if (($filters['period'] ?? null) === 'yearly') {
            $events = $events->filter(fn ($event) => $event->event_date->year === (int) $filters['year']);
        } elseif (($filters['period'] ?? null) === 'monthly') {
            $events = $events->filter(fn ($event) =>
                $event->event_date->year === (int) $filters['year']
                && $event->event_date->month === (int) $filters['month']
            );
        }

        $events = $events->values();

        return view('calendar.index', compact('events'));
    }

    public function create()
    {
        $this->authorize('create', CalendarEvent::class);
        $branches = Branch::all();
        return view('calendar.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CalendarEvent::class);

        $user = Auth::user();
        $adminProfile = $user->getAdminProfile();
        $isSuperAdmin = $user->isSuperAdmin();

        // For regular admins, automatically set branch_id to their branch
        if (!$isSuperAdmin && $adminProfile && $adminProfile->branch_id) {
            $request->merge(['branch_id' => $adminProfile->branch_id]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date|after_or_equal:today',
            'event_type' => 'required|in:activity,holiday,suspension',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $requiresApproval = !$isSuperAdmin
            && $user->role === 'admin'
            && $user->admin_type === 'branch_admin'
            && in_array($request->event_type, ['holiday', 'suspension'], true);

        CalendarEvent::create([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'event_type' => $request->event_type,
            'created_by' => Auth::id(),
            'branch_id' => $request->branch_id,
            'approval_status' => $requiresApproval ? 'pending' : 'approved',
            'approved_by' => $requiresApproval ? null : Auth::id(),
            'approved_at' => $requiresApproval ? null : now(),
        ]);

        return redirect()->route('calendar.index')->with('success', 'Calendar event created successfully.');
    }

    public function edit(CalendarEvent $calendar)
    {
        $this->authorize('update', $calendar);
        $branches = Branch::all();
        $calendarEvent = $calendar;
        return view('calendar.edit', compact('calendarEvent', 'branches'));
    }

    public function update(Request $request, CalendarEvent $calendar)
    {
        $this->authorize('update', $calendar);

        $user = Auth::user();
        $adminProfile = $user->getAdminProfile();
        $isSuperAdmin = $user->isSuperAdmin();

        // For regular admins, automatically set branch_id to their branch
        if (!$isSuperAdmin && $adminProfile && $adminProfile->branch_id) {
            $request->merge(['branch_id' => $adminProfile->branch_id]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date|after_or_equal:today',
            'event_type' => 'required|in:activity,holiday,suspension',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $requiresApproval = in_array($request->event_type, ['holiday', 'suspension'], true) && !$isSuperAdmin;

        $calendar->update([
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'event_type' => $request->event_type,
            'branch_id' => $request->branch_id,
            'approval_status' => $requiresApproval ? 'pending' : ($calendar->approval_status === 'pending' ? 'approved' : $calendar->approval_status),
            'approved_by' => $requiresApproval ? null : ($calendar->approval_status === 'pending' ? Auth::id() : $calendar->approved_by),
            'approved_at' => $requiresApproval ? null : ($calendar->approval_status === 'pending' ? now() : $calendar->approved_at),
        ]);

        return redirect()->route('calendar.index')->with('success', 'Calendar event updated successfully.');
    }

    public function destroy(CalendarEvent $calendar)
    {
        $this->authorize('delete', $calendar);
        $calendar->delete();
        return redirect()->route('calendar.index')->with('success', 'Calendar event deleted successfully.');
    }

    public function approve(CalendarEvent $calendar)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        if (!in_array($calendar->event_type, ['holiday', 'suspension'], true) || $calendar->approval_status !== 'pending') {
            return redirect()->route('calendar.index')->with('error', 'Only pending holiday or suspension requests can be approved.');
        }

        $calendar->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('calendar.index')->with('success', 'Calendar event approved successfully.');
    }

    public function calendar()
    {
        $user = Auth::user();
        $events = collect();
        $leaveRequests = collect();
        $currentUserProfileId = null;

        if ($user->role === 'admin') {
            if ($user->admin_type === 'branch_admin') {
                $adminProfile = $user->getAdminProfile();
                $branchId = $adminProfile->branch_id ?? null;

                $events = CalendarEvent::where('approval_status', 'approved')
                    ->where(function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                })->get();

                $leaveRequests = LeaveRequest::with('employeeProfile')
                    ->where('status', 'approved')
                    ->whereHas('employeeProfile', function ($query) use ($branchId) {
                        $query->where('branch_id', $branchId);
                    })->orderBy('start_date')->get();
            } else {
                $events = CalendarEvent::where('approval_status', 'approved')->get();
                $leaveRequests = LeaveRequest::with('employeeProfile')
                    ->where('status', 'approved')
                    ->orderBy('start_date')
                    ->get();
            }
        } elseif ($user->role === 'finance_head') {
            $financeProfile = $user->getFinanceProfile();
            if ($financeProfile) {
                $events = CalendarEvent::where('approval_status', 'approved')->where(function($query) use ($financeProfile) {
                    $query->whereNull('branch_id');
                    if ($financeProfile->branch_id) {
                        $query->orWhere('branch_id', $financeProfile->branch_id);
                    }
                })->get();

                $leaveRequests = LeaveRequest::with('employeeProfile')
                    ->where('status', 'approved')
                    ->when($financeProfile->branch_id, function ($query) use ($financeProfile) {
                        $query->whereHas('employeeProfile', function ($profileQuery) use ($financeProfile) {
                            $profileQuery->where('branch_id', $financeProfile->branch_id);
                        });
                    })->orderBy('start_date')->get();
            }
        } elseif ($user->role === 'finance_officer') {
            $financeProfile = $user->getFinanceProfile();
            $currentUserProfileId = $financeProfile?->employee_profile_id;
            $leaveRequests = LeaveRequest::with('employeeProfile')
                ->where('employee_id', $user->id)
                ->where('status', 'approved')
                ->orderBy('start_date')
                ->get();
        } elseif ($user->role === 'employee') {
            $employeeProfile = $user->getEmployeeProfile();
            $currentUserProfileId = $employeeProfile->id;
            if ($employeeProfile && $employeeProfile->branch_id) {
                $events = CalendarEvent::where('approval_status', 'approved')->where(function($query) use ($employeeProfile) {
                    $query->where('branch_id', $employeeProfile->branch_id)
                          ->orWhereNull('branch_id');
                })->get();

                // Show all branch leaves for employees
                $leaveRequests = LeaveRequest::with('employeeProfile')
                    ->where('status', 'approved')
                    ->whereHas('employeeProfile', function ($query) use ($employeeProfile) {
                        $query->where('branch_id', $employeeProfile->branch_id);
                    })->orderBy('start_date')->get();
            }
        }

        $events = $this->appendConfiguredHolidays($events);

        return view('calendar.calendar', compact('events', 'leaveRequests', 'currentUserProfileId'));
    }

    private function appendConfiguredHolidays($events)
    {
        $events = collect($events);
        $years = [Carbon::now(config('app.timezone'))->year, Carbon::now(config('app.timezone'))->addYear()->year];
        $existingDates = $events->where('event_type', 'holiday')
            ->where('approval_status', 'approved')
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
}
