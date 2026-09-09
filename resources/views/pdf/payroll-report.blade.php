<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payroll Report - {{ $period->period_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 18px; }
        .company { font-size: 16px; font-weight: bold; }
        .title { font-size: 13px; margin-top: 5px; }
        .period { margin-top: 4px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; }
        th { background: #e5e7eb; text-align: left; font-size: 9px; }
        td.amount { text-align: right; white-space: nowrap; }
        td.status { text-align: center; }
        .total td { background: #f3f4f6; font-weight: bold; }
        .approval { margin-top: 28px; border: 1px solid #cbd5e1; padding: 12px; }
        .approval-title { font-weight: bold; margin-bottom: 15px; }
        .signature { display: inline-block; width: 45%; margin-right: 4%; vertical-align: top; }
        .approval .signature { text-align: center; }
        .signature-line { border-bottom: 1px solid #1f2937; height: 22px; margin-bottom: 4px; }
        .signature-image { display: block; height: 22px; max-width: 150px; object-fit: contain; margin: 4px auto 0; }
        .approval-grid { width: 100%; border-collapse: collapse; }
        .approval-grid td { border: 0; width: 50%; padding: 0 8px; text-align: center; vertical-align: top; }
        .signer-table { width: 190px; border-collapse: collapse; margin-left: 0; }
        .signer-table td { width: 100%; padding: 0; text-align: center; vertical-align: middle; }
        .approved-badge { display: inline-block; background: #198754; color: #fff; border-radius: 4px; padding: 5px 12px; font-weight: bold; }
        .footer { margin-top: 18px; text-align: right; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    @php($isApprovedPayroll = in_array($period->status, ['approved', 'completed'], true))
    <div class="header">
        <div class="company">MOTHER THERESA COLEGIO GROUP OF SCHOOLS</div>
        <div class="title">PAYROLL REPORT</div>
        <div class="period">{{ $period->period_code }} | {{ $period->branch?->branch_name ?? 'N/A' }} | {{ $period->start_date->format('M d, Y') }} - {{ $period->end_date->format('M d, Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Employee Number</th>
                <th style="text-align:right">Basic Pay</th>
                <th style="text-align:right">Gross Pay</th>
                <th style="text-align:right">Total Deductions</th>
                <th style="text-align:right">Net Pay</th>
                <th style="text-align:center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ trim(($entry->employee->first_name ?? '') . ' ' . ($entry->employee->last_name ?? '')) }}</td>
                    <td>{{ $entry->employee->employee_number ?? 'N/A' }}</td>
                    <td class="amount">₱{{ number_format($entry->basic_pay, 2) }}</td>
                    <td class="amount">₱{{ number_format($entry->gross_pay, 2) }}</td>
                    <td class="amount">₱{{ number_format($entry->total_deductions, 2) }}</td>
                    <td class="amount">₱{{ number_format($entry->net_pay, 2) }}</td>
                    <td class="status">{{ $isApprovedPayroll ? ($period->admin_approval_stage === 'hr_fd' ? 'Approved HR/FD' : 'Finance Head Approved') : ucfirst($period->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center">No payroll entries found.</td>
                </tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
            <tfoot>
                <tr class="total">
                    <td colspan="2">TOTAL</td>
                    <td class="amount">₱{{ number_format($entries->sum('basic_pay'), 2) }}</td>
                    <td class="amount">₱{{ number_format($entries->sum('gross_pay'), 2) }}</td>
                    <td class="amount">₱{{ number_format($entries->sum('total_deductions'), 2) }}</td>
                    <td class="amount">₱{{ number_format($entries->sum('net_pay'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if ($isApprovedPayroll)
        <div class="approval">
            <div class="approval-title">Finance Head Approval</div>
            <table class="approval-grid">
                <tr>
                    <td>
                        <table class="signer-table">
                            <tr>
                                <td>
                                    @if ($financeHeadSignature)
                                        <img class="signature-image" src="{{ $financeHeadSignature }}" alt="Finance Head e-signature">
                                    @else
                                        <div class="signature-line"></div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ $period->approvedBy?->name ?? 'Finance Head' }}</strong></td>
                            </tr>
                            <tr>
                                <td><span class="approved-badge">APPROVED</span></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <strong>{{ $approvalDate?->format('M d, Y h:i A') ?? 'Pending approval date' }}</strong><br>
                        <span>Approval Date</span>
                    </td>
                </tr>
            </table>
        </div>
        @if ($period->hr_approved_at)
            <div class="approval">
                <div class="approval-title">HR Approval</div>
                <table class="approval-grid">
                    <tr>
                        <td>
                            <table class="signer-table">
                                <tr>
                                    <td>
                                        @if ($hrSignature)
                                            <img class="signature-image" src="{{ $hrSignature }}" alt="HR e-signature">
                                        @else
                                            <div class="signature-line"></div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ $period->hrApprovedBy?->name ?? 'HR' }}</strong></td>
                                </tr>
                                <tr>
                                    <td><span class="approved-badge">APPROVED</span></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <div class="signature-line"></div>
                            <strong>{{ $hrApprovalDate?->format('M d, Y h:i A') ?? 'Pending approval date' }}</strong><br>
                            <span>Approval Date</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif
        @if ($period->branch_approved_at)
            <div class="approval">
                <div class="approval-title">Branch Head Approval</div>
                <table class="approval-grid">
                    <tr>
                        <td>
                            <table class="signer-table">
                                <tr>
                                    <td>
                                        @if ($branchSignature)
                                            <img class="signature-image" src="{{ $branchSignature }}" alt="Branch Head e-signature">
                                        @else
                                            <div class="signature-line"></div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ $period->branchApprovedBy?->name ?? 'Branch Head' }}</strong></td>
                                </tr>
                                <tr>
                                    <td><span class="approved-badge">APPROVED</span></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <div class="signature-line"></div>
                            <strong>{{ $period->branch_approved_at?->format('M d, Y h:i A') ?? 'Pending approval date' }}</strong><br>
                            <span>Approval Date</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @endif

    <div class="footer">Generated {{ now()->format('M d, Y h:i A') }}</div>
</body>
</html>
