# DTR to Payroll Integration - Complete Workflow Guide

## Overview
The DTR (Daily Time Record) system is now fully integrated with the Payroll system. Employees submit their DTRs, admins approve them, and Finance Officers automatically generate payroll entries with all deductions calculated.

## Complete Workflow

### STEP 1: Employee Submits DTR
**Location**: `/dtr` (Employee Dashboard)

1. Employee views their DTR for the current 15-day period
2. Reviews all daily time entries (time-in, time-out)
3. Clicks "Submit for Approval" when ready
4. DTR status changes from **Draft** → **Submitted**

**Data Tracked in DTR**:
- Days Present / Days Absent
- Total Hours Worked
- Overtime Hours (tracked from time records)
- Late Minutes (tracked from time-in)
- Period: 1-15 or 16-end of month

---

### STEP 2: Admin/Manager Reviews & Approves DTR
**Location**: `/admin/dtr-management`

1. Admin views all submitted DTRs in dashboard
2. Clicks "Review" on employee's DTR
3. Checks daily attendance records
4. Options:
   - **Approve DTR** → Status becomes **Approved**
   - **Reject DTR** → Sends back to employee with remarks

**After Approval**: DTR is ready for payroll generation

---

### STEP 3: Finance Officer Generates Payroll from DTR
**Location**: `/finance/payroll-generation/ready-dtrs`

#### Option A: Generate Individual Payroll
1. Finance Officer sees all approved DTRs
2. Selects a DTR and chooses a Payroll Period
3. System auto-generates Payroll Entry with:
   - ✅ Basic Pay calculation
   - ✅ Overtime Pay calculation
   - ✅ Absent deductions
   - ✅ Late deductions
   - ✅ Approved leave additions
   - ✅ Government deductions (auto-calculated)
   - ✅ Withholding Tax (auto-calculated)

#### Option B: Batch Generate for Entire Period
1. Select Payroll Period
2. Click "Generate All for Selected Period"
3. System generates payroll for all approved DTRs in that period at once

---

## AUTO-CALCULATIONS IN PAYROLL ENTRY

### Income Calculations
| Component | Formula | Notes |
|-----------|---------|-------|
| **Basic Pay** | Daily Rate × Days Worked | Daily Rate = Basic Salary ÷ 22 |
| **Overtime Pay** | Hourly Rate × OT Hours × 1.25 | 25% premium on hourly rate |
| **Approved Leaves** | Daily Rate × Approved Leave Days | Added to gross pay (paid leaves) |
| **Gross Pay** | Basic + Overtime + Leaves - Late - Absent | Total before deductions |

### Deduction Calculations
| Deduction | Formula | Notes |
|-----------|---------|-------|
| **Late Deduction** | Per 15 min late = 0.25 day deduction | Automatic from DTR |
| **Absent Deduction** | Daily Rate × Days Absent | Days not worked |
| **SSS Contribution** | Based on salary bracket | Auto-deducted from salary bracket |
| **PhilHealth** | Salary × 3% (Min: ₱300, Max: ₱1,800) | Employee share = 50% |
| **Pag-IBIG** | ₱100 (if salary > ₱1,500) | Fixed amount |
| **Withholding Tax** | Based on tax bracket & taxable income | BIR calculation |
| **Total Deductions** | SSS + PhilHealth + Pag-ibig + Tax + Late + Absent | |

### Net Pay Calculation
```
Gross Pay - Total Deductions = Net Pay
```

---

## DETAILED BREAKDOWN IN PAYROLL ENTRY

When Finance Officer reviews a payroll entry, they see:

```
INCOME SECTION
├─ Basic Pay (X days × Daily Rate)
├─ Overtime (X hours × Hourly Rate × 1.25)
├─ Approved Leaves (Paid leave days)
└─ GROSS PAY

DEDUCTION SECTION
├─ Late Deductions (X minutes → days)
├─ Absent Deductions (X days)
├─ Leave Deductions (if applicable)
├─ Government Contributions
│  ├─ SSS Contribution
│  ├─ PhilHealth
│  ├─ Pag-IBIG
│  └─ Withholding Tax
└─ TOTAL DEDUCTIONS

FINAL
├─ NET PAY (Gross - Deductions)
└─ PAYSLIP (Employee take-home)
```

---

## FINANCE OFFICER WORKFLOW

### 1. Review Ready DTRs
- Go to `/finance/payroll-generation/ready-dtrs`
- See all approved DTRs ready for payroll
- Filter by payroll period

### 2. Generate Payroll Entry
- Select DTR and Payroll Period
- System auto-calculates all values
- Entry created in **Draft** status

### 3. Review/Adjust Entry
- Go to `/finance/payroll-generation/entry/{id}`
- View detailed breakdown of all calculations
- Can adjust:
  - Allowances (additional benefits)
  - Bonuses (special incentives)
  - Deduction amounts (if needed)
- System recalculates Net Pay automatically

### 4. Finalize Entry
- Click "Finalize Entry" button
- Status changes from **Draft** → **Processed**
- Ready for approval by management

### 5. View Period Summary
- Go to `/finance/payroll-generation/period/{id}/summary`
- See all payroll entries for period
- View total statistics:
  - Total Gross Pay
  - Total Deductions breakdown
  - Total Net Pay

---

## FILES CREATED / MODIFIED

### New Files Created
1. **Migration**: `2026_05_11_add_dtr_to_payroll_entries.php`
   - Adds `dtr_id` foreign key to payroll_entries
   - Adds `absent_deduction`, `leave_deduction` columns
   - Adds `payroll_breakdown` JSON column

2. **Service**: `app/Services/PayrollComputationService.php`
   - Auto-calculates all payroll components from DTR
   - Handles SSS, PhilHealth, Pag-ibig, Tax calculations

3. **Controller**: `app/Http/Controllers/Finance/PayrollGenerationController.php`
   - Manages payroll generation from DTRs
   - Handles entry review, adjustment, finalization

4. **Views**: 
   - `finance/payroll-generation/ready-dtrs.blade.php`
   - `finance/payroll-generation/view-entry.blade.php`
   - `finance/payroll-generation/summary.blade.php`

### Modified Files
1. **Model**: `app/Models/PayrollEntry.php`
   - Added DTR relationship
   - Added new fillable columns

2. **Model**: `app/Models/DTR.php`
   - Added payrollEntry() relationship

3. **Routes**: `routes/web.php`
   - Added Finance Officer payroll generation routes

---

## KEY FEATURES

✅ **Automatic Calculations**
- All payroll components auto-calculated from DTR
- No manual data entry needed

✅ **Complete Deduction Suite**
- SSS, PhilHealth, Pag-ibig all auto-deducted
- Withholding Tax calculated based on income
- Late and absent deductions from DTR records

✅ **Flexible Adjustment**
- Finance Officer can adjust allowances/bonuses
- Can modify deduction amounts if needed
- System recalculates Net Pay automatically

✅ **Complete Audit Trail**
- JSON breakdown stored for each payroll entry
- Tracks all calculations for reference
- Links to original DTR records

✅ **Batch Processing**
- Generate payroll for entire period at once
- Or generate individual entries

---

## USAGE STEPS

### For Employees
1. ✅ Time-in and Time-out (auto-recorded)
2. ✅ View DTR on dashboard
3. ✅ Submit DTR when complete

### For Admins
1. ✅ Review submitted DTRs
2. ✅ Approve or reject with remarks

### For Finance Officers
1. ✅ Go to Payroll Generation
2. ✅ Generate from approved DTRs
3. ✅ Review and adjust if needed
4. ✅ Finalize entries
5. ✅ View period summary

### For Management
1. ✅ Approve processed payroll
2. ✅ Generate payslips for employees

---

## DATABASE SCHEMA

### New Columns Added to `payroll_entries`
```
- dtr_id (FK) → dtrs.id
- absent_deduction (decimal)
- leave_deduction (decimal)
- payroll_breakdown (JSON)
```

### Sample DTR → Payroll Entry Link
```
DTR (1-15 May)
├─ Days Present: 10
├─ Days Absent: 3
├─ Overtime Hours: 5
└─ Late Minutes: 45

↓ GENERATES ↓

PayrollEntry
├─ Basic Pay: ₱5,000
├─ Overtime Pay: ₱500
├─ Late Deduction: -₱50
├─ Absent Deduction: -₱1,500
├─ SSS: -₱300
├─ PhilHealth: -₱150
├─ Pag-ibig: -₱100
├─ Tax: -₱500
├─ Total Deductions: -₱2,600
└─ NET PAY: ₱2,400
```

---

## ROUTES ADDED

```
POST   /finance/payroll-generation/dtr/{dtr}/generate
POST   /finance/payroll-generation/period/{period}/generate
GET    /finance/payroll-generation/ready-dtrs
GET    /finance/payroll-generation/entry/{entry}
PUT    /finance/payroll-generation/entry/{entry}
POST   /finance/payroll-generation/entry/{entry}/finalize
GET    /finance/payroll-generation/period/{period}/summary
```

---

## CALCULATION EXAMPLES

### Example 1: Regular Employee
```
DTR Period: May 1-15
Salary: ₱10,000/month
Daily Rate: ₱454.54 (10,000 ÷ 22 days)

DTR Records:
- Days Present: 14
- Days Absent: 1
- Overtime: 3 hours
- Late: 30 minutes (2 × 15min = 0.5 day deduction)

CALCULATION:
Basic Pay = 454.54 × 14 = ₱6,363.56
Overtime = (454.54 ÷ 8) × 3 × 1.25 = ₱212.74
Late Deduction = 454.54 × 0.5 ÷ 8 = -₱28.41
Absent Deduction = 454.54 × 1 = -₱454.54
Gross Pay = 6,363.56 + 212.74 - 28.41 - 454.54 = ₱6,093.35

Deductions:
SSS (bracket) = -₱300
PhilHealth = -₱150
Pag-ibig = -₱100
Tax = -₱450
Total Ded = -₱1,000

NET PAY = 6,093.35 - 1,000 = ₱5,093.35
```

### Example 2: With Approved Leaves
```
DTR Period: May 16-31
Salary: ₱15,000/month
Daily Rate: ₱681.81

DTR Records:
- Days Present: 11
- Days Absent: 0
- Approved Leaves: 4 (VL)
- Overtime: 8 hours

CALCULATION:
Basic = 681.81 × 11 = ₱7,499.91
Leaves = 681.81 × 4 = ₱2,727.24 (PAID)
Overtime = (681.81 ÷ 8) × 8 × 1.25 = ₱851.26
Gross = 7,499.91 + 2,727.24 + 851.26 = ₱11,078.41

Deductions:
SSS = -₱450
PhilHealth = -₱225
Pag-ibig = -₱100
Tax = -₱1,200
Total = -₱1,975

NET PAY = 11,078.41 - 1,975 = ₱9,103.41
```

---

## Status: ✅ Complete and Ready to Use
**Date Implemented**: May 11, 2026
**Integrated With**: DTR System, Payroll Period Management
