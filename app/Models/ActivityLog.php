<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'section',
        'action',
        'subject_label',
        'ip_address',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $section, string $action, string $subjectLabel): void
    {
        $user = Auth::user();

        static::create([
            'user_id'       => $user?->id,
            'user_name'     => $user?->name ?? 'System',
            'user_email'    => $user?->email ?? '',
            'section'       => $section,
            'action'        => $action,
            'subject_label' => $subjectLabel,
            'ip_address'    => Request::ip(),
        ]);
    }

    public static array $sections = [
        'login'         => 'Login Activity',
        'users'         => 'Users',
        'buses'         => 'Buses',
        'routes'        => 'Routes',
        'drivers'       => 'Drivers',
        'schedules'     => 'Schedules',
        'schedule_runs' => 'Schedule Runs',
        'fuel'          => 'Fuel',
        'maintenance'   => 'Maintenance',
    ];
}
