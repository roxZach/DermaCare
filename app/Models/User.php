<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /** Roles supported by the system (Guest = not logged in). */
    public const ROLES = [
        'admin' => 'Admin',
        'dermatologist' => 'Dermatologist',
        'receptionist' => 'Receptionist',
        'lab' => 'Lab Technician',
        'patient' => 'Patient',
    ];

    protected $fillable = ['name', 'email', 'phone', 'role', 'is_active', 'password', 'email_verified_at',
        'otp_code', 'otp_expires_at'];

    protected $hidden = ['password', 'remember_token', 'otp_code'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function patient() { return $this->hasOne(Patient::class); }
    public function doctor() { return $this->hasOne(Doctor::class); }
    public function alerts() { return $this->hasMany(Alert::class)->latest(); }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    /** Home page of each role after login. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'dermatologist' => 'doctor.dashboard',
            'receptionist' => 'reception.dashboard',
            'lab' => 'lab.dashboard',
            default => 'patient.dashboard',
        };
    }
}
