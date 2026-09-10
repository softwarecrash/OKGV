<?php

namespace App\Policies;

use App\Enums\FeatureModule;
use App\Enums\PollResultsVisibility;
use App\Enums\UserPermission;
use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return FeatureModule::Polls->enabled() && $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(UserPermission::ManagePolls);
    }

    public function view(User $user, Poll $poll): bool
    {
        return $this->viewAny($user) && ($this->create($user) || Poll::query()->forParticipant($user)->whereKey($poll->id)->exists());
    }

    public function update(User $user, Poll $poll): bool
    {
        return $this->create($user) && $poll->published_at === null && $poll->archived_at === null;
    }

    public function publish(User $user, Poll $poll): bool
    {
        return $this->update($user, $poll);
    }

    public function close(User $user, Poll $poll): bool
    {
        return $this->create($user) && $poll->published_at !== null && ! $poll->isClosed() && $poll->archived_at === null;
    }

    public function archive(User $user, Poll $poll): bool
    {
        return $this->create($user) && $poll->archived_at === null && ($poll->published_at === null || $poll->isClosed());
    }

    public function restore(User $user, Poll $poll): bool
    {
        return $this->create($user) && $poll->archived_at !== null;
    }

    public function answer(User $user, Poll $poll): bool
    {
        return $this->viewAny($user) && Poll::query()->unansweredFor($user)->whereKey($poll->id)->exists();
    }

    public function results(User $user, Poll $poll): bool
    {
        return $poll->isClosed() && $this->view($user, $poll) && ($this->create($user) || $poll->results_visibility === PollResultsVisibility::Participants);
    }

    public function export(User $user, Poll $poll): bool
    {
        return $this->create($user) && $poll->isClosed();
    }
}
