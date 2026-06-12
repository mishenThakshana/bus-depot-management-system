<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'driver_id', 'is_active', 'must_change_password', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Driver record this login account represents (driver role only). */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // ── Role helpers ─────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /** Where this user lands after logging in, based on role. */
    public function homeRoute(): string
    {
        return $this->isDriver() ? 'panel.my-schedule' : 'panel.dashboard';
    }

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'admin'      => 'Administrator',
            'supervisor' => 'Supervisor',
            'driver'     => 'Driver',
            default      => ucfirst($this->role),
        };
    }

    public function getRoleInitial(): string
    {
        return match ($this->role) {
            'admin'      => 'A',
            'supervisor' => 'S',
            'driver'     => 'D',
            default      => strtoupper($this->role[0]),
        };
    }
}
