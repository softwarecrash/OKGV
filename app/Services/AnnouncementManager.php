<?php

namespace App\Services;

use App\Enums\AnnouncementAudience;
use App\Enums\DocumentVisibility;
use App\Enums\FeatureModule;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AnnouncementManager
{
    public function save(array $data, User $actor, ?Announcement $announcement = null): Announcement
    {
        return DB::transaction(function () use ($data, $actor, $announcement): Announcement {
            $announcement = $announcement === null ? new Announcement : Announcement::query()->lockForUpdate()->findOrFail($announcement->id);
            Gate::forUser($actor)->authorize($announcement->exists ? 'update' : 'create', $announcement->exists ? $announcement : Announcement::class);
            $documentIds = $data['document_ids'] ?? [];
            unset($data['document_ids']);
            $announcement->fill([
                ...$data,
                'roles' => $data['audience'] === AnnouncementAudience::Roles->value ? $data['roles'] : [],
                'is_highlighted' => $data['is_highlighted'] ?? false,
                'requires_confirmation' => $data['requires_confirmation'] ?? false,
            ]);
            if (! $announcement->exists) {
                $announcement->created_by = $actor->id;
            }
            if (FeatureModule::Documents->enabled()) {
                $this->validateDocuments($documentIds, $announcement, $actor);
            }
            $announcement->save();
            if (FeatureModule::Documents->enabled()) {
                $announcement->documents()->sync($documentIds);
            }
            AuditLogger::log('announcement.saved', $actor, $announcement);

            return $announcement;
        });
    }

    public function publish(Announcement $announcement, User $actor): void
    {
        DB::transaction(function () use ($announcement, $actor): void {
            $announcement = Announcement::query()->lockForUpdate()->findOrFail($announcement->id);
            Gate::forUser($actor)->authorize('publish', $announcement);
            if ($announcement->ends_at?->lte(now())) {
                throw ValidationException::withMessages(['ends_at' => 'Das Ende liegt bereits in der Vergangenheit. Bitte bearbeite zuerst den Zeitraum.']);
            }
            if (FeatureModule::Documents->enabled()) {
                $this->validateDocuments($announcement->documents()->pluck('documents.id')->all(), $announcement, $actor);
            }
            $announcement->update(['published_at' => now()]);
            AuditLogger::log('announcement.published', $actor, $announcement);
        });
    }

    public function archive(Announcement $announcement, User $actor): void
    {
        DB::transaction(function () use ($announcement, $actor): void {
            $announcement = Announcement::query()->lockForUpdate()->findOrFail($announcement->id);
            Gate::forUser($actor)->authorize('archive', $announcement);
            $announcement->update(['archived_at' => now()]);
            AuditLogger::log('announcement.archived', $actor, $announcement);
        });
    }

    public function acknowledge(Announcement $announcement, User $actor): void
    {
        DB::transaction(function () use ($announcement, $actor): void {
            $announcement = Announcement::query()->lockForUpdate()->findOrFail($announcement->id);
            Gate::forUser($actor)->authorize('acknowledge', $announcement);
            $read = $announcement->reads()->firstOrCreate(['user_id' => $actor->id], ['read_at' => now()]);
            if ($read->wasRecentlyCreated) {
                AuditLogger::log('announcement.read', $actor, $announcement);
            }
        });
    }

    private function validateDocuments(array $ids, Announcement $announcement, User $actor): void
    {
        $documents = Document::query()->whereKey($ids)->lockForUpdate()->get();
        if ($documents->count() !== count($ids)) {
            throw ValidationException::withMessages(['document_ids' => 'Ein ausgewähltes Dokument existiert nicht mehr. Bitte wähle erneut.']);
        }
        foreach ($documents as $document) {
            if (! $document->isPublished() || ! $actor->can('view', $document)
                || ($announcement->audience === AnnouncementAudience::Public
                    && ($document->visibility !== DocumentVisibility::Public || ! $document->public_token))) {
                throw ValidationException::withMessages(['document_ids' => 'Verknüpfe nur freigegebene Dokumente mit eigener Zugriffsberechtigung. Für öffentliche Beiträge müssen auch die Dokumente öffentlich freigegeben sein.']);
            }
        }
    }
}
