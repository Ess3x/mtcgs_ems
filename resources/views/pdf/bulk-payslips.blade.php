<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulk Payslips</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .payslip { page-break-after: always; margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .payslip-title { font-size: 12px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 10px; }
        th { background-color: #f2f2f2; }
        .net-pay { font-size: 12px; font-weight: bold; text-align: right; margin-top: 10px; }
    </style>
</head>
<body>
    @foreach($entries as $entry)
    <div class="payslip">
        <div class="header">
            <div class="company-name">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
            <div class="payslip-title">EMPLOYEE PAYSLIP</div>
            <div>Period: {{ $entry->payrollPeriod->period_code ?? 'N/A' }}</div>
        </div>

        <table>
            <tr><td width="50%"><strong>Employee:</strong> {{ optional($entry->employeeProfile)->first_name ?? '' }} {{ optional($entry->employeeProfile)->last_name ?? '' }}</td>
                <td width="50%"><strong>Employee #:</strong> {{ optional($entry->employeeProfile)->employee_number ?? 'N/A' }}</td></tr>
            <tr><td><strong>Position:</strong> {{ optional($entry->employeeProfile)->position ?? 'N/A' }}</td>
                <td><strong>Branch:</strong> {{ optional($entry->branch)->branch_name ?? 'N/A' }}</td></tr>
        </table>

        <table>
            <tr><th>EARNINGS</th><th>Amount</th><th>DEDUCTIONS</th><th>Amount</th></tr>
            <tr><td>Basic Pay</td><td>₱{{ number_format($entry->basic_pay, 2) }}</td><td>SSS</td><td>₱{{ number_format($entry->sss_contribution, 2) }}</td></tr>
            <tr><td>Overtime Pay</td><td>₱{{ number_format($entry->overtime_pay, 2) }}</td><td>PhilHealth</td><td>₱{{ number_format($entry->philhealth_contribution, 2) }}</td></tr>
            <tr><td></td><td></td><td>Pag-IBIG</td><td>₱{{ number_format($entry->pagibig_contribution, 2) }}</td></tr>
            <tr><td></td><td></td><td>Withholding Tax</td><td>₱{{ number_format($entry->withholding_tax, 2) }}</td></tr>
            <tr class="total-row"><td><strong>GROSS PAY</strong></td><td><strong>₱{{ number_format($entry->gross_pay, 2) }}</strong></td>
                <td><strong>TOTAL DEDUCTIONS</strong></td><td><strong>₱{{ number_format($entry->total_deductions, 2) }}</strong></td></tr>
        </table>

        <div class="net-pay"><strong>NET PAY: ₱{{ number_format($entry->net_pay, 2) }}</strong></div>
    </div>
    @endforeach
</body>
</html>
