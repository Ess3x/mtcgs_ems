<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'mac_address',
        'serial_number',
        'wifi_mac_address',
        'laptop_mac_address',
        'allowed_mac_addresses',
        'api_secret',
        'device_name',
        'device_type',
        'branch_id',
        'location',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'location_check_enabled',
        'status',
        'last_used_at',
        'notes',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meters' => 'integer',
        'location_check_enabled' => 'boolean',
        'api_secret' => 'encrypted',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_secret',
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
     * Get device by serial number.
     */
    public static function getBySerialNumber($serialNumber)
    {
        $normalized = self::normalizeSerialNumber($serialNumber);

        if ($normalized === '') {
            return null;
        }

        return self::where('status', 'active')
            ->whereRaw('UPPER(TRIM(serial_number)) = ?', [$normalized])
            ->first();
    }

    public static function getByDeviceIdentifier($identifier)
    {
        $normalized = strtoupper(trim((string) $identifier));

        if ($normalized === '') {
            return null;
        }

        return self::where('status', 'active')
            ->where(function ($query) use ($normalized) {
                $query->whereRaw('UPPER(TRIM(serial_number)) = ?', [$normalized]);
            })
            ->first();
    }

    /**
     * Get device by MAC address
     */
    public static function getByMacAddress($macAddress)
    {
        $normalized = self::normalizeMacAddress($macAddress);

        if ($normalized === '') {
            return null;
        }

        return self::where('status', 'active')
            ->where(function ($query) use ($normalized) {
                $query->where('mac_address', strtoupper($normalized))
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(UPPER(mac_address), ':', ''), '-', ''), ' ', '') = ?", [$normalized])
                    ->orWhere('wifi_mac_address', strtoupper($normalized))
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(UPPER(wifi_mac_address), ':', ''), '-', ''), ' ', '') = ?", [$normalized])
                    ->orWhereRaw("LOWER(COALESCE(allowed_mac_addresses, '[]')) LIKE ?", ['%' . strtolower($normalized) . '%']);
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

    /**
     * Normalize serial format
     */
    public static function normalizeSerialNumber($serialNumber)
    {
        if ($serialNumber === null) {
            return '';
        }

        return strtoupper(trim((string) $serialNumber));
    }

    /**
     * Check whether a MAC address is in the allowed list for this device.
     */
    public function isMacAllowed(?string $mac): bool
    {
        $checkedMac = self::normalizeMacAddress($mac);

        if ($checkedMac === '') {
            return true;
        }

        $allowed = collect([
            $this->mac_address,
            $this->wifi_mac_address,
            ...($this->parsedAllowedMacAddresses()),
        ])
            ->map(fn ($allowedMac) => self::normalizeMacAddress($allowedMac))
            ->filter(fn ($allowedMac) => $allowedMac !== '')
            ->all();

        return in_array($checkedMac, $allowed, true);
    }

    /**
     * Parse JSON allowed MAC addresses list.
     */
    public function parsedAllowedMacAddresses(): array
    {
        if (empty($this->allowed_mac_addresses)) {
            return [];
        }

        $decoded = json_decode((string) $this->allowed_mac_addresses, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($value) => (string) $value, $decoded), fn ($value) => $value !== ''));
    }

    public function getFormattedMacAddressAttribute(): string
    {
        $normalized = self::normalizeMacAddress($this->mac_address);

        return strlen($normalized) === 12
            ? implode(':', str_split($normalized, 2))
            : (string) $this->mac_address;
    }

    public function getFormattedWifiMacAddressAttribute(): string
    {
        $normalized = self::normalizeMacAddress($this->wifi_mac_address);

        return strlen($normalized) === 12
            ? implode(':', str_split($normalized, 2))
            : (string) $this->wifi_mac_address;
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
    }
}
