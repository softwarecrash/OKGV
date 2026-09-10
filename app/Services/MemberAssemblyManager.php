<?php

namespace App\Services;

use App\Enums\MemberAssemblyMode;
use App\Models\MemberAssembly;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class MemberAssemblyManager
{
    public function save(array $data, User $actor): MemberAssembly
    {
        return DB::transaction(function () use ($data, $actor) {
            Gate::forUser($actor)->authorize('create', MemberAssembly::class);
            $resolutions = $data['resolutions'];
            unset($data['resolutions']);
            $assembly = new MemberAssembly($data);
            $assembly->created_by = $actor->id;
            $assembly->save();
            foreach ($resolutions as $position => $resolution) {
                $assembly->resolutions()->create(['position' => $position, 'title' => $resolution['title'], 'body' => $resolution['body']]);
            } AuditLogger::log('member_assembly.saved', $actor, $assembly);

            return $assembly;
        });
    }

    public function publish(MemberAssembly $assembly, User $actor): void
    {
        DB::transaction(function () use ($assembly, $actor) {
            $assembly = MemberAssembly::query()->lockForUpdate()->findOrFail($assembly->id);
            Gate::forUser($actor)->authorize('publish', $assembly);
            if ($assembly->mode !== MemberAssemblyMode::InPerson && blank($assembly->electronic_rights_notice)) {
                throw ValidationException::withMessages(['electronic_rights_notice' => 'Bitte beschreibe in der Einladung den elektronischen Rechteweg.']);
            } if ($assembly->mode === MemberAssemblyMode::Virtual && blank($assembly->virtual_authorization_reference)) {
                throw ValidationException::withMessages(['virtual_authorization_reference' => 'Für eine virtuelle Versammlung ist die Satzungsregelung oder der Mitgliederbeschluss zu dokumentieren.']);
            }
            $assembly->forceFill(['published_at' => now()])->save();
            AuditLogger::log('member_assembly.published', $actor, $assembly);
        });
    }

    public function attend(MemberAssembly $assembly, User $actor): void
    {
        DB::transaction(function () use ($assembly, $actor) {
            $assembly = MemberAssembly::query()->lockForUpdate()->findOrFail($assembly->id);
            Gate::forUser($actor)->authorize('attend', $assembly);
            $member = $actor->member;
            $assembly->participations()->firstOrCreate(['member_id' => $member->id], ['user_id' => $actor->id, 'attendance_mode' => 'electronic', 'attended_at' => now()]);
            AuditLogger::log('member_assembly.attended', $actor, $assembly);
        });
    }

    public function vote(MemberAssembly $assembly, User $actor, array $data): void
    {
        DB::transaction(function () use ($assembly, $actor, $data) {
            $assembly = MemberAssembly::query()->lockForUpdate()->findOrFail($assembly->id);
            Gate::forUser($actor)->authorize('vote', $assembly);
            $member = $actor->member;
            if (! $assembly->participations()->where('member_id', $member->id)->exists() || ! $assembly->resolutions()->whereKey($data['resolution_id'])->exists()) {
                throw ValidationException::withMessages(['resolution_id' => 'Bitte nimm zuerst an der Versammlung teil und wähle einen gültigen Beschluss.']);
            }
            $resolution = $assembly->resolutions()->findOrFail($data['resolution_id']);
            if ($resolution->votes()->where('member_id', $member->id)->exists()) {
                throw ValidationException::withMessages(['resolution_id' => 'Für diesen Beschluss ist deine Stimme bereits protokolliert und kann nicht geändert werden.']);
            }
            $resolution->votes()->create(['member_id' => $member->id, 'user_id' => $actor->id, 'choice' => $data['choice'], 'voted_at' => now()]);
            AuditLogger::log('member_assembly.voted', $actor, $assembly);
        });
    }

    public function finalize(MemberAssembly $assembly, User $actor): void
    {
        DB::transaction(function () use ($assembly, $actor) {
            $assembly = MemberAssembly::query()->lockForUpdate()->findOrFail($assembly->id);
            Gate::forUser($actor)->authorize('finalize', $assembly);
            $snapshot = ['attendees' => $assembly->participations()->count(), 'resolutions' => $assembly->resolutions->map(fn ($r) => ['title' => $r->title, 'yes' => $r->votes()->where('choice', 'yes')->count(), 'no' => $r->votes()->where('choice', 'no')->count(), 'abstain' => $r->votes()->where('choice', 'abstain')->count()])->all()];
            $assembly->forceFill(['finalized_at' => now(), 'finalized_by' => $actor->id, 'final_minutes_snapshot' => $snapshot])->save();
            AuditLogger::log('member_assembly.finalized', $actor, $assembly);
        });
    }
}
