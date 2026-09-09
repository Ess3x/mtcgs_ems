<!DOCTYPE html>
<html>
<body>
    <h2>Employment Status Updated</h2>
    <p>Dear {{ $employeeProfile->first_name }},</p>
    <p>Your employment status has been updated in MTCGS-EMS.</p>
    <p><strong>Previous status:</strong> {{ $previousStatus }}</p>
    <p><strong>New status:</strong> {{ $newStatus }}</p>
    <p>Your leave credits will follow your new employment status.</p>
    <p>Thank you.</p>
</body>
</html>