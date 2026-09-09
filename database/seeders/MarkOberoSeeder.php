<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeProfile;
use App\Models\AttendanceLog;
use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use Carbon\Carbon;

class MarkOberoSeeder extends Seeder
{
    public function run()
    {
        // Create user account first - using FirstOrCreate to avoid duplicate errors
        $user = User::firstOrCreate(
            ['email' => 'mark.obero@test.com'],
            [
                'name' => 'Mark Obero',
                'password' => bcrypt('password123'),
                'role' => 'employee',
                'admin_type' => null,
                'id_verification_status' => 'approved'
            ]
        );

        // Create employee profile with complete information
        $profile = EmployeeProfile::firstOrCreate(
            ['employee_number' => 'EMP888'],
            [
                'user_id' => $user->id,
                'branch_id' => 1,
                'first_name' => 'Mark',
                'last_name' => 'Obero',
                'middle_name' => 'Cruz',
                'date_of_birth' => '1988-03-15',
                'gender' => 'Male',
                'civil_status' => 'Married',
                'position' => 'Senior Developer',
                'department' => 'IT Department',
                'employment_type' => 'Regular',
                'date_hired' => '2023-06-01',
                'basic_salary' => 35000.00,
                'hourly_rate' => 168.00,
                'contact_number' => '09187654321',
                'emergency_contact_name' => 'Anna Obero',
                'emergency_contact_number' => '09187654322',
                'address' => '456 Developer Street, Tech City',
                'is_fingerprint_registered' => true
            ]
        );

        // Update user with profile relationship
        $user->update([
            'profile_type' => EmployeeProfile::class,
            'profile_id' => $profile->id
        ]);

        // Create legacy employee record if needed
        $employee = Employee::firstOrCreate(
            ['employee_number' => 'EMP888'],
            [
                'user_id' => $user->id,
                'branch_id' => 1,
                'first_name' => 'Mark',
                'last_name' => 'Obero',
                'middle_name' => 'Cruz',
                'position' => 'Senior Developer',
                'department' => 'IT Department',
                'date_hired' => '2023-06-01',
                'basic_salary' => 35000.00,
                'contact_number' => '09187654321',
                'address' => '456 Developer Street, Tech City'
            ]
        );

        // Create attendance records for the current month (May 2026)
        $this->createAttendanceRecords($profile->id, $employee->id);

        // Create payroll period and entry
        $this->createPayrollData($profile->id);

        $this->command->info('Mark Obero test employee created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Email: mark.obero@test.com');
        $this->command->info('Password: password123');
    }

    private function createAttendanceRecords($profileId, $employeeId)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Skip weekends
            if ($date->isWeekend()) continue;

            // Generate random times
            $lateMinutes = rand(0, 15);
            $amInTime = $date->copy()->setTime(6 + floor($lateMinutes / 60), $lateMinutes % 60);
            $amOutTime = $date->copy()->setTime(12, rand(0, 59));
            $pmInTime = $date->copy()->setTime(13, rand(0, 59));
            $pmOutTime = $date->copy()->setTime(17, rand(0, 59));

