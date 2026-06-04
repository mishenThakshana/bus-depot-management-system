<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'bus_id',
        'maintenance_type',
        'description',
        'serviced_date',
        'notes',
    ];

    protected $casts = [
        'serviced_date' => 'date',
    ];

    public static array $types = [
        'Oil Change',
        'Tire Replacement',
        'Brake Service',
        'Engine Repair',
        'Battery Replacement',
        'General Service',
        'Body Repair',
        'Air Conditioning',
        'Other',
    ];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }
}
