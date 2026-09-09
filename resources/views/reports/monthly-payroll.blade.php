<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Monthly Payroll Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 16px; margin: 0; }
        .header p { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MOTHER THERESA COLEGIO GROUP OF SCHOOLS</h1>
        <p>Monthly Payroll Report - {{ $branchName }}</p>
        <p>Period: {{ date('F Y', mktime(0,0,0, $month, 1, $year)) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee #</th>
                <th>Employee Name</th>
                <th>Basic Pay</th>
                <th>Overtime</th>
                <th>Gross Pay</th>
                <th>SSS</th>
                <th>PhilHealth</th>
                <th>Pag-IBIG</th>
                <th>Tax</th>
                <th>Deductions</th>
                <th>Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @php $totalGross = 0; $totalNet = 0; @endphp
            @foreach($entries as $entry)
            @php
                $employee = $entry->employeeProfile;
                $totalGross += $entry->gross_pay;
                $totalNet += $entry->net_pay;
            @endphp
            <tr>
                <td>{{ $employee->employee_number ?? 'N/A' }}</td>
                <td>{{ ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '') }}</td>
                <td class="text-right">₱{{ number_format($entry->basic_pay, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->overtime_pay, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->gross_pay, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->sss_contribution, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->philhealth_contribution, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->pagibig_contribution, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->withholding_tax, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->total_deductions, 2) }}</td>
                <td class="text-right">₱{{ number_format($entry->net_pay, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><strong>TOTALS</strong></td>
                <td class="text-right"><strong>₱{{ number_format($totalGross, 2) }}</strong></td>
                <td colspan="5"></td>
                <td class="text-right"><strong>₱{{ number_format($totalNet, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>This is a system-generated report. For inquiries, please contact Finance Department.</p>
        <p>Generated on {{ now()->format('F d, Y h:i A') }}</p>
    </div>
</body>
</html>
