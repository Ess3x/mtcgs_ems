<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAdditionalDeduction;
use App\Models\EmployeeProfile;
use App\Services\DeductionPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeductionController extends Controller
{
    protected $deductionService;

    public function __construct(DeductionPreviewService $deductionService)
    {
        $this->deductionService = $deductionService;
        $this->middleware('auth');
    }

    /**
     * Get deduction preview for employee based on their salary
     */
    public function getDeductionPreview()
    {
        $user = Auth::user();
        
        // Get employee profile
        if (!$user->profile || !($user->profile instanceof EmployeeProfile)) {
            return response()->json([
                'error' => 'Not an employee account',
            ], 403);
        }

        $employeeProfile = $user->profile;
        $salary = $employeeProfile->basic_salary ?? 0;

        // Get mandatory deductions preview
        $mandatoryDeductions = $this->deductionService->getMandatoryDeductionsPreview($salary);

        // Get optional deduction examples
        $optionalExamples = $this->deductionService->getOptionalDeductionExamples();

        // Get current additional deductions
        $currentDeductions = EmployeeAdditionalDeduction::where('employee_profile_id', $employeeProfile->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($deduction) {
                return [
                    'id' => $deduction->id,
                    'type' => $deduction->deduction_type,
                    'description' => $deduction->description,
                    'amount' => (float) $deduction->amount,
                ];
            });

        // Calculate total mandatory deductions
        $totalMandatory = array_sum(array_column($mandatoryDeductions, 'amount'));

        return response()->json([
            'success' => true,
            'employee' => [
                'name' => $employeeProfile->first_name . ' ' . $employeeProfile->last_name,
                'employee_number' => $employeeProfile->employee_number,
                'salary' => (float) $salary,
            ],
            'mandatory_deductions' => $mandatoryDeductions,
            'total_mandatory' => round($totalMandatory, 2),
            'optional_examples' => $optionalExamples,
            'current_deductions' => $currentDeductions,
            'total_current_additional' => $currentDeductions->sum('amount'),
        ]);
    }

    /**
     * Add a new optional deduction
     */
    public function addDeduction(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->profile || !($user->profile instanceof EmployeeProfile)) {
            return response()->json([
                'error' => 'Not an employee account',
            ], 403);
        }

        $validated = $request->validate([
            'deduction_type' => 'required|string|in:additional_pagibig,savings_deduction,union_dues,loan_deduction,other',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
        ]);

        $employeeProfile = $user->profile;

        try {
            // Check for duplicate active deduction of the same type
            $existing = EmployeeAdditionalDeduction::where('employee_profile_id', $employeeProfile->id)
                ->where('deduction_type', $validated['deduction_type'])
                ->where('is_active', true)
                ->first();

            if ($existing) {
                // Update the existing one instead of creating duplicate
                $existing->update([
                    'amount' => $validated['amount'],
                    'description' => $validated['description'] ?? $existing->description,
                ]);
                
                $deduction = $existing;
            } else {
                // Create new deduction
                $deduction = EmployeeAdditionalDeduction::create([
                    'employee_profile_id' => $employeeProfile->id,
                    'deduction_type' => $validated['deduction_type'],
                    'description' => $validated['description'] ?? $this->getDefaultDescription($validated['deduction_type']),
                    'amount' => $validated['amount'],
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Deduction added successfully',
                'deduction' => [
                    'id' => $deduction->id,
                    'type' => $deduction->deduction_type,
                    'description' => $deduction->description,
                    'amount' => (float) $deduction->amount,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add deduction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing optional deduction
     */
    public function updateDeduction(Request $request, $deductionId)
    {
        $user = Auth::user();
        
        if (!$user->profile || !($user->profile instanceof EmployeeProfile)) {
            return response()->json([
                'error' => 'Not an employee account',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $deduction = EmployeeAdditionalDeduction::findOrFail($deductionId);

            // Verify ownership
            if ($deduction->employee_profile_id !== $user->profile->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 403);
            }

            $deduction->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Deduction updated successfully',
                'deduction' => [
                    'id' => $deduction->id,
                    'type' => $deduction->deduction_type,
                    'description' => $deduction->description,
                    'amount' => (float) $deduction->amount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update deduction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove an optional deduction
     */
    public function removeDeduction($deductionId)
    {
        $user = Auth::user();
        
        if (!$user->profile || !($user->profile instanceof EmployeeProfile)) {
            return response()->json([
                'error' => 'Not an employee account',
            ], 403);
        }

        try {
            $deduction = EmployeeAdditionalDeduction::findOrFail($deductionId);

            // Verify ownership
            if ($deduction->employee_profile_id !== $user->profile->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Soft delete by marking as inactive
            $deduction->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Deduction removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove deduction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get default description for deduction type
     */
    private function getDefaultDescription($type)
    {
        $descriptions = [
            'additional_pagibig' => 'Additional Pag-IBIG Fund contribution',
            'savings_deduction' => 'Employee savings program',
            'union_dues' => 'Union membership dues',
            'loan_deduction' => 'Company loan payment',
            'other' => 'Additional deduction',
        ];

        return $descriptions[$type] ?? 'Additional deduction';
    }

    /**
     * Calculate estimated net pay with deductions
     */
    public function calculateEstimatedNetPay(Request $request)
    {
        $validated = $request->validate([
            'gross_pay' => 'required|numeric|min:0',
            'salary' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        
        if (!$user->profile || !($user->profile instanceof EmployeeProfile)) {
            return response()->json([
                'error' => 'Not an employee account',
            ], 403);
        }

        try {
            $grossPay = $validated['gross_pay'];
            $salary = $validated['salary'];

            // Get mandatory deductions
            $mandatoryDeductions = $this->deductionService->getMandatoryDeductionsPreview($salary);
            
            // Calculate withholding tax (after other deductions)
            $taxableIncome = $grossPay - array_sum(array_column($mandatoryDeductions, 'amount'));
            $withholdingTax = $this->deductionService->calculateWithholdingTax($taxableIncome);
            $mandatoryDeductions['withholding_tax']['amount'] = $withholdingTax;

            // Get employee's current additional deductions
            $additionalDeductions = EmployeeAdditionalDeduction::where('employee_profile_id', $user->profile->id)
                ->where('is_active', true)
                ->get();

            $totalMandatory = array_sum(array_column($mandatoryDeductions, 'amount'));
            $totalAdditional = $additionalDeductions->sum('amount');
            $totalDeductions = $totalMandatory + $totalAdditional;
            $netPay = $grossPay - $totalDeductions;

            return response()->json([
                'success' => true,
                'calculation' => [
                    'gross_pay' => round($grossPay, 2),
                    'mandatory_deductions' => round($totalMandatory, 2),
                    'additional_deductions' => round($totalAdditional, 2),
                    'total_deductions' => round($totalDeductions, 2),
                    'net_pay' => round($netPay, 2),
                ],
                'breakdown' => [
                    'mandatory' => $mandatoryDeductions,
                    'additional' => $additionalDeductions->map(function ($d) {
                        return [
                            'type' => $d->deduction_type,
                            'description' => $d->description,
                            'amount' => (float) $d->amount,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Calculation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
