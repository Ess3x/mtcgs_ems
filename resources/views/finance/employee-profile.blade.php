@extends('layouts.app')

@section('title', 'User Profile')

@section('content')
<div class="container-fluid profile-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 fw-bold"><i class="fas fa-id-card text-primary me-2"></i>User Profile</h2>
            <p class="text-muted mb-0">Complete profile information</p>
        </div>
        <a href="{{ route('finance.employees') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> Back to Employees
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ $profile->first_name }} {{ $profile->last_name }}</h5>
                </div>
                <div class="card-body">
                    @php
                        $formatProfileDate = static function ($value): string {
                            if (!$value) {
                                return 'Not specified';
                            }

                            return $value instanceof \DateTimeInterface
                                ? $value->format('M d, Y')
                                : \Carbon\Carbon::parse($value)->format('M d, Y');
                        };
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small">Employee Number</div><div class="fw-semibold">{{ $profile->employee_number ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Role</div><div class="fw-semibold">{{ str_replace('_', ' ', ucfirst($profile->user?->role ?? 'User')) }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Position</div><div class="fw-semibold">{{ $profile->position ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Department</div><div class="fw-semibold">{{ $profile->department ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $profile->user?->email ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Date Hired</div><div class="fw-semibold">{{ $formatProfileDate($profile->date_hired) }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Birthdate</div><div class="fw-semibold">{{ $formatProfileDate($profile->date_of_birth) }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Gender</div><div class="fw-semibold">{{ $profile->gender ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Branch</div><div class="fw-semibold">{{ $profile->branch?->branch_name ?? 'Not assigned' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Status</div><div class="fw-semibold">{{ $profile->status ?? 'Not specified' }}</div></div>
                    </div>
                    <hr class="my-4">
                    <h6 class="text-primary mb-3"><i class="fas fa-id-card me-2"></i>Government Numbers</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small">SSS Number</div><div class="fw-semibold">{{ $profile->sss_number ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">PhilHealth Number</div><div class="fw-semibold">{{ $profile->philhealth_number ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Pag-IBIG Number</div><div class="fw-semibold">{{ $profile->pagibig_number ?? 'Not specified' }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">TIN Number</div><div class="fw-semibold">{{ $profile->tin_number ?? 'Not specified' }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-header bg-white"><h5 class="mb-0">Profile Picture</h5></div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    @php
                        $hasProfilePhoto = $profile->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->profile_photo);
                    @endphp
                    @if($hasProfilePhoto)
                        <img src="{{ asset('storage/' . ltrim($profile->profile_photo, '/') . '?v=' . $profile->updated_at?->timestamp) }}" alt="{{ $profile->first_name }} {{ $profile->last_name }}" class="rounded-circle border shadow-sm mb-3" style="width: 190px; height: 190px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 190px; height: 190px; font-size: 4rem;">
                            {{ strtoupper(substr($profile->first_name, 0, 1) . substr($profile->last_name, 0, 1)) }}
                        </div>
                        <p class="text-muted mb-0">No profile picture uploaded</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
