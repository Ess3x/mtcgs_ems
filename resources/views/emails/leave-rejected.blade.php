<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Declined</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .leave-details {
            background: white;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #dc3545;
        }
        .badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            background: #dc3545;
            color: white;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Leave Request Declined</h1>
        </div>

        <div class="content">
            <p>Dear {{ $employeeProfile->first_name }} {{ $employeeProfile->last_name }},</p>

            <p>Unfortunately, your leave request has been <span class="badge">DECLINED</span></p>

            <div class="leave-details">
                <h3 style="margin-top: 0; color: #dc3545;">Leave Details</h3>
                
                <div class="detail-row">
                    <span class="label">Leave Type:</span><br>
                    {{ ucfirst($leaveRequest->leave_type) }} Leave
                </div>

                <div class="detail-row">
                    <span class="label">From Date:</span><br>
                    {{ $leaveRequest->start_date->format('F d, Y (l)') }}
                </div>

                <div class="detail-row">
                    <span class="label">To Date:</span><br>
                    {{ $leaveRequest->end_date->format('F d, Y (l)') }}
                </div>

                <div class="detail-row">
                    <span class="label">Total Days:</span><br>
                    <strong>{{ $leaveRequest->total_days }} day(s)</strong>
                </div>

                <div class="detail-row">
                    <span class="label">Reason:</span><br>
                    {{ $leaveRequest->reason }}
                </div>
            </div>

            <p>Your leave request could not be approved at this time. Please contact your administrator or HR department for more information about the reason for this decision.</p>

            <p>You are welcome to resubmit your leave request for a different date if needed.</p>

            <p>Best regards,<br>
            <strong>MTCGS-EMS Administration Team</strong></p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Mother Theresa Colegio Group of Schools | Employee Management System</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>