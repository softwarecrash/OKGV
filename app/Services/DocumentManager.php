<?php

namespace App\Services;

use App\Enums\DocumentVisibility;
use App\Enums\NumberSequenceType;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class DocumentManager
{
    public function __construct(
        private readonly NumberSequenceManager $numberSequenceManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, UploadedFile $file, User $actor): Document
    {
        $path = $file->store('documents', 'local');

        try {
            return DB::transaction(function () use ($data, $file, $actor, $path): Document {
                $document = Document::create([
                    'document_number' => $this->numberSequenceManager->next(
                        NumberSequenceType::Document,
                    ),
                    ...$this->metadata($data),
                    'uploaded_by' => $actor->id,
                    ...$this->fileMetadata($file, $path),
                    'current_version' => 1,
                ]);

                $this->createVersion($document, $file, $path, $actor, 1);
                AuditLogger::log('document.created', $actor, $document, [
                    'type' => $document->type->value,
                    'visibility' => $document->visibility->value,
                ]);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Document $document, array $data, ?UploadedFile $file, User $actor): Document
    {
        $path = $file?->store('documents', 'local');

        try {
            return DB::transaction(function () use ($document, $data, $file, $actor, $path): Document {
                $document = Document::query()->lockForUpdate()->findOrFail($document->id);
                $before = [
                    'type' => $document->type->value,
                    'visibility' => $document->visibility->value,
                    'published' => $document->published_at !== null,
                ];
                $attributes = $this->metadata($data, $document);

                if ($file && $path) {
                    $version = $document->current_version + 1;
                    $attributes = [
                        ...$attributes,
                        ...$this->fileMetadata($file, $path),
                        'current_version' => $version,
                    ];
                    $this->createVersion($document, $file, $path, $actor, $version);
                }

                $document->update($attributes);
                AuditLogger::log('document.updated', $actor, $document, [
                    'before' => $before,
                    'changed_fields' => array_keys($document->getChanges()),
                    'new_file_version' => $file ? $document->current_version : null,
                ]);

                return $document->refresh();
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function archive(Document $document, User $actor): void
    {
        $document->update([
            'published_at' => null,
            'public_token' => null,
            'archived_at' => now(),
        ]);

        AuditLogger::log('document.archived', $actor, $document);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function metadata(array $data, ?Document $document = null): array
    {
        $published = (bool) $data['published'];
        $visibility = DocumentVisibility::from($data['visibility']);
        $publicToken = $visibility === DocumentVisibility::Public && $published
            ? ($document?->public_token ?? Str::random(64))
            : null;

        return [
            'member_id' => $data['member_id'] ?? null,
            'parcel_id' => $data['parcel_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'visibility' => $visibility,
            'published_at' => $published ? ($document?->published_at ?? now()) : null,
            'public_token' => $publicToken,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileMetadata(UploadedFile $file, string $path): array
    {
        return [
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize(),
        ];
    }

    private function createVersion(
        Document $document,
        UploadedFile $file,
        string $path,
        User $actor,
        int $version,
    ): void {
        DocumentVersion::create([
            'document_id' => $document->id,
            'uploaded_by' => $actor->id,
            'version_number' => $version,
            ...$this->fileMetadata($file, $path),
        ]);
    }
}
