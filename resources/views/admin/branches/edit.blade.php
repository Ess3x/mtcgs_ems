@extends('layouts.app')

@section('title', 'Edit Branch')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Edit Branch</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.branches.update', $branch) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="branch_code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('branch_code') is-invalid @enderror"
                                       id="branch_code" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}" required>
                                @error('branch_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="branch_name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('branch_name') is-invalid @enderror"
                                       id="branch_name" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" required>
                                @error('branch_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="3" required>{{ old('address', $branch->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection