<?php

namespace App\Models;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'is_system_admin',
    'can_correct_meter_readings',
    'permissions',
    'permission_profile_id',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
            'is_system_admin' => 'boolean',
            'can_correct_meter_readings' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->is_system_admin || $this->role === UserRole::Administrator;
    }

    public function hasTenantAccess(): bool
    {
        return $this->member()->exists();
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function permissionProfile(): BelongsTo
    {
        return $this->belongsTo(PermissionProfile::class);
    }

    public function approvedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'approved_by');
    }

    public function meterReadingCorrections(): HasMany
    {
        return $this->hasMany(MeterReadingCorrection::class, 'corrected_by');
    }

    public function meterReadingSubmissions(): HasMany
    {
        return $this->hasMany(MeterReadingSubmission::class, 'submitted_by');
    }

    public function reviewedMeterReadingSubmissions(): HasMany
    {
        return $this->hasMany(MeterReadingSubmission::class, 'reviewed_by');
    }

    public function reviewedRegistrationRequests(): HasMany
    {
        return $this->hasMany(RegistrationRequest::class, 'reviewed_by');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function canCorrectMeterReadings(): bool
    {
        if ($this->permissions !== null) {
            return $this->hasPermission(UserPermission::CorrectMeterReadings);
        }

        return $this->role === UserRole::Board
            && $this->can_correct_meter_readings;
    }

    public function hasPermission(UserPermission $permission): bool
    {
        if (! $permission->isAvailable()) {
            return false;
        }

        $permissions = $this->permissions ?? $this->role->defaultPermissions();

        return in_array($permission->value, $permissions, true);
    }

    public function canViewAllMasterData(): bool
    {
        return $this->hasPermission(UserPermission::ViewAllMasterData);
    }

    public function canManageMasterData(): bool
    {
        return $this->hasPermission(UserPermission::ManageMasterData);
    }

    public function canViewAllMeters(): bool
    {
        return $this->hasPermission(UserPermission::ViewAllMeters);
    }

    public function canManageMeters(): bool
    {
        return $this->hasPermission(UserPermission::ManageMeters);
    }

    public function canManageBilling(): bool
    {
        return $this->hasPermission(UserPermission::ManageBilling);
    }

    public function canManageBillingTemplates(): bool
    {
        return $this->hasPermission(UserPermission::ManageBillingTemplates);
    }

    public function canManageSepa(): bool
    {
        return $this->hasPermission(UserPermission::ManageSepa);
    }

    public function canReviewTenantRegistrations(): bool
    {
        return $this->hasPermission(UserPermission::ReviewTenantRegistrations);
    }

    public function canReviewMeterReadingSubmissions(): bool
    {
        return $this->hasPermission(UserPermission::ReviewMeterReadingSubmissions);
    }

    public function canManageCommunication(): bool
    {
        return $this->hasPermission(UserPermission::ManageCommunication);
    }

    public function canManageDocuments(): bool
    {
        return $this->hasPermission(UserPermission::ManageDocuments);
    }

    public function canManageWorkEvents(): bool
    {
        return $this->hasPermission(UserPermission::ManageWorkEvents);
    }

    public function canManageWaitingList(): bool
    {
        return $this->hasPermission(UserPermission::ManageWaitingList);
    }

    public function canManageInventory(): bool
    {
        return $this->hasPermission(UserPermission::ManageInventory);
    }

    public function canManageDataTransfer(): bool
    {
        return $this->hasPermission(UserPermission::ManageDataTransfer);
    }

    public function canManagePrivacy(): bool
    {
        return $this->hasPermission(UserPermission::ManagePrivacy);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
