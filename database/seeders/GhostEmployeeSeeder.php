<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\AttendanceLog;
use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\TaxTable;
use Carbon\Carbon;

class GhostEmployeeSeeder extends Seeder
{
    public function run()
    {
        // Define ghost employees data
        $ghostEmployees = [
            // Iriga Branch (branch_id = 1)
            [
                'email' => 'ghost.iriga1@mtcgs.edu.ph',
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'employee_number' => 'EMP-GHOST-IRIGA-001',
                'branch_id' => 1,
                'position' => 'Software Developer',
                'department' => 'IT Department',
                'basic_salary' => 25000.00,
            ],
            [
                'email' => 'ghost.iriga2@mtcgs.edu.ph',
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'employee_number' => 'EMP-GHOST-IRIGA-002',
                'branch_id' => 1,
                'position' => 'Teacher',
                'department' => 'Academics',
                'basic_salary' => 22000.00,
            ],
            [
                'email' => 'ghost.iriga3@mtcgs.edu.ph',
                'first_name' => 'Pedro',
                'last_name' => 'Garcia',
                'employee_number' => 'EMP-GHOST-IRIGA-003',
                'branch_id' => 1,
                'position' => 'Accountant',
                'department' => 'Finance',
                'basic_salary' => 20000.00,
            ],
            [
                'email' => 'ghost.iriga4@mtcgs.edu.ph',
                'first_name' => 'Ana',
                'last_name' => 'Rodriguez',
                'employee_number' => 'EMP-GHOST-IRIGA-004',
                'branch_id' => 1,
                'position' => 'Librarian',
                'department' => 'Library',
                'basic_salary' => 18000.00,
            ],
            [
                'email' => 'ghost.iriga5@mtcgs.edu.ph',
                'first_name' => 'Carlos',
                'last_name' => 'Martinez',
                'employee_number' => 'EMP-GHOST-IRIGA-005',
                'branch_id' => 1,
                'position' => 'Security Guard',
                'department' => 'Security',
                'basic_salary' => 15000.00,
            ],
            // Buhi Branch (branch_id = 2)
            [
                'email' => 'ghost.buhi1@mtcgs.edu.ph',
                'first_name' => 'Rosa',
                'last_name' => 'Lopez',
                'employee_number' => 'EMP-GHOST-BUHI-001',
                'branch_id' => 2,
                'position' => 'Software Developer',
                'department' => 'IT Department',
                'basic_salary' => 25000.00,
            ],
            [
                'email' => 'ghost.buhi2@mtcgs.edu.ph',
                'first_name' => 'Miguel',
                'last_name' => 'Torres',
                'employee_number' => 'EMP-GHOST-BUHI-002',
                'branch_id' => 2,
                'position' => 'Teacher',
                'department' => 'Academics',
                'basic_salary' => 22000.00,
            ],
            [
                'email' => 'ghost.buhi3@mtcgs.edu.ph',
                'first_name' => 'Elena',
                'last_name' => 'Fernandez',
                'employee_number' => 'EMP-GHOST-BUHI-003',
                'branch_id' => 2,
                'position' => 'Accountant',
                'department' => 'Finance',
                'basic_salary' => 20000.00,
            ],
            [
                'email' => 'ghost.buhi4@mtcgs.edu.ph',
                'first_name' => 'Antonio',
                'last_name' => 'Morales',
                'employee_number' => 'EMP-GHOST-BUHI-004',
                'branch_id' => 2,
                'position' => 'Librarian',
                'department' => 'Library',
                'basic_salary' => 18000.00,
            ],
            [
                'email' => 'ghost.buhi5@mtcgs.edu.ph',
                'first_name' => 'Isabel',
                'last_name' => 'Ramirez',
                'employee_number' => 'EMP-GHOST-BUHI-005',
                'branch_id' => 2,
                'position' => 'Security Guard',
                'department' => 'Security',
                'basic_salary' => 15000.00,
            ],
        ];

        $password = 'password123'; // Same password for all ghost employees

        foreach ($ghostEmployees as $employeeData) {
            $this->createGhostEmployee($employeeData, $password);
        }

        $this->command->info('10 Ghost Employees created successfully!');
        $this->command->info('All ghost employees use password: ' . $password);
        $this->command->info('Emails: ghost.iriga1@mtcgs.edu.ph through ghost.iriga5@mtcgs.edu.ph (Iriga)');
        $this->command->info('Emails: ghost.buhi1@mtcgs.edu.ph through ghost.buhi5@mtcgs.edu.ph (Buhi)');
    }

