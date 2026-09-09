<?php

namespace Tests\Feature;

use App\Enums\BoardResolutionResult;
use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\BoardFollowUp;
use App\Models\BoardMeeting;
use App\Models\Document;
use App\Models\Member;
use App\Models\User;
use App\Services\ActionIndicatorService;
use App\Services\BoardMeetingManager;
use App\Services\PrivacyDataExportService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class BoardWorkWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function board(): User
    {
        return User::factory()->create(['role' => UserRole::Board]);
    }

    private function prepared(): BoardMeeting
    {
        $meeting = BoardMeeting::factory()->create();
        $topic = $meeting->agendaItems()->create(['title' => 'Wegebau', 'position' => 1, 'minutes' => 'Angebote wurden verglichen.']);
        $meeting->resolutions()->create(['title' => 'Weg instand setzen', 'body' => 'Der Vorstand beauftragt die Reparatur.', 'result' => BoardResolutionResult::Adopted, 'board_agenda_item_id' => $topic->id]);

        return $meeting;
    }

    private function finalized(User $board): BoardMeeting
    {
        Storage::fake('local');
        $meeting = $this->prepared();
        app(BoardMeetingManager::class)->finalize($meeting, $board);

        return $meeting->fresh();
    }

    public function test_board_can_plan_topics_record_minutes_and_search_finalized_resolutions(): void
    {
        Storage::fake('local');
        $board = $this->board();
        $this->actingAs($board)->get(route('board-meetings.create'))->assertOk();
        $this->post(route('board-meetings.store'), ['title' => 'Herbstsitzung', 'scheduled_at' => now()->subDay()->toDateTimeString()])->assertRedirect();
        $meeting = BoardMeeting::query()->firstOrFail();
        $this->get(route('board-meetings.edit', $meeting))->assertOk();
        $this->put(route('board-meetings.update', $meeting), ['title' => 'Herbstsitzung', 'scheduled_at' => $meeting->scheduled_at->toDateTimeString(), 'attendees' => 'Vorstand vollständig', 'minutes' => 'Beschlussfähig.'])->assertRedirect();
        $this->get(route('board-agenda.create', $meeting))->assertOk();
        $this->post(route('board-agenda.store', $meeting), ['title' => 'Wege', 'position' => 1])->assertRedirect();
        $topic = $meeting->agendaItems()->firstOrFail();
        $this->get(route('board-agenda.edit', [$meeting, $topic]))->assertOk();
        $this->put(route('board-agenda.update', [$meeting, $topic]), ['title' => 'Wegebau', 'position' => 2, 'minutes' => 'Diskussion abgeschlossen.'])->assertRedirect();
        $this->get(route('board-resolutions.create', $meeting))->assertOk();
        $this->post(route('board-resolutions.store', $meeting), ['title' => 'Reparatur beschließen', 'body' => '<script>alert(1)</script> Weg reparieren.', 'result' => 'adopted', 'board_agenda_item_id' => $topic->id, 'votes_for' => 3, 'votes_against' => 0, 'votes_abstained' => 0])->assertRedirect();
        $resolution = $meeting->resolutions()->firstOrFail();
        $this->get(route('board-resolutions.edit', [$meeting, $resolution]))->assertOk();
        $this->get(route('board-meetings.show', $meeting))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('board-meetings.book'))->assertOk()->assertDontSee('Reparatur beschließen');
        $this->post(route('board-meetings.finalize', $meeting), ['confirmed' => 1])->assertRedirect();
        $this->get(route('board-meetings.book', ['q' => 'Reparatur']))->assertOk()->assertSee('Reparatur beschließen');
        $this->get(route('board-meetings.book', ['result' => 'rejected']))->assertOk()->assertDontSee('Reparatur beschließen');
        $this->get(route('board-meetings.index'))->assertOk()->assertSee('Herbstsitzung');
        $this->assertDatabaseHas('audit_logs', ['action' => 'board_meeting.finalized', 'subject_id' => $meeting->id]);
    }

    public function test_tenants_and_unprivileged_board_members_cannot_access_confidential_routes(): void
    {
        $meeting = $this->prepared();
        $this->get(route('board-meetings.show', $meeting))->assertRedirect(route('login'));
        foreach ([UserRole::Tenant, UserRole::Treasurer, UserRole::Board] as $role) {
            $user = User::factory()->create(['role' => $role, 'permissions' => []]);
            $this->actingAs($user)->get(route('board-meetings.index'))->assertForbidden();
            $this->get(route('board-meetings.show', $meeting))->assertForbidden();
            $this->get(route('board-meetings.book'))->assertForbidden();
            $this->get(route('board-meetings.pdf', $meeting))->assertForbidden();
            $this->post(route('board-meetings.finalize', $meeting), ['confirmed' => 1])->assertForbidden();
            $this->get(route('home'))->assertOk()->assertDontSee(route('board-meetings.index'));
        }
        $this->assertDatabaseMissing('audit_logs', ['action' => 'board_meeting.finalized']);
    }

    public function test_read_permission_does_not_grant_management_and_custom_snapshots_stay_unchanged(): void
    {
        $reader = User::factory()->create(['role' => UserRole::Board, 'permissions' => [UserPermission::ViewBoardWork->value]]);
        $meeting = $this->prepared();
        $this->actingAs($reader)->get(route('board-meetings.show', $meeting))->assertOk()->assertDontSee('Sitzung / Protokoll bearbeiten');
        $this->get(route('board-meetings.create'))->assertForbidden();
        $this->put(route('board-meetings.update', $meeting), [])->assertForbidden();
        $this->post(route('board-meetings.archive', $meeting))->assertForbidden();
        $this->assertSame([UserPermission::ViewBoardWork->value], $reader->fresh()->permissions);
        $this->assertContains(UserPermission::ViewBoardWork->value, UserPermission::expandDependencies([UserPermission::ManageBoardWork->value]));
    }

    public function test_cross_meeting_topics_and_resolution_updates_are_rejected(): void
    {
        $first = $this->prepared();
        $second = $this->prepared();
        $topic = $second->agendaItems()->firstOrFail();
        $resolution = $second->resolutions()->firstOrFail();
        $this->actingAs($this->board())->get(route('board-agenda.edit', [$first, $topic]))->assertNotFound();
        $this->put(route('board-agenda.update', [$first, $topic]), ['title' => 'Fremd', 'position' => 5])->assertNotFound();
        $this->post(route('board-resolutions.store', $first), ['title' => 'Test', 'body' => 'Test', 'result' => 'adopted', 'board_agenda_item_id' => $topic->id])->assertSessionHasErrors('board_agenda_item_id');
        $this->put(route('board-resolutions.update', [$first, $resolution]), ['title' => 'Test', 'body' => 'Test', 'result' => 'adopted'])->assertNotFound();
        $this->assertSame('Weg instand setzen', $resolution->fresh()->title);
    }

    public function test_duplicate_positions_invalid_votes_and_incomplete_finalization_are_understandable(): void
    {
        $meeting = $this->prepared();
        $this->actingAs($this->board())->post(route('board-agenda.store', $meeting), ['title' => 'Doppelt', 'position' => 1])->assertSessionHasErrors('position');
        $this->post(route('board-resolutions.store', $meeting), ['title' => 'Test', 'body' => 'Text', 'result' => 'adopted', 'votes_for' => 0])->assertSessionHasErrors(['votes_against', 'votes_abstained']);
        $this->post(route('board-meetings.finalize', $meeting))->assertSessionHasErrors('confirmed');
        $meeting->update(['scheduled_at' => now()->addDay()]);
        $this->post(route('board-meetings.finalize', $meeting), ['confirmed' => 1])->assertSessionHasErrors('minutes');
        $empty = BoardMeeting::factory()->create(['minutes' => null]);
        $this->post(route('board-meetings.finalize', $empty), ['confirmed' => 1])->assertSessionHasErrors('minutes');
        $this->assertNull($meeting->fresh()->finalized_at);
        $this->assertDatabaseCount('board_agenda_items', 1);
    }

    public function test_finalized_pdf_is_private_immutable_and_survives_settings_changes(): void
    {
        $board = $this->board();
        $meeting = $this->finalized($board);
        $original = Storage::disk('local')->get($meeting->pdf_path);
        $this->assertStringStartsWith('%PDF-', $original);
        $this->assertArrayNotHasKey('bank_iban', $meeting->association_snapshot);
        ApplicationSetting::current()->update(['association_name' => 'Späterer Vereinsname']);
        $this->actingAs($board)->get(route('board-meetings.pdf', $meeting))->assertOk()->assertDownload('vorstandsprotokoll-'.$meeting->id.'.pdf');
        $this->assertSame($original, Storage::disk('local')->get($meeting->pdf_path));
        $this->put(route('board-meetings.update', $meeting), [])->assertForbidden();
        $this->post(route('board-agenda.store', $meeting), ['position' => 2, 'title' => 'Nachtrag'])->assertForbidden();
        $this->post(route('board-resolutions.store', $meeting), [])->assertForbidden();
        $this->post(route('board-meetings.finalize', $meeting), ['confirmed' => 1])->assertForbidden();
        $this->delete(route('board-meetings.show', $meeting))->assertStatus(405);
        $this->post(route('board-meetings.archive', $meeting))->assertRedirect();
        $this->get(route('board-meetings.book'))->assertOk()->assertSee('Weg instand setzen');
        $this->get(route('board-meetings.show', $meeting))->assertOk()->assertSee('Aus Archiv zurückholen');
        $this->post(route('board-meetings.unarchive', $meeting))->assertRedirect();
        $this->assertNull($meeting->fresh()->archived_at);
        $this->put(route('board-meetings.update', $meeting), [])->assertForbidden();
        $this->get(route('board-meetings.pdf', $meeting))->assertOk();
        $this->assertSame($original, Storage::disk('local')->get($meeting->pdf_path));
    }

    public function test_model_guards_reject_changes_to_finalized_topics_and_resolutions(): void
    {
        $meeting = $this->finalized($this->board());
        foreach ([$meeting, $meeting->agendaItems()->firstOrFail(), $meeting->resolutions()->firstOrFail()] as $record) {
            try {
                $record->update(['title' => 'Unerlaubte Änderung']);
                $this->fail('Historical update was permitted.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('immutable', $exception->getMessage());
            }
        }
    }

    public function test_failed_pdf_storage_does_not_finalize_or_leave_an_audit_entry(): void
    {
        $meeting = $this->prepared();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($this->board())->post(route('board-meetings.finalize', $meeting), ['confirmed' => 1])->assertSessionHasErrors('minutes');
        $this->assertNull($meeting->fresh()->finalized_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'board_meeting.finalized']);
    }

    public function test_tasks_clear_only_the_correct_indicators_and_survive_archiving(): void
    {
        $board = $this->board();
        $reader = User::factory()->create(['permissions' => [UserPermission::ViewBoardWork->value]]);
        $other = User::factory()->create(['permissions' => [UserPermission::ViewBoardWork->value]]);
        $member = Member::factory()->create(['user_id' => $reader->id]);
        $meeting = $this->finalized($board);
        $resolution = $meeting->resolutions()->firstOrFail();
        $this->actingAs($board)->get(route('board-follow-ups.create', $resolution))->assertOk();
        $this->post(route('board-follow-ups.store', $resolution), ['title' => 'Angebot einholen', 'due_at' => today()->toDateString(), 'assigned_to' => $reader->id])->assertRedirect();
        $task = BoardFollowUp::query()->firstOrFail();
        $this->post(route('board-meetings.archive', $meeting))->assertRedirect();
        $indicators = app(ActionIndicatorService::class);
        $this->assertSame(1, $indicators->forUser($reader)['board_work']);
        $this->assertSame(1, $indicators->forUser($board)['board_work']);
        $this->assertSame(0, $indicators->forUser($other)['board_work']);
        $this->assertSame(0, $indicators->forTenantPortal($reader)['board_work']);
        $this->actingAs($other)->post(route('board-follow-ups.complete', $task))->assertForbidden();
        $this->get(route('board-meetings.index', ['due' => 1]))->assertOk()->assertDontSee('Angebot einholen');
        $this->actingAs($reader)->get(route('board-meetings.index', ['due' => 1]))->assertOk()->assertSee('Angebot einholen');
        $this->post(route('board-follow-ups.complete', $task))->assertRedirect();
        $this->assertSame(0, $indicators->forUser($reader)['board_work']);
        $this->post(route('board-follow-ups.complete', $task))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'board_follow_up.completed', 'subject_id' => $task->id]);
        $this->assertCount(1, app(PrivacyDataExportService::class)->build($member)['board_follow_ups']);
    }

    public function test_unfinalized_rejected_resolutions_and_ineligible_assignees_cannot_receive_tasks(): void
    {
        $board = $this->board();
        $meeting = $this->prepared();
        $resolution = $meeting->resolutions()->firstOrFail();
        $data = ['title' => 'Auftrag', 'due_at' => today()->toDateString()];
        $this->actingAs($board)->post(route('board-follow-ups.store', $resolution), $data)->assertSessionHasErrors('title');
        $resolution->update(['result' => BoardResolutionResult::Rejected]);
        Storage::fake('local');
        app(BoardMeetingManager::class)->finalize($meeting, $board);
        $this->post(route('board-follow-ups.store', $resolution), $data)->assertSessionHasErrors('title');
        $accepted = $this->finalized($board)->resolutions()->firstOrFail();
        $tenant = User::factory()->create();
        $this->post(route('board-follow-ups.store', $accepted), [...$data, 'assigned_to' => $tenant->id])->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseCount('board_follow_ups', 0);
        $this->post(route('board-follow-ups.store', $accepted), [...$data, 'due_at' => today()->addDay()->toDateString()])->assertRedirect();
        $this->assertSame(0, app(ActionIndicatorService::class)->forUser($board)['board_work']);
    }

    public function test_document_links_do_not_grant_document_access_or_allow_cross_meeting_downloads(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/board.pdf', 'PDF');
        $board = $this->board();
        $document = Document::create(['title' => 'Vertraulicher Vertrag', 'type' => DocumentType::Other, 'visibility' => DocumentVisibility::Internal, 'file_path' => 'documents/board.pdf', 'original_name' => 'vertrag.pdf', 'mime_type' => 'application/pdf', 'file_size' => 3, 'uploaded_by' => $board->id]);
        $meeting = $this->prepared();
        $meeting->documents()->attach($document);
        $reader = User::factory()->create(['permissions' => [UserPermission::ViewBoardWork->value, UserPermission::ManageBoardWork->value]]);
        $this->actingAs($reader)->get(route('board-meetings.show', $meeting))->assertOk()->assertDontSee($document->title);
        $this->get(route('board-meetings.document', [$meeting, $document]))->assertForbidden();
        $this->post(route('board-meetings.store'), ['title' => 'Test', 'scheduled_at' => today()->toDateString(), 'document_ids' => [$document->id]])->assertSessionHasErrors('document_ids');
        $this->put(route('board-meetings.update', $meeting), ['title' => 'Geändert ohne Dokumentrechte', 'scheduled_at' => $meeting->scheduled_at->toDateTimeString()])->assertRedirect();
        $this->assertDatabaseHas('board_meeting_document', ['board_meeting_id' => $meeting->id, 'document_id' => $document->id]);
        $this->actingAs($board)->get(route('board-meetings.document', [$meeting, $document]))->assertOk();
        $this->get(route('board-meetings.document', [$this->prepared(), $document]))->assertNotFound();
        config(['modules.documents' => false]);
        $this->put(route('board-meetings.update', $meeting), ['title' => 'Geändert', 'scheduled_at' => $meeting->scheduled_at->toDateTimeString()])->assertRedirect();
        $this->assertDatabaseHas('board_meeting_document', ['board_meeting_id' => $meeting->id, 'document_id' => $document->id]);
        $this->get(route('board-meetings.document', [$meeting, $document]))->assertNotFound();
        $this->get(route('board-meetings.show', $meeting))->assertOk()->assertDontSee('Verknüpfte Dokumente');
    }

    public function test_disabled_module_blocks_even_administrators_and_preserves_data(): void
    {
        $admin = User::factory()->administrator()->create();
        $meeting = $this->finalized($admin);
        config(['modules.board_work' => false]);
        $this->actingAs($admin)->get(route('board-meetings.index'))->assertNotFound();
        $this->get(route('board-meetings.pdf', $meeting))->assertNotFound();
        $this->get(route('board-meetings.book'))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee(route('board-meetings.index'));
        $this->assertFalse($admin->hasPermission(UserPermission::ManageBoardWork));
        $this->assertSame(0, app(ActionIndicatorService::class)->forUser($admin)['board_work']);
        config(['modules.board_work' => true]);
        $this->get(route('board-meetings.show', $meeting))->assertOk();
        $this->assertDatabaseCount('board_meetings', 1);
    }

    public function test_migrations_roll_back_and_reapply_on_isolated_sqlite_only(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $migrations = array_map(fn ($path) => require $path, glob(database_path('migrations/2026_09_09_140*.php')));
        $this->assertCount(5, $migrations);
        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }
        $this->assertFalse(Schema::hasTable('board_meetings'));
        foreach ($migrations as $migration) {
            $migration->up();
        }
        $this->assertTrue(Schema::hasTable('board_meeting_document'));
    }

    public function test_draft_archiving_is_reversible_but_freezes_edits_until_restored(): void
    {
        $meeting = $this->prepared();
        $this->actingAs($this->board())->post(route('board-meetings.archive', $meeting))->assertRedirect();
        $this->get(route('board-meetings.edit', $meeting))->assertForbidden();
        $this->post(route('board-meetings.unarchive', $meeting))->assertRedirect();
        $this->get(route('board-meetings.edit', $meeting))->assertOk();
        $this->assertNull($meeting->fresh()->archived_at);
    }

    public function test_sitting_size_limits_prevent_unbounded_pdf_generation(): void
    {
        $meeting = BoardMeeting::factory()->create();
        for ($position = 1; $position <= 50; $position++) {
            $meeting->agendaItems()->create(['title' => 'Thema', 'position' => $position]);
        }
        for ($index = 0; $index < 100; $index++) {
            $meeting->resolutions()->create(['title' => 'Beschluss', 'body' => 'Text', 'result' => 'adopted']);
        }
        $this->actingAs($this->board())->post(route('board-agenda.store', $meeting), ['title' => 'Weiteres Thema', 'position' => 51])->assertSessionHasErrors('title');
        $this->post(route('board-resolutions.store', $meeting), ['title' => 'Weiterer Beschluss', 'body' => 'Text', 'result' => 'adopted'])->assertSessionHasErrors('title');
        $this->assertDatabaseCount('board_agenda_items', 50);
        $this->assertDatabaseCount('board_resolutions', 100);
    }
}
