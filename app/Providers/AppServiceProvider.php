<?php

namespace App\Providers;

use App\Models\AdminProfile;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\DTR;
use App\Models\EmployeeAdditionalDeduction;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        foreach ([
            AdminProfile::class,
            AttendanceLog::class,
            Branch::class,
            CalendarEvent::class,
            DTR::class,
            EmployeeAdditionalDeduction::class,
            EmployeeProfile::class,
            FinanceProfile::class,
            LeaveBalance::class,
            LeaveRequest::class,
            PayrollEntry::class,
            PayrollPeriod::class,
            User::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
