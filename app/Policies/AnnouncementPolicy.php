<?php

namespace App\Policies;

use App\Enums\FeatureModule;
use App\Enums\UserPermission;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return FeatureModule::Announcements->enabled() && $user->hasVerifiedEmail() && ! $user->hasPendingRegistrationApproval();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(UserPermission::ManageAnnouncements);
    }

    public function view(?User $user, Announcement $announcement): bool
    {
        return FeatureModule::Announcements->enabled()
            && (($user !== null && $this->create($user))
                || Announcement::query()->visibleTo($user)->whereKey($announcement->id)->exists());
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $this->create($user) && $announcement->published_at === null && $announcement->archived_at === null;
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    public function archive(User $user, Announcement $announcement): bool
    {
        return $this->create($user) && $announcement->archived_at === null;
    }

    public function acknowledge(User $user, Announcement $announcement): bool
    {
        return $this->viewAny($user) && Announcement::query()->visibleTo($user)->whereKey($announcement->id)->exists();
    }
}
