@extends('layouts.app')

@section('title', 'Branch Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Branch Details</h4>
                    <div>
                        @if(Auth::user()->isSuperAdmin())
                            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Branch Code:</th>
                                    <td>{{ $branch->branch_code }}</td>
                                </tr>
                                <tr>
                                    <th>Branch Name:</th>
                                    <td>{{ $branch->branch_name }}</td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>{{ $branch->address }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ optional($branch->created_at)->format('M d, Y H:i') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At:</th>
                                    <td>{{ optional($branch->updated_at)->format('M d, Y H:i') ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Employees in this Branch</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">Total Employees: <strong>{{ $branch->employees()->count() }}</strong></p>
                                    @if($branch->employees()->count() > 0)
                                        <a href="#" class="btn btn-sm btn-info mt-2">
                                            <i class="fas fa-users"></i> View Employees
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection