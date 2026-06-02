<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'nic',
        'licence_number',
        'licence_expiry_date',
        'phone_number',
        'is_active',
    ];

    protected $casts = [
        'licence_expiry_date' => 'date',
        'is_active'           => 'boolean',
    ];
}
