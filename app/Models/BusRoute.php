<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusRoute extends Model
{
    protected $fillable = [
        'name',
        'origin',
        'origin_lat',
        'origin_lng',
        'destination',
        'destination_lat',
        'destination_lng',
        'stops',
        'distance_km',
        'is_active',
    ];

    protected $casts = [
        'stops'           => 'array',
        'is_active'       => 'boolean',
        'distance_km'     => 'decimal:2',
        'origin_lat'      => 'decimal:7',
        'origin_lng'      => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
    ];
}
