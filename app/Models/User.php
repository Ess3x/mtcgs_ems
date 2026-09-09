<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use CanResetPassword, HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'admin_type', 'profile_id', 'profile_type', 'branch_id',
        'id_verification_status', 'id_document_path', 'id_document_type',
        'rejection_reason', 'is_verified', 'is_active', 'email_verified_at', 'last_login_at', 'pending_password'
    ];

    protected $hidden = ['password', 'remember_token'];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function profile()
    {
        return $this->morphTo();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
    
    // Helper methods para makuha ang profile ng user ayon sa role
    public function getEmployeeProfile()
    {
        if ($this->role === 'employee' && $this->profile_type === EmployeeProfile::class) {
            return $this->profile;
        }
        return null;
    }
    
    public function getFinanceProfile()
    {
        if (in_array($this->role, ['finance_officer', 'finance_head'], true) && $this->profile_type === FinanceProfile::class) {
            return $this->profile;
        }
        return null;
    }
    
    public function getAdminProfile()
    {
        if ($this->role !== 'admin') {
            return null;
        }

        if ($this->profile_type === AdminProfile::class && $this->profile) {
            return $this->profile;
        }

        return AdminProfile::where('user_id', $this->id)->orderBy('id')->first();
    }
    
    public function getBranchHeadProfile()
    {
        if ($this->role === 'branch_head' && $this->profile_type === BranchHeadProfile::class) {
            return $this->profile;
        }
        return null;
    }
    
    public function getEffectiveBranchId()
    {
        if (!is_null($this->branch_id)) {
            return $this->branch_id;
        }

        $profile = $this->profile;
        if ($profile && !is_null($profile->branch_id)) {
            return $profile->branch_id;
        }

        return null;
    }

    public function isSuperAdmin()
    {
        return $this->role === 'admin' && ($this->admin_type === 'super_admin' || empty($this->admin_type));
    }
    
    public function isBranchAdmin()
    {
        return ($this->role === 'admin' && $this->admin_type === 'branch_admin')
            || $this->role === 'branch_head';
    }

    public function isBranchHead()
    {
        return $this->role === 'branch_head';
    }
    
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->role === 'branch_head';
    }
    
    public function isFinanceOfficer()
    {
        return in_array($this->role, ['finance_officer', 'finance_head'], true);
    }

    public function isFinanceHead()
    {
        return $this->role === 'finance_head';
    }

    public function isFinanceStaff()
    {
        return $this->isFinanceOfficer();
    }
    
    public function isEmployee()
    {
        return $this->role === 'employee';
    }
}
