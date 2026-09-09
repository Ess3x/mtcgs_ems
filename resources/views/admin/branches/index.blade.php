@extends('layouts.app')

@section('title', 'Branches Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Branches Management</h4>
                    @if(Auth::user()->isSuperAdmin())
                        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Branch
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Branch Code</th>
                                    <th>Branch Name</th>
                                    <th>Address</th>
                                    <th>Employees Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                    <tr>
                                        <td>{{ $branch->branch_code }}</td>
                                        <td>{{ $branch->branch_name }}</td>
                                        <td>{{ $branch->address }}</td>
                                        <td>{{ $branch->employees()->count() }}</td>
                                        <td>
                                            <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            @if(Auth::user()->isSuperAdmin())
                                                <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this branch?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No branches found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection