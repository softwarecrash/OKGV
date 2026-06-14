<?php

namespace App\Policies;

use App\Enums\MailCampaignStatus;
use App\Models\MailCampaign;
use App\Models\User;

class MailCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageCommunication();
    }

    public function view(User $user, MailCampaign $campaign): bool
    {
        return $user->canManageCommunication();
    }

    public function create(User $user): bool
    {
        return $user->canManageCommunication();
    }

    public function send(User $user, MailCampaign $campaign): bool
    {
        return $user->canManageCommunication()
            && $campaign->status === MailCampaignStatus::Draft;
    }
}
