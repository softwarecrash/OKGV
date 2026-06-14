<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'can_correct_meter_readings'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'can_correct_meter_readings' => 'boolean',
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function approvedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'approved_by');
    }

    public function meterReadingCorrections(): HasMany
    {
        return $this->hasMany(MeterReadingCorrection::class, 'corrected_by');
    }

    public function canCorrectMeterReadings(): bool
    {
        return in_array($this->role, [UserRole::Administrator, UserRole::Board], true)
            && $this->can_correct_meter_readings;
    }
}
