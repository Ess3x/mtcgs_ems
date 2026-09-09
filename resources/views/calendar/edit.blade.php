@extends('layouts.app')

@section('title', 'Edit Calendar Event')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Calendar
                </a>
                <div>
                    <h2 class="mb-0 fw-bold">
                        <i class="fas fa-edit text-primary me-2"></i>Edit Calendar Event
                    </h2>
                    <p class="text-muted mb-0">Update event details</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.calendar.update', ['calendar' => $calendarEvent]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label fw-bold">Event Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                       id="title" name="title" value="{{ old('title', $calendarEvent->title) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="event_type" class="form-label fw-bold">Event Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('event_type') is-invalid @enderror"
                                        id="event_type" name="event_type" required>
                                    <option value="">Select Type</option>
                                    <option value="activity" {{ old('event_type', $calendarEvent->event_type) === 'activity' ? 'selected' : '' }}>School Activity</option>
                                    <option value="holiday" {{ old('event_type', $calendarEvent->event_type) === 'holiday' ? 'selected' : '' }}>Holiday</option>
                                </select>
                                @error('event_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="event_date" class="form-label fw-bold">Event Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('event_date') is-invalid @enderror"
                                   id="event_date" name="event_date" value="{{ old('event_date', $calendarEvent->event_date->format('Y-m-d')) }}" required>
                            @error('event_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="branch_id" class="form-label fw-bold">Branch</label>
                            @php
                                $adminProfile = Auth::user()->getAdminProfile();
                                $isSuperAdmin = Auth::user()->isSuperAdmin();
                            @endphp

                            @if($isSuperAdmin)
                                <!-- Super Admin can choose any branch or all branches -->
                                <select class="form-select @error('branch_id') is-invalid @enderror"
                                        id="branch_id" name="branch_id">
                                    <option value="">All Branches (School-wide)</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $calendarEvent->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Leave empty if this event applies to all branches</div>
                            @else
                                <!-- Regular Admin is automatically assigned to their branch -->
                                @if($adminProfile && $adminProfile->branch)
                                    <input type="hidden" name="branch_id" value="{{ $adminProfile->branch_id }}">
                                    <div class="form-control-plaintext bg-light p-2 rounded">
                                        <strong>{{ $adminProfile->branch->branch_name }}</strong>
                                        <small class="text-muted d-block">This event will be visible only to users from your branch</small>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Your admin profile is not properly configured with a branch. Please contact system administrator.
                                    </div>
                                @endif
                            @endif
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Provide details about this event...">{{ old('description', $calendarEvent->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Created by {{ $calendarEvent->creator->name }} on {{ $calendarEvent->created_at->format('M d, Y') }}
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Event
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection