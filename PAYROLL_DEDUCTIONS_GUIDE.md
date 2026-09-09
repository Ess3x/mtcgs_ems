# Payroll Mandatory Deductions Feature - Setup & Usage Guide

## 🎯 What Was Implemented

Your payroll system now has a **complete mandatory deductions management feature** where:

✅ **Employees can see** their mandatory deductions (SSS, PhilHealth, Pag-IBIG, Withholding Tax) calculated automatically based on their salary
✅ **Employees can add** optional/additional deductions (like extra Pag-IBIG, savings programs, union dues, loan payments)
✅ **Finance officers see** complete deduction breakdown when processing payroll
✅ **Deductions are dynamic** - they adjust automatically based on salary changes

---

## 📋 Files Created/Modified

### New Files:
1. **`app/Services/DeductionPreviewService.php`**
   - Calculates all mandatory deductions based on salary
   - Provides optional deduction examples
   - Handles tax calculations

2. **`app/Http/Controllers/Api/DeductionController.php`**
   - API endpoints for managing deductions
   - Handles adding, updating, removing deductions
   - Calculates estimated net pay with deductions

3. **`resources/views/employee/deductions.blade.php`**
   - Interactive deduction management interface
   - Shows mandatory deductions, current optional deductions
   - Modal for adding new deductions with validation

### Modified Files:
- **`routes/api.php`** - Added 5 new API endpoints
- **`resources/views/employee/dashboard.blade.php`** - Included deductions section

---

## 🚀 How to Use

### For Employees:
1. Log into the employee dashboard
2. Look for the **"Payroll Deductions & Benefits"** card (with calculator icon)
3. Click to expand and see:
   - **Mandatory Deductions** (automatically calculated from your ₱[salary])
   - **Your Additional Deductions** (what you've added)
4. To add an optional deduction:
   - Click the **"+ Add Deduction"** button
   - Select a deduction type from the cards shown
   - Enter the monthly amount (with validation)
   - Click **"Confirm & Add"**
5. Your deduction is saved immediately!

### Optional Deduction Types Available:
- **Additional Pag-IBIG** - Extra contributions to your Pag-IBIG savings (₱100-₱10,000/month)
- **Employee Savings Program** - Automatic savings deduction (₱100-₱50,000/month)
- **Union Dues** - Union membership payments (₱100-₱5,000/month)
- **Company Loan Payment** - Installment payments (₱500-₱100,000/month)

---

## 🔧 API Reference

All endpoints require authentication (`auth:sanctum`). Base URL: `/api`

### Get Deduction Preview
```
GET /api/deductions/preview

Response:
{
  "success": true,
  "employee": { ... },
  "mandatory_deductions": { ... },
  "total_mandatory": 1500.00,
  "optional_examples": [ ... ],
  "current_deductions": [ ... ],
  "total_current_additional": 500.00
}
```

### Add Deduction
```
POST /api/deductions/add
Content-Type: application/json

{
  "deduction_type": "additional_pagibig",
  "amount": 500,
  "description": "Extra Pag-IBIG for home savings"
}
```

### Update Deduction
```
PUT /api/deductions/{deductionId}
Content-Type: application/json

{
  "amount": 750,
  "description": "Updated description"
}
```

### Remove Deduction
```
DELETE /api/deductions/{deductionId}/remove
```

### Calculate Net Pay Estimate
```
POST /api/deductions/calculate-net-pay
Content-Type: application/json

{
  "gross_pay": 25000,
  "salary": 20000
}

Response:
{
  "success": true,
  "calculation": {
    "gross_pay": 25000,
    "mandatory_deductions": 4000,
    "additional_deductions": 500,
    "total_deductions": 4500,
    "net_pay": 20500
  }
}
```

---

## 🧮 Deduction Calculations

### Mandatory Deductions (Automatic):

1. **SSS (Social Security System)**
   - Based on salary brackets from `sss_contributions` table
   - Example: Salary ₱15,000 → SSS ₱375

2. **PhilHealth**
   - 3% of monthly salary
   - Minimum: ₱300, Maximum: ₱1,800
   - Employee pays 50% of total

3. **Pag-IBIG (Pagibig Fund)**
   - ₱0 if salary ≤ ₱1,500
   - ₱100 fixed if salary > ₱1,500

4. **Withholding Tax (BIR)**
   - Calculated on taxable income
   - Based on tax brackets from `tax_tables` table
   - Applied after mandatory deductions

### Formula for Net Pay:
```
Net Pay = Gross Pay - (Mandatory Deductions + Additional Deductions)
```

---

## 📊 Finance Officer View

When processing payroll in the Finance module:
- The payroll entry view shows:
  - Mandatory deductions breakdown
  - Section for "Optional/Additional Deductions"
  - Adjusted net pay calculation
  - Total of all deductions

The system automatically pulls each employee's active additional deductions when calculating final net pay.

---

## 🔒 Security Features

- ✅ All endpoints require user authentication
- ✅ Employees can only manage their own deductions
- ✅ Deduction amounts validated server-side (min/max checks)
- ✅ Only predefined deduction types allowed
- ✅ Soft delete (marks as inactive, preserves history)

---

## 🧪 Testing

All PHP code has been validated for syntax:
```
✅ DeductionPreviewService.php - No syntax errors
✅ DeductionController.php - No syntax errors  
✅ api.php routes - No syntax errors
✅ deductions.blade.php view - No syntax errors
✅ dashboard.blade.php - No syntax errors
```

---

## 📝 Database Tables Used

- `employee_additional_deductions` - Stores optional deductions
- `payroll_entries` - References additional deductions
- `sss_contributions` - SSS brackets
- `philhealth_contributions` - PhilHealth rates
- `pagibig_contributions` - Pag-IBIG rates
- `tax_tables` - Tax brackets

---

## 🎨 UI Features

The deduction interface includes:
- Collapsible card with summary statistics
- Color-coded cards for deduction types
- Real-time validation with helpful messages
- Loading states and error handling
- Responsive design for mobile/desktop
- Success notifications when actions complete

---

## 🚨 Troubleshooting

**Issue**: Deductions section not showing on employee dashboard
- **Solution**: Clear browser cache, refresh page

**Issue**: API returns 403 Unauthorized
- **Solution**: Ensure user is authenticated and role is 'employee'

**Issue**: Deduction amount validation failing
- **Solution**: Check that amount is between displayed min/max values

**Issue**: Changes not saving
- **Solution**: Check browser console for errors, verify CSRF token in HTML

---

## 📌 Notes

- Deduction calculations follow Philippine labor standards (as of 2026)
- All amounts displayed in Philippine Peso (₱)
- Changes to employee deductions take effect on next payroll run
- Optional deductions are applied after all mandatory deductions
- Employees can manage their deductions anytime (no approval needed)

---

## 🔗 Related Files

- Payroll Generation: `app/Http/Controllers/Finance/PayrollGenerationController.php`
- Payroll View: `resources/views/finance/payroll-generation/view-entry.blade.php`
- Computation Service: `app/Services/PayrollComputationService.php`

---

**Deployment Notes:**
- No database migrations needed (uses existing tables)
- No configuration changes required
- Service is automatically bound in Laravel's service provider
- Routes are in `api.php` so use `/api/deductions/...` endpoints
