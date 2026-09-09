# DTR to Payroll Workflow - Quick Reference

## 📊 Complete Process Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ EMPLOYEE PHASE - Time Tracking & DTR Submission                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Daily Time-In/Out (Biometric)                               │
│     ↓                                                            │
│  2. System auto-creates/updates DTR for 15-day period           │
│     ↓                                                            │
│  3. Employee views DTR on Dashboard                             │
│     ├─ See daily records                                        │
│     ├─ View hours worked                                        │
│     └─ Check absences/late                                      │
│     ↓                                                            │
│  4. Employee Submits DTR [Status: Submitted]                    │
│     ↓                                                            │
│  5. Awaits Admin Review                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ ADMIN PHASE - DTR Approval                                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Admin/Manager views pending DTRs                            │
│     Location: /admin/dtr-management                             │
│     ↓                                                            │
│  2. Click "Review" on employee's DTR                            │
│     ↓                                                            │
│  3. Check daily attendance records                              │
│     ├─ Verify time entries                                      │
│     ├─ Check late/overtime                                      │
│     └─ Review absences                                          │
│     ↓                                                            │
│  4. Action:                                                      │
│     ├─ ✅ APPROVE → Status: Approved                            │
│     │                                                           │
│     └─ ❌ REJECT  → Status: Draft + Send Remarks                │
│        (Employee can resubmit)                                  │
│     ↓                                                            │
│  5. Send notification to Finance Officer                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ FINANCE OFFICER PHASE - Payroll Generation & Deductions         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. View Ready DTRs                                              │
│     Location: /finance/payroll-generation/ready-dtrs            │
│     ↓                                                            │
│  2. Select Payroll Period                                        │
│     ├─ Individual generation: Pick DTR + Period                 │
│     └─ Batch generation: Entire period at once                  │
│     ↓                                                            │
│  3. SYSTEM AUTO-CALCULATES:                                     │
│     ├─ Basic Pay = Salary ÷ 22 × Days Worked                    │
│     ├─ Overtime = (Daily ÷ 8) × OT Hours × 1.25                │
│     ├─ Late Deduction = Based on late minutes                   │
│     ├─ Absent Deduction = Daily Rate × Days Absent             │
│     ├─ Approved Leaves = Paid leave days added                  │
│     ├─ SSS Contribution = Auto from salary bracket             │
│     ├─ PhilHealth = Salary × 3% (min ₱300, max ₱1,800)        │
│     ├─ Pag-IBIG = ₱100 (if salary > ₱1,500)                    │
│     ├─ Withholding Tax = Tax bracket calculation               │
│     └─ NET PAY = Gross - Deductions                             │
│     ↓                                                            │
│  4. PayrollEntry Created [Status: Draft]                        │
│     ↓                                                            │
│  5. Review & Adjust Entry                                        │
│     Location: /finance/payroll-generation/entry/{id}            │
│     ├─ See detailed breakdown                                   │
│     ├─ Can adjust allowances/bonuses                            │
│     ├─ Can modify deductions if needed                          │
│     ├─ System recalculates automatically                        │
│     └─ JSON breakdown saved for audit                           │
│     ↓                                                            │
│  6. Finalize Entry                                               │
│     └─ Status: Draft → Processed                                │
│     ↓                                                            │
│  7. View Period Summary                                          │
│     ├─ Total gross pay                                          │
│     ├─ Total deductions breakdown                               │
│     ├─ Total net pay                                            │
│     └─ All entries ready for approval                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ MANAGEMENT PHASE - Approval & Payslips                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. View processed payroll for period                           │
│     ↓                                                            │
│  2. Approve/Reject entries                                      │
│     ↓                                                            │
│  3. Generate Payslips                                            │
│     └─ Show employee take-home and deductions                   │
│     ↓                                                            │
│  4. Employee views Payslip on /my-payslips                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔢 Calculation Example: From DTR to Payslip

### Input: DTR Records
```
Employee: Juan Dela Cruz
Salary: ₱10,000/month
Period: May 1-15, 2026

DTR Data:
✓ Days Present: 14
✗ Days Absent: 1
⏰ Overtime Hours: 4
⏱️ Late Minutes: 30
🎓 Approved Leaves: 0
```

### System Processing (AUTO)

#### 1️⃣ CALCULATE INCOME
```
Daily Rate = ₱10,000 ÷ 22 = ₱454.54/day
Hourly Rate = ₱454.54 ÷ 8 = ₱56.82/hour

Basic Pay = ₱454.54 × 14 days = ₱6,363.56
Overtime = ₱56.82 × 4 hrs × 1.25 = ₱284.11
SUBTOTAL = ₱6,647.67
```