            // Create attendance record with more realistic data
            AttendanceLog::create([
                'employee_id' => $employeeId,
                'employee_profile_id' => $profileId,
                'branch_id' => 1,
                'attendance_date' => $date->format('Y-m-d'),
                'am_in' => $amInTime,
                'am_out' => $amOutTime,
                'pm_in' => $pmInTime,
                'pm_out' => $pmOutTime,
                'status' => 'present',
                'late_minutes' => $lateMinutes,
            ]);
        }
    }

    private function createPayrollData($profileId)
    {
        // Use existing payroll period or create new one
        $payrollPeriod = PayrollPeriod::firstOrCreate(
            ['period_code' => 'MAY2026-01'],
            [
                'period_type' => 'monthly',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-31',
                'cutoff_date' => '2026-05-25',
                'payment_date' => '2026-05-31',
                'status' => 'completed'
            ]
        );

        // Calculate payroll components for higher salary
        $basicPay = 35000.00;
        $daysPresent = 22; // Approximate working days
        $overtimePay = 2500; // More overtime for senior developer
        $grossPay = $basicPay + $overtimePay + 1000; // Base pay + overtime + bonus

        // Calculate deductions for higher salary
        $sss = $this->calculateSSS($basicPay);
        $philhealth = $this->calculatePhilhealth($basicPay);
        $pagibig = $this->calculatePagibig($basicPay);
        $withholding_tax = $this->calculateTax($grossPay);

        $totalDeductions = $sss + $philhealth + $pagibig + $withholding_tax;
        $netPay = $grossPay - $totalDeductions;

        // Create payroll entry
        PayrollEntry::create([
            'payroll_period_id' => $payrollPeriod->id,
            'employee_profile_id' => $profileId,
            'branch_id' => 1,
            'basic_pay' => $basicPay,
            'overtime_pay' => $overtimePay,
            'overtime_hours' => 12.5,
            'days_present' => $daysPresent,
            'days_absent' => 0,
            'late_deduction' => 50,
            'allowances' => 2000, // Transportation allowance
            'bonuses' => 1000, // Performance bonus
            'gross_pay' => $grossPay,
            'sss_contribution' => $sss,
            'philhealth_contribution' => $philhealth,
            'pagibig_contribution' => $pagibig,
            'withholding_tax' => $withholding_tax,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'status' => 'approved'
        ]);
    }

    private function calculateSSS($salary)
    {
        // Simplified SSS calculation
        if ($salary <= 3250) return 135;
        if ($salary <= 3750) return 157.50;
        if ($salary <= 4250) return 180;
        if ($salary <= 4750) return 202.50;
        if ($salary <= 5250) return 225;
        if ($salary <= 5750) return 247.50;
        if ($salary <= 6250) return 270;
        if ($salary <= 6750) return 292.50;
        if ($salary <= 7250) return 315;
        if ($salary <= 7750) return 337.50;
        if ($salary <= 8250) return 360;
        if ($salary <= 8750) return 382.50;
        if ($salary <= 9250) return 405;
        if ($salary <= 9750) return 427.50;
        if ($salary <= 10250) return 450;
        if ($salary <= 10750) return 472.50;
        if ($salary <= 11250) return 495;
        if ($salary <= 11750) return 517.50;
        if ($salary <= 12250) return 540;
        if ($salary <= 12750) return 562.50;
        if ($salary <= 13250) return 585;
        if ($salary <= 13750) return 607.50;
        if ($salary <= 14250) return 630;
        if ($salary <= 14750) return 652.50;
        if ($salary <= 15250) return 675;
        if ($salary <= 15750) return 697.50;
        if ($salary <= 16250) return 720;
        if ($salary <= 16750) return 742.50;
        if ($salary <= 17250) return 765;
        if ($salary <= 17750) return 787.50;
        if ($salary <= 18250) return 810;
        if ($salary <= 18750) return 832.50;
        if ($salary <= 19250) return 855;
        if ($salary <= 19750) return 877.50;
        return 900; // Maximum SSS contribution
    }

    private function calculatePhilhealth($salary)
    {
        // PhilHealth is 4% of monthly salary, shared between employee and employer
        // Employee pays 2%
        $contribution = $salary * 0.02;

        // Cap at maximum contribution
        return min($contribution, 1800); // Maximum employee share
    }

    private function calculatePagibig($salary)
    {
        // Pag-IBIG is 2% of monthly salary for salary above 1500
        if ($salary <= 1500) return 0;

        $contribution = $salary * 0.02;
        return min($contribution, 100); // Maximum employee contribution
    }

    private function calculateTax($grossPay)
    {
        // Simplified tax calculation based on TRAIN Law
        $annualGross = $grossPay * 12;
        $annualTax = 0;

        if ($annualGross <= 250000) {
            $annualTax = 0;
        } elseif ($annualGross <= 400000) {
            $annualTax = ($annualGross - 250000) * 0.20;
        } elseif ($annualGross <= 800000) {
            $annualTax = 30000 + ($annualGross - 400000) * 0.25;
        } elseif ($annualGross <= 2000000) {
            $annualTax = 130000 + ($annualGross - 800000) * 0.30;
        } elseif ($annualGross <= 8000000) {
            $annualTax = 490000 + ($annualGross - 2000000) * 0.32;
        } else {
            $annualTax = 2410000 + ($annualGross - 8000000) * 0.35;
        }

        return $annualTax / 12; // Monthly tax
    }
}