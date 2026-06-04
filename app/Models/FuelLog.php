<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    protected $fillable = [
        'bus_id',
        'driver_id',
        'fuel_date',
        'litres',
        'cost_per_litre',
        'odometer_reading',
        'notes',
    ];

    protected $casts = [
        'fuel_date'        => 'date',
        'litres'           => 'decimal:2',
        'cost_per_litre'   => 'decimal:2',
        'odometer_reading' => 'integer',
    ];

    public function getTotalCostAttribute(): float
    {
        return (float) $this->litres * (float) $this->cost_per_litre;
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