#### 2️⃣ CALCULATE DEDUCTIONS (from DTR)
```
Late Minutes: 30 min = 2 × 15min = 0.5 day
Late Deduction = ₱454.54 × (0.5 ÷ 8) = -₱28.41

Absent Days: 1 day
Absent Deduction = ₱454.54 × 1 = -₱454.54

SUBTOTAL DEDUCTIONS = -₱482.95
```

#### 3️⃣ GROSS PAY
```
Gross = 6,647.67 - 482.95 = ₱6,164.72
```

#### 4️⃣ GOVERNMENT CONTRIBUTIONS (AUTO-DEDUCTED)
```
SSS = ₱300.00 (based on salary bracket)
PhilHealth = (10,000 × 3%) ÷ 2 = ₱150.00
Pag-IBIG = ₱100.00 (fixed for salary > 1,500)
Withholding Tax = ₱400.00 (based on tax bracket)

TOTAL CONTRIBUTIONS = -₱950.00
```

#### 5️⃣ FINAL NET PAY
```
NET PAY = 6,164.72 - 950.00 = ₱5,214.72
```

### Output: Payslip
```
═══════════════════════════════════════════
        MONTHLY PAYSLIP (May 1-15, 2026)
═══════════════════════════════════════════

EMPLOYEE: Juan Dela Cruz
ID: EMP-001
POSITION: Sales Officer

INCOME:
  Basic Pay (14 days)      ₱6,363.56
  Overtime (4 hrs @ 1.25x) ₱  284.11
  ────────────────────────────────
  GROSS PAY                ₱6,647.67

DEDUCTIONS:
  Late Deduction           -₱   28.41
  Absent (1 day)           -₱  454.54
  ────────────────────────────────
  Subtotal                 -₱  482.95

GOVERNMENT DEDUCTIONS:
  SSS Contribution         -₱  300.00
  PhilHealth               -₱  150.00
  Pag-IBIG                 -₱  100.00
  Withholding Tax (BIR)    -₱  400.00
  ────────────────────────────────
  Total Government         -₱  950.00

TOTAL DEDUCTIONS:          -₱1,432.95

═══════════════════════════════════════════
NET PAY (Take Home):       ₱5,214.72
═══════════════════════════════════════════
```

---

## 🔗 Key Links for Each Role

### 👤 Employee
- View/Submit DTR: `/dtr`
- DTR Summary: `/dtr/summary`
- View Payslips: `/my-payslips`

### 👨‍💼 Admin/Manager
- Manage DTRs: `/admin/dtr-management`
- Review DTR: `/admin/dtr/{dtr}`
- Approve/Reject DTR: Form on review page

### 💰 Finance Officer
- Ready DTRs: `/finance/payroll-generation/ready-dtrs`
- Generate Payroll: Form on ready DTRs page
- Review Entry: `/finance/payroll-generation/entry/{entry}`
- Period Summary: `/finance/payroll-generation/period/{id}/summary`

---

## ⚙️ Automatic Features

### ✅ System Does This Automatically

1. **DTR Auto-Creation**
   - Creates new DTR when employee first times-in
   - Covers 15-day period (1-15 or 16-end of month)

2. **Payroll Auto-Calculation**
   - Calculates all components from DTR
   - No manual data entry needed
   - Recalculates if amounts adjusted

3. **Deduction Auto-Application**
   - SSS, PhilHealth, Pag-ibig all auto-deducted
   - Tax calculated automatically
   - Late and absent tracked automatically

4. **JSON Breakdown Storage**
   - All calculations stored for audit
   - Complete calculation trail preserved
   - Links to original DTR

---

## 📋 Status Workflow

```
DTR:
Draft ──Submit──> Submitted ──Approve──> Approved ──Generate──> ✓ Payroll Generated
                      │                                              │
                      └──────────Reject─────────────────────────────┘
                      (Remarks sent to employee for correction)

PayrollEntry:
Draft ──Review──> Draft ──Finalize──> Processed ──Approve──> Approved ──Generate──> Payslip
       (Adjust)                             │                            │
                                            └────────Reject──────────────┘
```

---

## 🎯 Summary

| Phase | Owner | Action | Input | Output |
|-------|-------|--------|-------|--------|
| 1 | Employee | Time-in/out | Biometric | DTR record |
| 2 | Admin | Review DTR | Attendance data | Approved DTR |
| 3 | Finance | Generate payroll | Approved DTR | Payroll Entry |
| 4 | Finance | Review & adjust | Payroll Entry | Processed Entry |
| 5 | Management | Approve payroll | Processed Entry | Approved Payroll |
| 6 | System | Generate payslip | Approved Payroll | Payslip |

---

**Status**: ✅ Complete & Operational
