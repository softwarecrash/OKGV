<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'document_id',
    'uploaded_by',
    'version_number',
    'file_path',
    'original_name',
    'mime_type',
    'file_size',
])]
class DocumentVersion extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Document versions are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Document versions cannot be deleted.');
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
