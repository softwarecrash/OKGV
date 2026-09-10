<?php

namespace Tests\Feature;

use App\Enums\PollAudience;
use App\Enums\PollResultsVisibility;
use App\Enums\PollType;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\ParcelTenant;
use App\Models\Poll;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Services\ActionIndicatorService;
use App\Services\PrivacyDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PollWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function activeMember(User $user): Member
    {
        return Member::factory()->create(['user_id' => $user->id, 'joined_at' => now()->subYear()]);
    }

    private function draft(array $attributes = []): Poll
    {
        $poll = Poll::create([
            'title' => 'Welcher Termin passt?',
            'type' => PollType::Survey,
            'audience' => PollAudience::Members,
            'results_visibility' => PollResultsVisibility::Participants,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
            ...$attributes,
        ]);
        $poll->options()->createMany([
            ['position' => 0, 'label' => 'Samstag'],
            ['position' => 1, 'label' => 'Sonntag'],
        ]);

        return $poll;
    }

    public function test_board_can_publish_a_poll_and_only_snapshots_current_eligible_accounts(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $member = User::factory()->create();
        $this->activeMember($member);
        $pending = User::factory()->create();
        $this->activeMember($pending);
        RegistrationRequest::factory()->create(['user_id' => $pending->id, 'email' => $pending->email]);
        $poll = $this->draft();

        $this->actingAs($board)->post(route('polls.publish', $poll), ['confirmed' => 1])->assertRedirect();

        $this->assertNotNull($poll->fresh()->published_at);
        $this->assertDatabaseHas('poll_participations', ['poll_id' => $poll->id, 'user_id' => $member->id]);
        $this->assertDatabaseMissing('poll_participations', ['poll_id' => $poll->id, 'user_id' => $pending->id]);
        $lateMember = User::factory()->create();
        $this->activeMember($lateMember);
        $this->actingAs($lateMember)->get(route('polls.show', $poll))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'poll.publish', 'subject_id' => $poll->id]);
    }

    public function test_participant_can_answer_once_and_the_indicator_clears(): void
    {
        $manager = User::factory()->administrator()->create();
        $user = User::factory()->create();
        $member = $this->activeMember($user);
        $poll = $this->draft();
        $this->actingAs($manager)->post(route('polls.publish', $poll), ['confirmed' => 1]);
        $poll->refresh();
        $option = $poll->options()->firstOrFail();
        $indicators = app(ActionIndicatorService::class);
        $this->assertSame(1, $indicators->forUser($user)['polls']);
        $this->assertSame(1, $indicators->forTenantPortal($user)['polls']);

        $this->actingAs($user)->post(route('polls.answer', $poll), ['option_ids' => [$option->id], 'confirmed' => 1])->assertRedirect();
        $this->assertSame(0, $indicators->forUser($user)['polls']);
        $this->assertSame(0, $indicators->forTenantPortal($user)['polls']);
        $this->post(route('polls.answer', $poll), ['option_ids' => [$option->id], 'confirmed' => 1])->assertForbidden();
        $this->assertCount(1, app(PrivacyDataExportService::class)->build($member)['poll_participations']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'poll.answered', 'subject_id' => $poll->id]);
    }

    public function test_invalid_or_foreign_answers_are_rejected_and_abstention_is_explicit(): void
    {
        $manager = User::factory()->administrator()->create();
        $user = User::factory()->create();
        $this->activeMember($user);
        $poll = $this->draft();
        $foreign = $this->draft();
        $this->actingAs($manager)->post(route('polls.publish', $poll), ['confirmed' => 1]);
        $this->actingAs($user)->post(route('polls.answer', $poll), ['option_ids' => [$foreign->options()->first()->id], 'confirmed' => 1])->assertSessionHasErrors('option_ids');
        $this->post(route('polls.answer', $poll), ['abstain' => 1, 'option_ids' => [$poll->options()->first()->id], 'confirmed' => 1])->assertSessionHasErrors('option_ids');
        $this->post(route('polls.answer', $poll), ['abstain' => 1, 'confirmed' => 1])->assertRedirect();
        $this->assertSame([], $poll->participations()->where('user_id', $user->id)->firstOrFail()->option_ids);
    }

    public function test_results_are_hidden_until_closed_and_can_be_confidential(): void
    {
        $manager = User::factory()->administrator()->create();
        $participant = User::factory()->create();
        $this->activeMember($participant);
        $poll = $this->draft(['results_visibility' => PollResultsVisibility::Managers]);
        $this->actingAs($manager)->post(route('polls.publish', $poll), ['confirmed' => 1]);
        $this->actingAs($participant)->get(route('polls.show', $poll))->assertOk()->assertSee('Ergebnisse werden erst')->assertDontSee('Antworten von');
        $this->actingAs($manager)->post(route('polls.close', $poll), ['confirmed' => 1]);
        $this->actingAs($participant)->get(route('polls.show', $poll))->assertOk()->assertSee('nur für die Umfrageverwaltung')->assertDontSee('Antworten von');
        $this->actingAs($manager)->get(route('polls.show', $poll))->assertOk()->assertSee('Antworten von');
        $this->actingAs($participant)->get(route('polls.export', $poll))->assertForbidden();
    }

    public function test_date_polls_allow_multiple_options_and_respect_current_tenancy(): void
    {
        $manager = User::factory()->administrator()->create();
        $tenant = User::factory()->create();
        $member = $this->activeMember($tenant);
        $tenancy = ParcelTenant::factory()->create(['member_id' => $member->id, 'starts_at' => now()->subMonth()]);
        $poll = $this->draft(['type' => PollType::Dates, 'audience' => PollAudience::Tenants]);
        $poll->options()->first()->update(['starts_at' => now()->addWeeks(2)]);
        $poll->options()->skip(1)->first()->update(['starts_at' => now()->addWeeks(3)]);
        $this->actingAs($manager)->post(route('polls.publish', $poll), ['confirmed' => 1]);
        $ids = $poll->options()->pluck('id')->all();
        $this->actingAs($tenant)->post(route('polls.answer', $poll), ['option_ids' => $ids, 'confirmed' => 1])->assertRedirect();
        $tenancy->update(['ends_at' => now()->subDay()]);
        $this->get(route('polls.show', $poll))->assertForbidden();
    }

    public function test_permissions_modules_and_lifecycle_protect_polls(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board, 'permissions' => []]);
        $poll = $this->draft();
        $this->actingAs($board)->get(route('polls.create'))->assertForbidden();
        $board->update(['permissions' => [UserPermission::ManagePolls->value]]);
        $this->get(route('polls.create'))->assertOk();
        $this->post(route('polls.publish', $poll), ['confirmed' => 1])->assertSessionHasErrors('poll');
        $member = User::factory()->create();
        $this->activeMember($member);
        $this->post(route('polls.publish', $poll), ['confirmed' => 1]);
        $this->put(route('polls.update', $poll), [])->assertForbidden();
        $this->post(route('polls.archive', $poll), ['confirmed' => 1])->assertForbidden();
        $this->post(route('polls.close', $poll), ['confirmed' => 1])->assertRedirect();
        $this->post(route('polls.archive', $poll), ['confirmed' => 1])->assertRedirect();
        config()->set('modules.polls', false);
        $this->get(route('polls.index'))->assertNotFound();
    }

    public function test_poll_migrations_roll_back_and_migrate_again_in_isolated_database(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $migrations = array_map(fn ($path) => require $path, glob(database_path('migrations/2026_09_10_120*.php')));
        $this->assertCount(3, $migrations);
        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }
        $this->assertFalse(Schema::hasTable('polls'));
        foreach ($migrations as $migration) {
            $migration->up();
        }
        $this->assertTrue(Schema::hasTable('poll_participations'));
    }
}
