<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'document_number',
    'member_id',
    'parcel_id',
    'uploaded_by',
    'title',
    'description',
    'type',
    'visibility',
    'file_path',
    'original_name',
    'mime_type',
    'file_size',
    'current_version',
    'published_at',
    'public_token',
    'archived_at',
])]
class Document extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('Documents must be archived and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'type' => DocumentType::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->archived_at === null;
    }
}
