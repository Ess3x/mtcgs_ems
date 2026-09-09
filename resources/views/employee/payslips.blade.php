@extends('layouts.app')

@section('title', 'My Payslips')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">My Payslips</h1>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Payroll History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Pay Period</th>
                        <th>Basic Pay</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->payrollPeriod->period_code }}</small></td>
                        <td>{{ date('M d, Y', strtotime($payslip->payrollPeriod->start_date)) }}<br>
                            <small>to {{ date('M d, Y', strtotime($payslip->payrollPeriod->end_date)) }}</small>
                        </td>
                        <td>₱{{ number_format($payslip->basic_pay, 2) }}</small></td>
                        <td><strong>₱{{ number_format($payslip->gross_pay, 2) }}</strong></small></td>
                        <td>₱{{ number_format($payslip->total_deductions, 2) }}</small></td>
                        <td><strong class="text-success">₱{{ number_format($payslip->net_pay, 2) }}</strong></small></td>
                        <td>
                            <a href="{{ route('admin.payroll.payslip', $payslip->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('admin.payroll.download-payslip', $payslip->id) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-download"></i> PDF
                            </a>
                        </td>
                    </tr>
                    @empty
                        <td><td colspan="7" class="text-center">No payslips found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $payslips->links() }}
        </div>
    </div>
</div>
@endsection
