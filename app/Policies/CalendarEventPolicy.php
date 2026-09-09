<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CalendarEventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'finance_officer', 'finance_head', 'employee']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if (in_array($user->role, ['finance_officer', 'finance_head'], true)) {
            $financeProfile = $user->getFinanceProfile();
            return $financeProfile && ($calendarEvent->branch_id === $financeProfile->branch_id || $calendarEvent->branch_id === null);
        }

        if ($user->role === 'employee') {
            $employeeProfile = $user->getEmployeeProfile();
            return $employeeProfile && ($calendarEvent->branch_id === $employeeProfile->branch_id || $calendarEvent->branch_id === null);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CalendarEvent $calendarEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CalendarEvent $calendarEvent): bool
    {
        return false;
    }
}
