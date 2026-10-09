<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    private function canViewSchedules(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->role === 'branch_head'
            || ($user->role === 'admin' && in_array($user->admin_type ?? '', ['branch_admin', 'hr'], true));
    }

    private function canManageSchedules(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin' && $user->admin_type === 'branch_admin';
    }

    public function index()
    {
        abort_unless($this->canViewSchedules(), 403, 'You do not have access to view shifts and schedules.');

        $user = Auth::user();
        $branchId = $user->isSuperAdmin() ? null : $user->getEffectiveBranchId();

        $shifts = Shift::withCount(['assignedEmployees' => function ($query) use ($branchId) {
            if ($branchId !== null) {
                $query->where('branch_id', $branchId);
            }
        }])->orderBy('name')->get();

        $employees = EmployeeProfile::with(['shift', 'shifts', 'branch'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('last_name')
            ->get();

        $canManageSchedules = $this->canManageSchedules();

        return view('admin.shifts.index', compact('shifts', 'employees', 'canManageSchedules'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageSchedules(), 403, 'Only Branch Admin can set schedules.');
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:shifts,name',
            'class_code' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i|after:break_start',
            'working_days' => 'required|array|min:1',
        ]);
        $validated['working_days'] = implode(',', $validated['working_days']);
        Shift::create($validated);
        return back()->with('success', 'Shift created successfully.');
    }

    public function update(Request $request, Shift $shift)
    {
        abort_unless($this->canManageSchedules(), 403, 'Only Branch Admin can set schedules.');
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:shifts,name,' . $shift->id,
            'class_code' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i|after:break_start',
            'working_days' => 'required|array|min:1',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['working_days'] = implode(',', $validated['working_days']);
        $shift->update($validated);
        return back()->with('success', 'Shift updated successfully.');
    }

    public function employeeSchedule(EmployeeProfile $employee)
    {
        abort_unless($this->canViewSchedules(), 403, 'You do not have access to view shifts and schedules.');

        $user = Auth::user();
        if (!$user->isSuperAdmin() && $employee->branch_id !== $user->getEffectiveBranchId()) {
            abort(403, 'You can only view schedules for employees in your branch.');
        }

        $profile = $employee->load('shifts', 'shift');
        $shifts = $profile->shifts;

        if ($shifts->isEmpty() && $profile->shift) {
            $shifts = collect([$profile->shift]);
        }

        return view('employee.schedule', compact('profile', 'shifts'));
    }

    public function detachShift(EmployeeProfile $employee, Shift $shift)
    {
        abort_unless($this->canManageSchedules(), 403, 'Only Branch Admin can set schedules.');

        $user = Auth::user();
        if (!$user->isSuperAdmin() && $employee->branch_id !== $user->getEffectiveBranchId()) {
            abort(403, 'You can only remove schedules from employees in your branch.');
        }

        $employee->shifts()->detach($shift->id);

        if (($employee->shift_id ?? null) == $shift->id) {
            $remainingShift = $employee->shifts()->first();
            $employee->update(['shift_id' => $remainingShift?->id]);
        }

        return back()->with('success', 'Employee schedule removed successfully.');
    }

    public function assign(Request $request, EmployeeProfile $employee)
    {
        abort_unless($this->canManageSchedules(), 403, 'Only Branch Admin can set schedules.');
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $employee->branch_id !== $user->getEffectiveBranchId()) {
            abort(403, 'You can only assign schedules to employees in your branch.');
        }

        $validated = $request->validate([
            'shift_ids' => 'nullable|array',
            'shift_ids.*' => 'exists:shifts,id',
        ]);

        $shiftIds = $validated['shift_ids'] ?? [];
        $allowedShiftIds = Shift::whereIn('id', $shiftIds)->pluck('id')->all();
        $employee->shifts()->sync($allowedShiftIds);

        // Keep the legacy single-shift column aligned for older attendance code.
        $employee->update(['shift_id' => $allowedShiftIds[0] ?? null]);
        return back()->with('success', 'Employee shift assignment updated.');
    }

    public function destroy(Shift $shift)
    {
        abort_unless($this->canManageSchedules(), 403, 'Only Branch Admin can set schedules.');
        $shift->assignedEmployees()->detach();
        $shift->employeeProfiles()->update(['shift_id' => null]);
        $shift->delete();
        return back()->with('success', 'Shift deleted and employee assignments cleared.');
    }
}
