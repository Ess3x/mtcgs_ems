<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class EmployeeScheduleController extends Controller
{
    public function index()
    {
        $profile = Auth::user()->getEmployeeProfile();

        if (!$profile) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found');
        }

        $profile->load('shifts', 'shift');
        $shifts = $profile->shifts;
        if ($shifts->isEmpty() && $profile->shift) {
            $shifts = collect([$profile->shift]);
        }

        return view('employee.schedule', compact('profile', 'shifts'));
    }
}