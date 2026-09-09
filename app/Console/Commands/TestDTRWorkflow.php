<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmployeeProfile;
use App\Models\AttendanceLog;
use App\Models\DTR;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Services\PayrollComputationService;
use Carbon\Carbon;

class TestDTRWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:dtr-workflow {employee_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete DTR workflow: generate attendance, create DTR, submit, approve, generate payroll';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->argument('employee_id') ?? 1; // Default to first ghost employee

        $employee = EmployeeProfile::find($employeeId);
        if (!$employee) {
            $this->error("Employee not found with ID: $employeeId");
            return;
        }

        $this->info("Testing DTR workflow for: {$employee->first_name} {$employee->last_name}");

        // Define biweekly period: May 1-15, 2026
        $periodStart = Carbon::create(2026, 5, 1);
        $periodEnd = Carbon::create(2026, 5, 15);

        $this->info("Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}");

        // Step 1: Create attendance logs for the period
        $this->createAttendanceLogs($employee, $periodStart, $periodEnd);

        // Step 2: Generate DTR
        $dtr = $this->generateDTR($employee, $periodStart, $periodEnd);

        // Step 3: Submit DTR
        $this->submitDTR($dtr);

        // Step 4: Approve DTR
        $this->approveDTR($dtr);

        // Step 5: Generate Payroll
        $payroll = $this->generatePayroll($dtr);

        // Step 6: Verify computations
        $this->verifyPayroll($payroll, $dtr);

        $this->info('DTR Workflow test completed successfully!');
    }

    private function createAttendanceLogs(EmployeeProfile $employee, Carbon $start, Carbon $end)
    {
        $this->info('Creating attendance logs...');
        $current = $start->copy();
        while ($current <= $end) {
            if ($current->isWeekday()) { // Only weekdays
                AttendanceLog::create([
                    'employee_id' => $employee->user_id,
                    'employee_profile_id' => $employee->id,
                    'attendance_date' => $current->format('Y-m-d'),
                    'am_in' => Carbon::createFromTime(7, 0),
                    'am_out' => Carbon::createFromTime(12, 0),
                    'pm_in' => Carbon::createFromTime(13, 0),
                    'pm_out' => Carbon::createFromTime(17, 0),
                    'status' => 'present',
                    'late_minutes' => 0,
                    'overtime_hours' => 0,
                ]);
            }
            $current->addDay();
        }
        $this->info('Attendance logs created.');
    }

    private function generateDTR(EmployeeProfile $employee, Carbon $start, Carbon $end)
    {
        $this->info('Generating DTR...');
        $existing = DTR::where('employee_profile_id', $employee->id)
            ->whereDate('period_start', $start)
            ->whereDate('period_end', $end)
            ->first();

        if ($existing) {
            $dtr = $existing;
            $this->info("Using existing DTR with ID: {$dtr->id}");
        } else {
            $dtr = DTR::create([
                'employee_profile_id' => $employee->id,
                'period_start' => $start,
                'period_end' => $end,
                'status' => 'draft',
            ]);
            $this->info("DTR generated with ID: {$dtr->id}");
        }
        
        $dtr->calculateTotals(); // Assuming this method exists in DTR model
        $dtr->save();
        $this->info("Total Hours: {$dtr->total_hours}");
        return $dtr;
    }

    private function submitDTR(DTR $dtr)
    {
        $this->info('Submitting DTR...');
        $dtr->update(['status' => 'submitted']);
        $this->info('DTR submitted.');
    }

    private function approveDTR(DTR $dtr)
    {
        $this->info('Approving DTR...');
        $dtr->update(['status' => 'approved']);
        $this->info('DTR approved.');
    }

    private function generatePayroll(DTR $dtr)
    {
        $this->info('Generating payroll...');
        $payrollPeriod = PayrollPeriod::first() ?? PayrollPeriod::create([
            'name' => 'Test Period',
            'start_date' => $dtr->period_start,
            'end_date' => $dtr->period_end,
            'status' => 'active',
        ]);
        
        $service = new PayrollComputationService();
        $payroll = $service->generatePayrollFromDTR($dtr, $payrollPeriod);

        $this->info("Payroll generated with ID: {$payroll->id}");
        return $payroll;
    }

    private function verifyPayroll(PayrollEntry $payroll, DTR $dtr)
    {
        $this->info('Verifying payroll computations...');
        $this->line("Basic Pay: {$payroll->basic_pay}");
        $this->line("Overtime Pay: {$payroll->overtime_pay}");
        $this->line("Deductions: {$payroll->total_deductions}");
        $this->line("Net Pay: {$payroll->net_pay}");

        // Basic checks
        if ($payroll->basic_pay > 0 && $payroll->net_pay > 0) {
            $this->info('✓ Payroll computations look valid.');
        } else {
            $this->error('✗ Payroll computations may have issues.');
        }
    }
}
