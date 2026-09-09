<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['title', 'body', 'audience', 'roles', 'starts_at', 'ends_at', 'is_highlighted', 'requires_confirmation', 'published_at', 'archived_at', 'created_by'])]
class Announcement extends Model
{
    protected static function booted(): void
    {
        static::updating(function (Announcement $announcement): void {
            if ($announcement->getRawOriginal('published_at') !== null
                && array_diff(array_keys($announcement->getDirty()), ['archived_at', 'updated_at']) !== []) {
                throw new LogicException('Published announcements are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Announcements must be archived.'));
    }

    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'roles' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'is_highlighted' => 'boolean',
            'requires_confirmation' => 'boolean',
        ];
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->whereNull('archived_at')
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $eligible = $user !== null && $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval();
        $tenant = $eligible && $user->member?->parcelTenancies()->activeOn()->exists();

        return $query->current()->where(function (Builder $query) use ($user, $eligible, $tenant): void {
            $query->where('audience', AnnouncementAudience::Public);
            if (! $eligible) {
                return;
            }
            $query->orWhere('audience', AnnouncementAudience::All)
                ->orWhere(fn (Builder $query) => $query->where('audience', AnnouncementAudience::Roles)
                    ->whereJsonContains('roles', $user->role->value));
            if ($tenant) {
                $query->orWhere('audience', AnnouncementAudience::Tenants);
            }
        });
    }

    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        return $query->visibleTo($user)->whereDoesntHave('reads', fn (Builder $query) => $query->where('user_id', $user->id));
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->archived_at !== null => 'Zurückgezogen',
            $this->published_at === null => 'Entwurf',
            $this->ends_at !== null && $this->ends_at->lte(now()) => 'Abgelaufen',
            $this->starts_at->isFuture() => 'Geplant',
            default => 'Veröffentlicht',
        };
    }
}
