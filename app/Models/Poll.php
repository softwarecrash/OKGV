<?php

namespace App\Models;

use App\Enums\MemberStatus;
use App\Enums\PollAudience;
use App\Enums\PollResultsVisibility;
use App\Enums\PollType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['title', 'description', 'type', 'multiple', 'audience', 'roles', 'results_visibility', 'starts_at', 'ends_at'])]
class Poll extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Poll $poll): void {
            if (($poll->getRawOriginal('published_at') !== null || $poll->getRawOriginal('archived_at') !== null)
                && array_diff(array_keys($poll->getDirty()), ['closed_at', 'archived_at', 'updated_at']) !== []) {
                throw new LogicException('Published polls are immutable.');
            }
            if ($poll->getRawOriginal('closed_at') !== null && $poll->isDirty('closed_at')) {
                throw new LogicException('Closed polls cannot be reopened.');
            }
        });
        static::deleting(fn () => throw new LogicException('Polls must be archived.'));
    }

    protected function casts(): array
    {
        return ['type' => PollType::class, 'audience' => PollAudience::class, 'results_visibility' => PollResultsVisibility::class, 'multiple' => 'boolean', 'roles' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'published_at' => 'datetime', 'closed_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(PollParticipation::class);
    }

    public function isClosed(): bool
    {
        return $this->published_at !== null && ($this->closed_at !== null || $this->ends_at->lte(now()));
    }

    public function isOpen(): bool
    {
        return $this->published_at !== null && $this->archived_at === null && ! $this->isClosed() && $this->starts_at->lte(now());
    }

    public static function activeMembership(Builder $query): Builder
    {
        return $query->where('status', MemberStatus::Active)->whereNull('archived_at')->whereDate('joined_at', '<=', today())
            ->where(fn ($query) => $query->whereNull('left_at')->orWhereDate('left_at', '>=', today()));
    }

    public function candidates(): Builder
    {
        $query = User::query()->whereNotNull('email_verified_at');
        if ($this->audience === PollAudience::Roles) {
            return $query->whereIn('role', $this->roles ?? []);
        }

        return $query->whereHas('member', function (Builder $query): void {
            self::activeMembership($query);
            if ($this->audience === PollAudience::Tenants) {
                $query->whereHas('parcelTenancies', fn ($query) => $query->activeOn());
            }
        });
    }

    public function scopeForParticipant(Builder $query, User $user): Builder
    {
        $member = $user->member()->where(fn ($query) => self::activeMembership($query))->first();
        $tenant = $member && $member->parcelTenancies()->activeOn()->exists();

        return $query->whereNotNull('published_at')->whereHas('participations', fn ($query) => $query->where('user_id', $user->id))
            ->where(function (Builder $query) use ($user, $member, $tenant): void {
                $query->where(fn ($query) => $query->where('audience', PollAudience::Roles)->whereJsonContains('roles', $user->role->value));
                if ($member) {
                    $query->orWhere('audience', PollAudience::Members);
                }
                if ($tenant) {
                    $query->orWhere('audience', PollAudience::Tenants);
                }
            });
    }

    public function scopeUnansweredFor(Builder $query, User $user): Builder
    {
        if (! $user->can('viewAny', self::class)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->forParticipant($user)->whereNull('archived_at')->whereNull('closed_at')->where('starts_at', '<=', now())->where('ends_at', '>', now())
            ->whereHas('participations', fn ($query) => $query->where('user_id', $user->id)->whereNull('answered_at'));
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->archived_at !== null => 'Archiviert', $this->published_at === null => 'Entwurf',
            $this->isClosed() => 'Abgeschlossen', $this->starts_at->isFuture() => 'Geplant', default => 'Offen',
        };
    }
}
