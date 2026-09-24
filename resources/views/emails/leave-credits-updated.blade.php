<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Credits Updated</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7fb; font-family:Arial, sans-serif; color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7fb; padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr style="background-color:#6d28d9;">
                        <td style="padding:24px 32px; color:#ffffff; font-size:24px; font-weight:bold;">
                            Leave Credits Updated
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">
                                Hello {{ $employeeProfile->first_name ?? 'Employee' }},
                            </p>
                            <p style="margin:0 0 20px; font-size:15px; line-height:1.6;">
                                Your leave credits have been updated by the administrator.
                            </p>

                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; border:1px solid #e5e7eb; font-size:14px; margin-bottom:20px;">
                                <tr style="background:#f9fafb;">
                                    <th align="left" style="border:1px solid #e5e7eb; padding:10px;">Leave Type</th>
                                    <th align="right" style="border:1px solid #e5e7eb; padding:10px;">Credits</th>
                                </tr>
                                @foreach ($credits as $label => $value)
                                    <tr>
                                        <td style="border:1px solid #e5e7eb; padding:10px;">{{ $label }}</td>
                                        <td align="right" style="border:1px solid #e5e7eb; padding:10px;">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="margin:0; font-size:14px; line-height:1.6; color:#4b5563;">
                                Please log in to the system to review your updated leave balance.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
