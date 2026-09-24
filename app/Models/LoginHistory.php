<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    protected $fillable = [
        'user_id', 'event', 'status', 'email', 'ip_address', 'user_agent', 'failure_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
