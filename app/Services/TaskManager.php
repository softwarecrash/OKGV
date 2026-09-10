<?php

namespace App\Services;

use App\Enums\BoardResolutionResult;
use App\Enums\FeatureModule;
use App\Enums\TaskRecurrence;
use App\Models\BoardResolution;
use App\Models\Document;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class TaskManager
{
    public function save(array $data, User $actor, ?Task $task = null): Task
    {
        return DB::transaction(function () use ($data, $actor, $task): Task {
            $task = $task ? Task::query()->lockForUpdate()->findOrFail($task->id) : new Task;
            $actor = $actor->fresh();
            Gate::forUser($actor)->authorize($task->exists ? 'update' : 'create', $task->exists ? $task : Task::class);
            if (! $task->exists) {
                $task->board_resolution_id = $data['board_resolution_id'] ?? null;
                if ($task->board_resolution_id !== null) {
                    $resolution = BoardResolution::query()->findOrFail($task->board_resolution_id);
                    if (! $actor->can('view', $resolution->meeting) || ! $resolution->meeting->finalized_at || $resolution->result !== BoardResolutionResult::Adopted) {
                        throw ValidationException::withMessages(['board_resolution_id' => 'Bitte einen angenommenen Beschluss einer abgeschlossenen, für dich zugreifbaren Sitzung auswählen.']);
                    }
                }
            }
            unset($data['board_resolution_id']);
            foreach (['member_id' => Member::class, 'parcel_id' => Parcel::class, 'document_id' => Document::class] as $field => $model) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $task->$field) {
                    if ($field === 'document_id' && ! FeatureModule::Documents->enabled()) {
                        throw ValidationException::withMessages([$field => 'Das Dokumentmodul ist deaktiviert. Die vorhandene Verknüpfung bleibt erhalten.']);
                    }
                    if ($task->$field && ! $actor->can('view', $model::findOrFail($task->$field))) {
                        throw ValidationException::withMessages([$field => 'Für das Ändern dieser Verknüpfung fehlt dir die Leseberechtigung.']);
                    }
                    if (! empty($data[$field]) && ! $actor->can('view', $model::findOrFail($data[$field]))) {
                        throw ValidationException::withMessages([$field => 'Bitte einen Datensatz auswählen, auf den du Zugriff hast.']);
                    }
                }
            }
            $task->fill($data);
            if (! empty($task->assigned_to)) {
                $assignee = User::query()->lockForUpdate()->find($task->assigned_to);
                if (! $assignee || ! $assignee->can('view', $task)) {
                    throw ValidationException::withMessages(['assigned_to' => 'Die ausgewählte Person benötigt Zugriff auf diese Aufgabenart. Bei Beschlussaufgaben ist zusätzlich Vorstandsleserecht nötig.']);
                }
            }
            if (! $task->exists || $task->isDirty(['recurrence', 'due_at'])) {
                $task->recurrence_anchor = $task->due_at;
                $task->occurrence = 0;
            }
            $created = ! $task->exists;
            if ($created) {
                $task->created_by = $actor->id;
            }
            $fields = array_keys($task->getDirty());
            $task->save();
            if ($fields !== []) {
                $this->record($task, $actor, $created ? 'created' : 'updated', $fields);
            }

            return $task;
        });
    }

    public function complete(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor): void {
            $task = $task->newQuery()->lockForUpdate()->findOrFail($task->id);
            $actor = $actor->fresh();
            Gate::forUser($actor)->authorize('complete', $task);
            if ($task->recurrence !== TaskRecurrence::None && ! FeatureModule::Tasks->enabled()) {
                throw ValidationException::withMessages(['recurrence' => 'Für wiederkehrende Aufgaben muss das Aufgabenmodul aktiviert sein.']);
            }
            if ($task->recurrence !== TaskRecurrence::None) {
                $next = $task->replicate(['started_at', 'completed_at', 'completed_by', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'archived_at']);
                $next->setRelations([]);
                $next->previous_task_id = $task->id;
                $next->recurrence_anchor = $task->recurrence_anchor ?? $task->due_at;
                $next->occurrence = $task->occurrence + 1;
                $next->due_at = $task->recurrence->date($next->recurrence_anchor, $next->occurrence);
                if ($next->due_at->year >= 9999) {
                    throw ValidationException::withMessages(['recurrence' => 'Der nächste Termin liegt außerhalb des zulässigen Zeitraums. Bitte die Wiederholung beenden.']);
                }
                $next->remind_at = $task->remind_at ? $next->due_at->copy()->subDays((int) $task->remind_at->diffInDays($task->due_at)) : null;
                $next->created_by = $actor->id;
                $next->save();
                $this->record($next, $actor, 'repeated');
            }
            $task->completed_at = now();
            $task->completed_by = $actor->id;
            $task->save();
            $this->record($task, $actor, 'completed');
            if ($task->board_resolution_id !== null) {
                AuditLogger::log('board_follow_up.completed', $actor, $task);
            }
        });
    }

    public function act(Task $task, User $actor, string $action, ?string $reason = null): void
    {
        DB::transaction(function () use ($task, $actor, $action, $reason): void {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $actor = $actor->fresh();
            Gate::forUser($actor)->authorize($action, $task);
            $event = match ($action) {
                'start' => 'started', 'cancel' => 'cancelled', 'archive' => 'archived', 'restore' => 'restored',
                default => throw new \LogicException('Unsupported task action.'),
            };
            if ($action === 'cancel') {
                if (! filled($reason)) {
                    throw ValidationException::withMessages(['cancel_reason' => 'Bitte einen Grund für den Abbruch eintragen.']);
                }
                $task->cancelled_at = now();
                $task->cancelled_by = $actor->id;
                $task->cancel_reason = $reason;
            } elseif ($action === 'start') {
                $task->started_at = now();
            } else {
                $task->archived_at = $action === 'archive' ? now() : null;
            }
            $task->save();
            $this->record($task, $actor, $event);
        });
    }

    public function record(Task $task, User $actor, string $action, array $fields = []): void
    {
        $task->events()->create(['user_id' => $actor->id, 'action' => $action, 'changed_fields' => $fields, 'created_at' => now()]);
        AuditLogger::log('task.'.$action, $actor, $task, ['changed_fields' => $fields]);
    }
}
