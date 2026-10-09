<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DTR - {{ $employeeProfile->employee_number }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        .header { margin-bottom: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: 0; padding: 0; text-align: center; vertical-align: middle; }
        .logo { display: block; width: 92px; height: 92px; object-fit: contain; margin: 0 auto 8px; }
        .company { font-size: 15px; font-weight: bold; }
        .title { font-size: 12px; margin-top: 4px; }
        .meta { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px; }
        th { background: #e5e7eb; text-align: center; }
        td.center { text-align: center; }
        .summary td { width: 25%; }
        .summary-detail td { width: 25%; }
        .summary strong { display: block; font-size: 11px; margin-top: 3px; }
        .status { text-align: center; font-weight: bold; }
        .signature { page-break-inside: avoid; margin-top: 16px; text-align: center; }
        .signature-table { width: 220px; margin: 0 auto; border-collapse: collapse; }
        .signature-table td { border: 0; padding: 0; text-align: center; }
        .signature img { display: block; width: 180px; height: 54px; object-fit: contain; margin: 0 auto 3px; }
        .signature-line { width: 180px; border-top: 1px solid #111; margin: 30px auto 4px; }
        .signature-name { display: block; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    @if ($logo)
                        <img class="logo" src="{{ $logo }}" alt="MTCGS logo">
                    @endif
                    <div class="company">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
                    <div class="title">DAILY TIME RECORD</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Employee:</strong> {{ $employeeProfile->first_name }} {{ $employeeProfile->last_name }}</td>
            <td><strong>Employee No.:</strong> {{ $employeeProfile->employee_number }}</td>
        </tr>
        <tr>
            <td><strong>Period:</strong> {{ $dtr->period_start->format('M d, Y') }} - {{ $dtr->period_end->format('M d, Y') }}</td>
            <td><strong>Status:</strong> {{ ucfirst($dtr->status) }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>Total Hours<strong>{{ number_format($stats['total_hours'], 2) }}</strong></td>
            <td>Working Days<strong>{{ $stats['working_days'] }}</strong></td>
            <td>Days Present<strong>{{ $stats['days_present'] }}</strong></td>
            <td>Days Absent<strong>{{ $stats['days_absent'] }}</strong></td>
        </tr>
        <tr class="summary-detail">
            <td>Total Late<strong>{{ $stats['total_late_minutes'] }} min</strong></td>
            <td>Early Out<strong>{{ $stats['total_early_out_minutes'] }} min</strong></td>
            <td>Total Paid Leave<strong>{{ $stats['total_paid_leave'] }} day(s)</strong></td>
            <td>Total Leave Without Pay<strong>{{ $stats['total_leave_without_pay'] }} day(s)</strong></td>
        </tr>
        <tr class="summary-detail">
            <td>Total Holidays<strong>{{ $stats['total_holidays'] }}</strong></td>
            <td>Total Suspensions<strong>{{ $stats['total_suspensions'] }}</strong></td>
            <td>Total Halfdays<strong>{{ $stats['total_half_days'] }}</strong></td>
            <td>Total Suspended Hours<strong>{{ number_format($stats['total_suspended_hours'], 1) }} hrs</strong></td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time-In</th>
                <th>Time-Out</th>
                <th>Late/Early Out (min)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($daysInPeriod as $day)
                @php($log = $day['log'] ?? null)
                @php($status = $day['status'] ?? 'N/A')
                @php($lateEarlyOutMinutes = \App\Models\DTR::normalizeLateMinutesForLog($log))
                @if ($log && in_array($status, ['present', 'late'], true))
                    @php($attendanceStatus = $log->getDtrStatus())
                    @if (in_array($attendanceStatus, ['Half Day', 'Early Out', 'Late', 'Late / Early Out'], true))
                        @php($status = $attendanceStatus)
                    @endif
                    @if (in_array($attendanceStatus, ['Early Out', 'Late / Early Out'], true) && $log->pm_out)
                        @php($scheduledEnd = $log->pm_out->copy()->setTime(17, 0, 0))
                        @php($lateEarlyOutMinutes += max(0, (int) abs($scheduledEnd->diffInMinutes($log->pm_out))))
                    @endif
                @endif
                <tr>
                    <td class="center">{{ $day['date']->format('M d, Y') }}</td>
                    <td class="center">{{ ($log?->am_in ?? $log?->pm_in)?->format('h:i A') ?? '--' }}</td>
                    <td class="center">{{ ($log?->pm_out ?? $log?->am_out)?->format('h:i A') ?? '--' }}</td>
                    <td class="center">{{ $lateEarlyOutMinutes }}</td>
                    <td class="status">{{ is_string($status) && $status === 'Half Day' ? $status : ucfirst($status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <table class="signature-table">
            <tr>
                <td>
                    @if ($employeeSignature)
                        <img src="{{ $employeeSignature }}" alt="Employee e-signature">
                    @else
                        <div class="signature-line"></div>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <span class="signature-name">{{ $employeeProfile->first_name }} {{ $employeeProfile->last_name }}</span>
                    <span>Employee Signature</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Generated {{ now()->format('M d, Y h:i A') }}</div>
</body>
</html>
