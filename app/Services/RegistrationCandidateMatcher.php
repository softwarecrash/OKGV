<?php

namespace App\Services;

use App\Models\Member;
use App\Models\RegistrationRequest;
use Illuminate\Support\Collection;

final class RegistrationCandidateMatcher
{
    /**
     * @param  Collection<int, Member>  $members
     * @return Collection<int, Member>
     */
    public function rank(Collection $members, RegistrationRequest $registrationRequest): Collection
    {
        return $members
            ->each(function (Member $member) use ($registrationRequest): void {
                $emailMatches = $member->email !== null
                    && $this->normalize($member->email) === $this->normalize($registrationRequest->email);
                $firstNameMatches = $this->normalize($member->first_name)
                    === $this->normalize($registrationRequest->first_name);
                $lastNameMatches = $this->normalize($member->last_name)
                    === $this->normalize($registrationRequest->last_name);
                $score = ($emailMatches ? 100 : 0)
                    + ($firstNameMatches ? 20 : 0)
                    + ($lastNameMatches ? 30 : 0);

                $member->setAttribute('registration_match_score', $score);
                $member->setAttribute('registration_email_matches', $emailMatches);
                $member->setAttribute('registration_first_name_matches', $firstNameMatches);
                $member->setAttribute('registration_last_name_matches', $lastNameMatches);
            })
            ->sortByDesc(fn (Member $member): int => $member->registration_match_score)
            ->values()
            ->each(function (Member $member, int $index): void {
                $member->setAttribute(
                    'registration_recommended',
                    $index === 0 && $member->registration_match_score > 0,
                );
            });
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
