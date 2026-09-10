<?php

namespace App\Services;

use App\Enums\PollAudience;
use App\Enums\PollType;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class PollManager
{
    public function save(array $data, User $actor, ?Poll $poll = null): Poll
    {
        return DB::transaction(function () use ($data, $actor, $poll): Poll {
            $poll = $poll ? Poll::query()->lockForUpdate()->findOrFail($poll->id) : new Poll;
            Gate::forUser($actor)->authorize($poll->exists ? 'update' : 'create', $poll->exists ? $poll : Poll::class);
            $options = $data['options'];
            unset($data['options']);
            $poll->fill([...$data, 'roles' => $data['audience'] === PollAudience::Roles->value ? $data['roles'] : [], 'multiple' => $data['type'] === PollType::Dates->value || ($data['multiple'] ?? false)]);
            if (! $poll->exists) {
                $poll->created_by = $actor->id;
            }
            $poll->save();
            $poll->options()->delete();
            foreach ($options as $position => $option) {
                $poll->options()->create(['position' => $position, 'label' => $option['label'], 'starts_at' => $poll->type === PollType::Dates ? $option['starts_at'] : null, 'ends_at' => $poll->type === PollType::Dates ? ($option['ends_at'] ?? null) : null]);
            }
            AuditLogger::log('poll.saved', $actor, $poll);

            return $poll;
        });
    }

    public function transition(Poll $poll, User $actor, string $action): void
    {
        DB::transaction(function () use ($poll, $actor, $action): void {
            $poll = Poll::query()->lockForUpdate()->findOrFail($poll->id);
            Gate::forUser($actor)->authorize($action, $poll);
            if ($action === 'publish') {
                if ($poll->ends_at->lte(now()) || $poll->options()->count() < 2) {
                    throw ValidationException::withMessages(['poll' => 'Bitte prüfe zuerst den Zeitraum und mindestens zwei Antwortmöglichkeiten im Entwurf.']);
                }
                $users = $poll->candidates()->get()->reject(fn (User $user) => $user->hasPendingRegistrationApproval());
                if ($users->isEmpty()) {
                    throw ValidationException::withMessages(['poll' => 'In dieser Zielgruppe gibt es noch keine bestätigten und freigegebenen Benutzerkonten.']);
                }
                foreach ($users as $user) {
                    $poll->participations()->create(['user_id' => $user->id]);
                }
                $poll->published_at = now();
            } elseif ($action === 'close') {
                $poll->closed_at = now();
            } elseif ($action === 'archive') {
                $poll->archived_at = now();
            } elseif ($action === 'restore') {
                $poll->archived_at = null;
            }
            $poll->save();
            AuditLogger::log('poll.'.$action, $actor, $poll);
        });
    }

    public function answer(Poll $poll, User $actor, array $data): void
    {
        DB::transaction(function () use ($poll, $actor, $data): void {
            $poll = Poll::query()->lockForUpdate()->findOrFail($poll->id);
            Gate::forUser($actor)->authorize('answer', $poll);
            $ids = array_map('intval', $data['option_ids'] ?? []);
            $abstain = (bool) ($data['abstain'] ?? false);
            if (! ($data['confirmed'] ?? false) || (empty($ids) !== $abstain)
                || count($ids) !== count(array_unique($ids)) || (! $poll->multiple && count($ids) > 1)
                || count($ids) !== $poll->options()->whereKey($ids)->count()) {
                throw ValidationException::withMessages(['option_ids' => 'Wähle gültige Antworten oder ausdrücklich „Keine Auswahl“. Bei Einfachauswahl ist nur eine Antwort erlaubt.']);
            }
            $participation = $poll->participations()->where('user_id', $actor->id)->lockForUpdate()->firstOrFail();
            $participation->forceFill(['option_ids' => $ids, 'answered_at' => now()])->save();
            AuditLogger::log('poll.answered', $actor, $poll);
        });
    }

    public function results(Poll $poll, User $actor): array
    {
        Gate::forUser($actor)->authorize('results', $poll);
        $responses = $poll->participations()->whereNotNull('answered_at')->pluck('option_ids');
        $options = $poll->options()->get()->map(fn ($option) => ['label' => $option->label, 'starts_at' => $option->starts_at?->format('d.m.Y H:i'), 'ends_at' => $option->ends_at?->format('d.m.Y H:i'), 'count' => $responses->filter(fn ($ids) => in_array($option->id, $ids ?? [], true))->count()]);

        return ['eligible' => $poll->participations()->count(), 'answered' => $responses->count(), 'abstained' => $responses->filter(fn ($ids) => empty($ids))->count(), 'options' => $options];
    }
}
