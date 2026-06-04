<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bus extends Model
{
    protected $fillable = [
        'registration_number',
        'vehicle_type',
        'seat_capacity',
        'current_mileage',
        'is_in_service',
    ];

    protected $casts = [
        'is_in_service'   => 'boolean',
        'seat_capacity'   => 'integer',
        'current_mileage' => 'integer',
    ];

    public static array $vehicleTypes = [
        'Mini Bus',
        'Standard Bus',
        'Double Decker',
        'Articulated Bus',
    ];

    public function getRegistrationNumberAttribute(string $value): string
    {
        return strtoupper($value);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
