<?php

namespace App\Models;

use App\Enums\UserPermission;
use Database\Factories\PermissionProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'permissions', 'is_active', 'created_by'])]
class PermissionProfile extends Model
{
    /** @use HasFactory<PermissionProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected function permissionLabels(): Attribute
    {
        return Attribute::get(fn (): array => collect($this->permissions ?? [])
            ->map(fn (string $permission) => UserPermission::tryFrom($permission)?->label())
            ->filter()
            ->values()
            ->all());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
