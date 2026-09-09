<?php

namespace App\Services;

use App\Enums\BoardResolutionResult;
use App\Enums\FeatureModule;
use App\Models\BoardAgendaItem;
use App\Models\BoardFollowUp;
use App\Models\BoardMeeting;
use App\Models\BoardResolution;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BoardMeetingManager
{
    public function __construct(private readonly BoardMeetingPdfGenerator $pdf, private readonly AssociationDocumentProfile $profile) {}

    public function save(array $data, User $actor, ?BoardMeeting $meeting = null): BoardMeeting
    {
        return DB::transaction(function () use ($data, $actor, $meeting): BoardMeeting {
            $meeting = $meeting ? BoardMeeting::query()->lockForUpdate()->findOrFail($meeting->id) : new BoardMeeting;
            Gate::forUser($actor)->authorize($meeting->exists ? 'update' : 'create', $meeting->exists ? $meeting : BoardMeeting::class);
            $ids = $data['document_ids'] ?? [];
            unset($data['document_ids']);
            if (FeatureModule::Documents->enabled()) {
                // Editing meeting text must not silently remove links the editor cannot see.
                if ($meeting->exists) {
                    $hiddenIds = $meeting->documents()->lockForUpdate()->get()
                        ->reject(fn (Document $document) => $actor->can('view', $document))->modelKeys();
                } else {
                    $hiddenIds = [];
                }
                $documents = Document::query()->whereKey($ids)->lockForUpdate()->get();
                if ($documents->count() !== count($ids) || $documents->contains(fn (Document $document) => ! $actor->can('view', $document))) {
                    throw ValidationException::withMessages(['document_ids' => 'Bitte nur Dokumente auswählen, auf die du Zugriff hast.']);
                }
            }
            $meeting->fill($data);
            if (! $meeting->exists) {
                $meeting->created_by = $actor->id;
            }
            $meeting->save();
            if (FeatureModule::Documents->enabled()) {
                $meeting->documents()->sync(array_unique([...$ids, ...$hiddenIds]));
            }
            AuditLogger::log('board_meeting.saved', $actor, $meeting);

            return $meeting;
        });
    }

    public function saveAgenda(BoardMeeting $meeting, array $data, User $actor, ?BoardAgendaItem $item = null): void
    {
        DB::transaction(function () use ($meeting, $data, $actor, $item): void {
            $meeting = $this->editable($meeting, $actor);
            $item = $item ? $meeting->agendaItems()->findOrFail($item->id) : $meeting->agendaItems()->make();
            if (! $item->exists && $meeting->agendaItems()->count() >= 50) {
                throw ValidationException::withMessages(['title' => 'Eine Sitzung kann höchstens 50 Tagesordnungspunkte enthalten. Bitte weitere Themen in einer neuen Sitzung erfassen.']);
            }
            if ($meeting->agendaItems()->where('position', $data['position'])->when($item->exists, fn ($query) => $query->where('id', '!=', $item->id))->exists()) {
                throw ValidationException::withMessages(['position' => 'Diese Position ist bereits belegt. Bitte eine freie Nummer wählen.']);
            }
            $item->fill($data)->save();
            AuditLogger::log('board_agenda.saved', $actor, $item);
        });
    }

    public function saveResolution(BoardMeeting $meeting, array $data, User $actor, ?BoardResolution $resolution = null): void
    {
        DB::transaction(function () use ($meeting, $data, $actor, $resolution): void {
            $meeting = $this->editable($meeting, $actor);
            if (! empty($data['board_agenda_item_id']) && ! $meeting->agendaItems()->whereKey($data['board_agenda_item_id'])->exists()) {
                throw ValidationException::withMessages(['board_agenda_item_id' => 'Bitte ein Thema dieser Sitzung auswählen.']);
            }
            $resolution = $resolution ? $meeting->resolutions()->findOrFail($resolution->id) : $meeting->resolutions()->make();
            if (! $resolution->exists && $meeting->resolutions()->count() >= 100) {
                throw ValidationException::withMessages(['title' => 'Eine Sitzung kann höchstens 100 Beschlüsse enthalten. Bitte eine neue Sitzung anlegen.']);
            }
            $resolution->fill($data)->save();
            AuditLogger::log('board_resolution.saved', $actor, $resolution);
        });
    }

    public function finalize(BoardMeeting $meeting, User $actor): void
    {
        $path = null;
        try {
            DB::transaction(function () use ($meeting, $actor, &$path): void {
                $meeting = $this->editable($meeting, $actor);
                if (! filled($meeting->attendees) || ! filled($meeting->minutes) || ! $meeting->agendaItems()->exists() || $meeting->scheduled_at->isFuture()) {
                    throw ValidationException::withMessages(['minutes' => 'Vor dem Abschluss müssen Teilnehmer, Protokoll und mindestens ein Tagesordnungspunkt erfasst sein. Der Sitzungstermin darf nicht in der Zukunft liegen.']);
                }
                $meeting->finalized_at = now();
                $meeting->finalized_by = $actor->id;
                $meeting->association_snapshot = Arr::only($this->profile->snapshot(), ['name', 'street', 'zip', 'city', 'logo_path', 'logo_mime']);
                $path = 'board-meetings/'.Str::uuid().'.pdf';
                if (! Storage::disk('local')->put($path, $this->pdf->render($meeting))) {
                    throw ValidationException::withMessages(['minutes' => 'Die PDF-Datei konnte nicht gespeichert werden. Die Sitzung bleibt im Entwurf. Bitte Speicherplatz und Dateirechte prüfen lassen.']);
                }
                $meeting->pdf_path = $path;
                $meeting->save();
                AuditLogger::log('board_meeting.finalized', $actor, $meeting);
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function archive(BoardMeeting $meeting, User $actor): void
    {
        DB::transaction(function () use ($meeting, $actor): void {
            $meeting = BoardMeeting::query()->lockForUpdate()->findOrFail($meeting->id);
            Gate::forUser($actor)->authorize('archive', $meeting);
            $meeting->archived_at = now();
            $meeting->save();
            AuditLogger::log('board_meeting.archived', $actor, $meeting);
        });
    }

    public function addFollowUp(BoardResolution $resolution, array $data, User $actor): BoardFollowUp
    {
        return DB::transaction(function () use ($resolution, $data, $actor): BoardFollowUp {
            $meeting = BoardMeeting::query()->lockForUpdate()->findOrFail($resolution->board_meeting_id);
            Gate::forUser($actor)->authorize('create', BoardMeeting::class);
            $resolution = $meeting->resolutions()->findOrFail($resolution->id);
            if (! $meeting->finalized_at || $resolution->result !== BoardResolutionResult::Adopted) {
                throw ValidationException::withMessages(['title' => 'Aufgaben können nur aus angenommenen Beschlüssen abgeschlossener Sitzungen entstehen.']);
            }
            if (! empty($data['assigned_to'])) {
                $assignee = User::query()->lockForUpdate()->find($data['assigned_to']);
                if (! $assignee || ! $assignee->can('viewAny', BoardMeeting::class)) {
                    throw ValidationException::withMessages(['assigned_to' => 'Bitte eine Person mit Zugriff auf Vorstandsunterlagen auswählen.']);
                }
            }
            $task = $resolution->followUps()->make($data);
            $task->created_by = $actor->id;
            $task->save();
            AuditLogger::log('board_follow_up.created', $actor, $task);

            return $task;
        });
    }

    public function unarchive(BoardMeeting $meeting, User $actor): void
    {
        DB::transaction(function () use ($meeting, $actor): void {
            $meeting = BoardMeeting::query()->lockForUpdate()->findOrFail($meeting->id);
            Gate::forUser($actor)->authorize('unarchive', $meeting);
            $meeting->archived_at = null;
            $meeting->save();
            AuditLogger::log('board_meeting.unarchived', $actor, $meeting);
        });
    }

    public function complete(BoardFollowUp $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor): void {
            $task = BoardFollowUp::query()->lockForUpdate()->findOrFail($task->id);
            Gate::forUser($actor)->authorize('complete', $task);
            $task->completed_at = now();
            $task->completed_by = $actor->id;
            $task->save();
            AuditLogger::log('board_follow_up.completed', $actor, $task);
        });
    }

    private function editable(BoardMeeting $meeting, User $actor): BoardMeeting
    {
        $meeting = BoardMeeting::query()->lockForUpdate()->findOrFail($meeting->id);
        Gate::forUser($actor)->authorize('update', $meeting);

        return $meeting;
    }
}
