# DTR (Daily Time Record) Feature - Implementation Summary

## What Has Been Created

A complete biweekly Daily Time Record (DTR) system that automatically tracks employee time-in and time-out records.

### Key Features

1. **Automatic DTR Generation**
   - DTR periods are automatically created for the 1st-15th and 16th-end of each month
   - DTRs are generated automatically when employees clock in/out
   - Each employee has their own DTR for each 15-day period

2. **Employee Features**
   - View current DTR period on the Employee Dashboard
   - Access DTR list showing all past and present DTR periods
   - View detailed DTR with daily breakdown of time entries
   - Submit DTR for approval
   - View DTR summary with performance comparison charts
   - Export DTR as PDF
   - Automatic calculation of:
     - Total hours worked
     - Days present/absent
     - Late minutes
     - Overtime hours

3. **Admin/Finance Features**
   - DTR Management Dashboard
   - View all pending DTRs awaiting approval
   - Review detailed DTR records
   - Approve or Reject DTRs
   - Send feedback remarks to employees

### Files Created

#### Models
- `app/Models/DTR.php` - DTR model with all calculation methods

#### Controllers
- `app/Http/Controllers/DTRController.php` - Employee DTR operations
- `app/Http/Controllers/Admin/DTRManagementController.php` - Admin DTR approval
- `app/Http/Controllers/Api/AttendanceController.php` - Updated to auto-generate DTR

#### Views (Employee)
- `resources/views/employee/dtr/index.blade.php` - DTR list view
- `resources/views/employee/dtr/show.blade.php` - DTR details view
- `resources/views/employee/dtr/summary.blade.php` - DTR summary with charts
- `resources/views/employee/dtr/pdf.blade.php` - PDF export view

#### Views (Admin)
- `resources/views/admin/dtr/index.blade.php` - DTR management dashboard
- `resources/views/admin/dtr/show.blade.php` - DTR review & approval view

#### Database
- `database/migrations/2026_05_11_create_dtr_table.php` - DTR table migration

#### Routes
- Added employee DTR routes under `/dtr` prefix
- Added admin DTR management routes under `/admin/dtr-management`

### Database Schema

The `dtrs` table includes:
- `id` - Primary key
- `employee_profile_id` - Link to employee
- `period_start` - Start date (1st or 16th of month)
- `period_end` - End date (15th or last day of month)
- `status` - draft, submitted, approved, or rejected
- `remarks` - Additional notes
- `created_at`, `updated_at`, `deleted_at` - Timestamps

### Routes Created

**Employee Routes:**
- `GET /dtr` - View DTR list
- `GET /dtr/summary` - View DTR summary
- `GET /dtr/{dtr}` - View specific DTR details
- `POST /dtr/{dtr}/submit` - Submit DTR for approval
- `GET /dtr/{dtr}/export-pdf` - Export DTR as PDF

**Admin Routes:**
- `GET /admin/dtr-management` - DTR management dashboard
- `GET /admin/dtr/{dtr}` - Review DTR
- `POST /admin/dtr/{dtr}/approve` - Approve DTR
- `POST /admin/dtr/{dtr}/reject` - Reject DTR
- `GET /admin/employee/{employee}/dtrs` - View employee's all DTRs

### How It Works

1. **Automatic DTR Creation**: When an employee clocks in (am_in), the system automatically creates/updates the current period DTR
2. **Attendance Tracking**: All time-in/time-out records are automatically linked to the DTR period
3. **Auto Calculations**: System calculates hours, late minutes, overtime automatically
4. **Employee Submission**: Employees can view and submit their DTR for approval
5. **Admin Approval**: Admins review and approve/reject DTRs

### Usage Instructions

**For Employees:**
1. Go to Dashboard → DTR section
2. View current DTR period
3. Click "View DTR Details & Submit"
4. Review all daily records
5. Click "Submit for Approval" when ready
6. View summary and export PDF if needed

**For Admins:**
1. Go to Admin → DTR Management
2. View pending DTRs in dashboard
3. Click "Review" on any DTR
4. Check attendance records
5. Click "Approve DTR" or "Reject DTR"
6. Add remarks if rejecting

### Calculations Included

- **Total Hours Worked**: Sum of (AM out - AM in) + (PM out - PM in)
- **Days Present**: Count of days with AM in recorded
- **Days Absent**: Working days - Days present
- **Late Minutes**: Difference between 8:00 AM and actual AM in time
- **Overtime Hours**: Difference between 5:00 PM and actual PM out time
- **Working Days**: Count of Mon-Fri in the period

### Special Features

- PDF export with professional formatting
- Period-based organization (biweekly)
- Status tracking (draft → submitted → approved/rejected)
- Remarks/feedback system
- Automatic weekend marking (no late/absent counting)
- Performance comparison charts
- Soft deletes for data preservation

### Integration Points

- Automatically integrated with existing AttendanceLog system
- Works with EmployeeProfile relationships
- Compatible with existing authentication
- Uses existing branch and user systems

---

**Status**: ✅ Complete and Ready to Use
**Date Created**: May 11, 2026
