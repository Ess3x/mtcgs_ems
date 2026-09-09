<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'mac_address',
        'device_name',
        'device_type',
        'branch_id',
        'location',
        'status',
        'last_used_at',
        'notes',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Check if MAC address is registered and active
     */
    public static function isValidMacAddress($macAddress)
    {
        return self::getByMacAddress($macAddress) !== null;
    }

    /**
     * Get device by MAC address
     */
    public static function getByMacAddress($macAddress)
    {
        $normalized = self::normalizeMacAddress($macAddress);

        return self::where('status', 'active')
            ->where(function ($query) use ($normalized) {
                $query->where('mac_address', strtoupper($normalized))
                    ->orWhere('mac_address', strtoupper($normalized))
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(UPPER(mac_address), ':', ''), '-', ''), ' ', '') = ?", [$normalized]);
            })
            ->first();
    }

    /**
     * Normalize MAC address format
     */
    public static function normalizeMacAddress($mac)
    {
        if ($mac === null) {
            return '';
        }

        return strtoupper(preg_replace('/[^A-F0-9]/i', '', (string) $mac));
    }

    public function getFormattedMacAddressAttribute(): string
    {
        $normalized = self::normalizeMacAddress($this->mac_address);

        return strlen($normalized) === 12
            ? implode(':', str_split($normalized, 2))
            : (string) $this->mac_address;
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
    }
}
