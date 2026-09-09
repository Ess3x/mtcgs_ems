<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'event_type',
        'created_by',
        'branch_id'
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActivities($query)
    {
        return $query->where('event_type', 'activity');
    }

    public function scopeHolidays($query)
    {
        return $query->where('event_type', 'holiday');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }
}
