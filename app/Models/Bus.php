<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
