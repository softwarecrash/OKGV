<?php

namespace App\Models;

use App\Enums\MemberAssemblyMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['title', 'mode', 'scheduled_at', 'location', 'electronic_rights_notice', 'virtual_authorization_reference', 'agenda', 'minutes'])]
class MemberAssembly extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $assembly): void {
            if (($assembly->getRawOriginal('published_at') !== null || $assembly->getRawOriginal('finalized_at') !== null || $assembly->getRawOriginal('archived_at') !== null)
                && array_diff(array_keys($assembly->getDirty()), ['finalized_at', 'finalized_by', 'final_minutes_snapshot', 'archived_at', 'updated_at']) !== []) {
                throw new LogicException('Published member assemblies are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Member assemblies must be archived.'));
    }

    protected function casts(): array
    {
        return ['mode' => MemberAssemblyMode::class, 'scheduled_at' => 'datetime', 'published_at' => 'datetime', 'finalized_at' => 'datetime', 'archived_at' => 'datetime', 'final_minutes_snapshot' => 'array'];
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(MemberAssemblyResolution::class)->orderBy('position');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(MemberAssemblyParticipation::class);
    }

    public function isOpen(): bool
    {
        return $this->published_at !== null && $this->finalized_at === null && $this->archived_at === null;
    }

    public function statusLabel(): string
    {
        return $this->archived_at ? 'Archiviert' : ($this->finalized_at ? 'Abgeschlossen' : ($this->published_at ? 'Einberufen' : 'Entwurf'));
    }
}