    private function createGhostEmployee($data, $password)
    {
        // Create user account first - using FirstOrCreate to avoid duplicate errors
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'password' => bcrypt($password),
                'role' => 'employee',
                'branch_id' => $data['branch_id'],
                'id_verification_status' => 'approved',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create employee profile with complete information
        $profile = EmployeeProfile::firstOrCreate(
            ['employee_number' => $data['employee_number']],
            [
                'user_id' => $user->id,
                'branch_id' => $data['branch_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => 'Test',
                'date_of_birth' => '1990-01-15',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'position' => $data['position'],
                'department' => $data['department'],
                'employment_type' => 'Regular',
                'date_hired' => '2024-01-01',
                'basic_salary' => $data['basic_salary'],
                'is_fingerprint_registered' => true,
                'fingerprint_template' => 'ghost_fingerprint_template_' . $data['employee_number'] . '_' . time(),
            ]
        );

        // Update user profile references
        $user->profile_id = $profile->id;
        $user->profile_type = EmployeeProfile::class;
        $user->save();

        // Create attendance records for the current month (May 2026)
        $this->createAttendanceRecords($profile->id, $user->id);

        // Create payroll period and entry
        $this->createPayrollData($profile->id, $data['branch_id'], $data['basic_salary']);

        $this->command->info('Created: ' . $data['first_name'] . ' ' . $data['last_name'] . ' (' . $data['email'] . ')');
    }

    private function createAttendanceRecords($employeeProfileId, $userId)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $daysInMonth = Carbon::now()->daysInMonth;

        for ($day = 1; $day <= min($daysInMonth, Carbon::now()->day); $day++) {
            $date = Carbon::create($currentYear, $currentMonth, $day);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Create attendance record with realistic times
            AttendanceLog::firstOrCreate(
                [
                    'employee_profile_id' => $employeeProfileId,
                    'attendance_date' => $date
                ],
                [
                    'branch_id' => 1, // Default to Iriga, will be updated if needed
                    'employee_id' => $userId,
                    'am_in' => $date->setTime(8, rand(0, 15), 0), // 8:00-8:15 AM
                    'am_out' => $date->setTime(12, rand(0, 30), 0), // 12:00-12:30 PM
                    'pm_in' => $date->setTime(13, rand(0, 30), 0), // 1:00-1:30 PM
                    'pm_out' => $date->setTime(17, rand(0, 60), 0), // 5:00-6:00 PM
                    'status' => 'Complete',
                    'late_minutes' => rand(0, 15), // Random late minutes
                    'overtime_hours' => rand(0, 2), // Random overtime
                ]
            );
        }
    }

    private function createPayrollData($employeeProfileId, $branchId, $basicSalary)
    {
        // Create payroll period for current month
        $payrollPeriod = PayrollPeriod::firstOrCreate(
            [
                'period_code' => 'GHOST-MAY2026-' . $branchId,
            ],
            [
                'period_type' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => Carbon::now()->endOfMonth(),
                'cutoff_date' => Carbon::now()->endOfMonth(),
                'payment_date' => Carbon::now()->endOfMonth()->addDays(5),
                'status' => 'completed'
            ]
        );

        // Calculate payroll components
        $daysPresent = 22; // Assuming 22 working days present
        $daysAbsent = 0;
        $overtimeHours = rand(0, 8); // Random overtime 0-8 hours
        $overtimeRate = 1.25; // 125% of regular rate
        $dailyRate = $basicSalary / 22;
        $overtimePay = ($dailyRate / 8) * $overtimeRate * $overtimeHours;

        $basicPay = $dailyRate * $daysPresent;
        $grossPay = $basicPay + $overtimePay;

        // Government deductions (sample rates)
        $sssContribution = 900.00;
        $philhealthContribution = 500.00;
        $pagibigContribution = 100.00;

        // Tax calculation (simplified)
        $taxableIncome = $grossPay - ($sssContribution + $philhealthContribution + $pagibigContribution);
        $withholdingTax = $taxableIncome * 0.15; // 15% tax rate

        $totalDeductions = $sssContribution + $philhealthContribution + $pagibigContribution + $withholdingTax;
        $netPay = $grossPay - $totalDeductions;

        // Create payroll entry
        PayrollEntry::firstOrCreate(
            [
                'payroll_period_id' => $payrollPeriod->id,
                'employee_profile_id' => $employeeProfileId,
            ],
            [
                'branch_id' => $branchId,
                'basic_pay' => $basicPay,
                'overtime_pay' => $overtimePay,
                'overtime_hours' => $overtimeHours,
                'days_present' => $daysPresent,
                'days_absent' => $daysAbsent,
                'late_deduction' => 0,
                'allowances' => 0,
                'bonuses' => 0,
                'gross_pay' => $grossPay,
                'sss_contribution' => $sssContribution,
                'philhealth_contribution' => $philhealthContribution,
                'pagibig_contribution' => $pagibigContribution,
                'withholding_tax' => $withholdingTax,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'status' => 'calculated', // Set to calculated so Finance can edit
            ]
        );
    }
}