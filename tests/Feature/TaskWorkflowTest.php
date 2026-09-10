<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\BoardMeeting;
use App\Models\Document;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\Task;
use App\Models\User;
use App\Services\ActionIndicatorService;
use App\Services\BoardMeetingManager;
use App\Services\PrivacyDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function board(): User
    {
        return User::factory()->create(['role' => UserRole::Board]);
    }

    private function reader(): User
    {
        return User::factory()->create(['permissions' => [UserPermission::ViewTasks->value]]);
    }

    private function data(array $extra = []): array
    {
        return ['title' => 'Unterlagen prüfen', 'due_at' => today()->addWeek()->toDateString(), 'recurrence' => 'none', ...$extra];
    }

    private function resolutionTask(User $actor): Task
    {
        $meeting = BoardMeeting::factory()->create();
        $resolution = $meeting->resolutions()->create(['title' => 'Vertraulich', 'body' => 'Interner Beschluss', 'result' => 'adopted']);
        $meeting->forceFill(['finalized_at' => now()])->save();
        $followUp = app(BoardMeetingManager::class)->addFollowUp($resolution, ['title' => 'Geheime Beschlussaufgabe', 'due_at' => today(), 'assigned_to' => $actor->id], $actor);

        return Task::findOrFail($followUp->id);
    }

    public function test_board_can_create_assign_edit_link_and_complete_a_task(): void
    {
        $board = $this->board();
        $reader = $this->reader();
        $member = Member::factory()->create();
        $parcel = Parcel::factory()->create();
        $this->actingAs($board)->get(route('tasks.create'))->assertOk();
        $this->post(route('tasks.store'), $this->data(['description' => '<script>unsafe</script>', 'assigned_to' => $reader->id, 'member_id' => $member->id, 'parcel_id' => $parcel->id]))->assertRedirect();
        $task = Task::firstOrFail();
        $this->get(route('tasks.show', $task))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe</script>', false)->assertSee($member->full_name);
        $this->get(route('tasks.edit', $task))->assertOk();
        $this->put(route('tasks.update', $task), $this->data(['title' => 'Geänderter Auftrag', 'assigned_to' => $reader->id]))->assertRedirect();
        $this->assertSame($member->id, $task->fresh()->member_id);
        $this->actingAs($reader)->get(route('tasks.show', $task))->assertOk()->assertDontSee($member->full_name)->assertSee('Mitglied: Zugriff eingeschränkt.');
        $this->post(route('tasks.start', $task))->assertRedirect();
        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status());
        $this->post(route('tasks.complete', $task))->assertRedirect();
        $this->assertSame(TaskStatus::Completed, $task->fresh()->status());
        $this->assertDatabaseCount('task_events', 4);
        $this->assertDatabaseHas('audit_logs', ['action' => 'task.completed', 'subject_id' => $task->id]);
        $this->assertStringNotContainsString('unsafe', Task::firstOrFail()->events->toJson());
    }

    public function test_readers_cannot_view_foreign_tasks_or_manage_assignments(): void
    {
        $reader = $this->reader();
        $own = Task::factory()->create(['assigned_to' => $reader->id]);
        $foreign = Task::factory()->create(['title' => 'Fremder Auftrag', 'assigned_to' => $this->reader()->id]);
        $this->actingAs($reader)->get(route('tasks.index'))->assertOk()->assertSee($own->title)->assertDontSee($foreign->title);
        $this->get(route('tasks.show', $foreign))->assertForbidden();
        $this->get(route('tasks.edit', $own))->assertForbidden();
        $this->post(route('tasks.store'), $this->data())->assertForbidden();
        $this->post(route('tasks.cancel', $own), ['cancel_reason' => 'Nein'])->assertForbidden();
        $this->post(route('tasks.complete', $foreign))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('tasks.index'))->assertForbidden();
        $this->get(route('tasks.show', $own))->assertForbidden();
    }

    public function test_general_managers_do_not_gain_confidential_board_access(): void
    {
        $board = $this->board();
        $task = $this->resolutionTask($board);
        $manager = User::factory()->create(['permissions' => [UserPermission::ManageTasks->value]]);
        $this->actingAs($manager)->get(route('tasks.index'))->assertOk()->assertDontSee($task->title);
        $this->get(route('tasks.show', $task))->assertForbidden();
        $this->post(route('tasks.complete', $task))->assertForbidden();
        $this->post(route('tasks.store'), $this->data(['board_resolution_id' => $task->board_resolution_id]))->assertSessionHasErrors('board_resolution_id');
        $this->assertSame(0, app(ActionIndicatorService::class)->forUser($manager)['tasks']);
        $this->assertSame([UserPermission::ManageTasks->value], $manager->fresh()->permissions);
    }

    public function test_reminders_match_actionable_list_and_do_not_duplicate_board_or_portal_counts(): void
    {
        $this->freezeTime();
        $board = $this->board();
        $reader = $this->reader();
        Member::factory()->create(['user_id' => $reader->id]);
        $task = Task::factory()->create(['assigned_to' => $reader->id, 'remind_at' => today(), 'due_at' => today()->addWeek()]);
        Task::factory()->create(['assigned_to' => $reader->id, 'due_at' => today()->addDay()]);
        $boardTask = $this->resolutionTask($board);
        $indicators = app(ActionIndicatorService::class);
        $this->assertSame(1, $indicators->forUser($reader)['tasks']);
        $this->assertSame(2, $indicators->forUser($board)['tasks']);
        $this->assertSame(2, $indicators->forUser($board)['communication_group']);
        $this->assertSame(0, $indicators->forTenantPortal($reader)['tasks']);
        $this->actingAs($reader)->get(route('tasks.index', ['due' => 1]))->assertOk()->assertViewHas('tasks', fn ($rows) => $rows->modelKeys() === [$task->id]);
        $this->post(route('tasks.complete', $task))->assertRedirect();
        $this->assertSame(0, $indicators->forUser($reader)['tasks']);
        $this->actingAs($board)->post(route('board-follow-ups.complete', $boardTask))->assertRedirect();
        $this->assertSame(0, $indicators->forUser($board)['tasks']);
    }

    public function test_monthly_recurrence_keeps_anchor_and_duplicate_completion_cannot_create_extra_successors(): void
    {
        $board = $this->board();
        $task = Task::factory()->create(['due_at' => '2028-01-31', 'remind_at' => '2028-01-29', 'recurrence' => TaskRecurrence::Monthly, 'recurrence_anchor' => '2028-01-31']);
        $this->actingAs($board)->post(route('tasks.complete', $task))->assertRedirect();
        $next = $task->successor()->firstOrFail();
        $this->assertSame('2028-02-29', $next->due_at->toDateString());
        $this->assertSame('2028-02-27', $next->remind_at->toDateString());
        $this->post(route('tasks.complete', $task))->assertForbidden();
        $this->assertDatabaseCount('board_follow_ups', 2);
        $this->post(route('tasks.complete', $next))->assertRedirect();
        $this->assertSame('2028-03-31', $next->successor()->firstOrFail()->due_at->toDateString());
        $this->get(route('tasks.show', $task))->assertOk()->assertSee('Nächster Termin');
        $this->assertCount(1, $task->fresh()->events);
    }

    public function test_yearly_weekly_late_and_changed_recurrences_have_deterministic_dates(): void
    {
        $board = $this->board();
        $this->actingAs($board);
        foreach ([['2024-02-29', 'yearly', '2025-02-28'], ['2025-01-01', 'weekly', '2025-01-08']] as [$start, $repeat, $expected]) {
            $task = Task::factory()->create(['due_at' => $start, 'recurrence' => $repeat]);
            $this->post(route('tasks.complete', $task))->assertRedirect();
            $this->assertSame($expected, $task->successor()->firstOrFail()->due_at->toDateString());
        }
        $task = Task::factory()->create(['due_at' => '2026-01-31', 'recurrence' => 'monthly', 'recurrence_anchor' => '2026-01-31', 'occurrence' => 3]);
        $this->put(route('tasks.update', $task), $this->data(['due_at' => '2026-05-15', 'recurrence' => 'weekly']))->assertRedirect();
        $this->assertSame(0, $task->fresh()->occurrence);
        $this->post(route('tasks.complete', $task))->assertRedirect();
        $next = $task->successor()->firstOrFail();
        $this->assertSame('2026-05-22', $next->due_at->toDateString());
        $this->put(route('tasks.update', $next), $this->data(['due_at' => '2026-05-22', 'recurrence' => 'none']))->assertRedirect();
        $this->post(route('tasks.complete', $next))->assertRedirect();
        $this->assertNull($next->successor()->first());
    }

    public function test_cancellation_requires_reason_stops_recurrence_and_allows_reversible_archiving(): void
    {
        $task = Task::factory()->create(['recurrence' => 'monthly']);
        $this->actingAs($this->board())->post(route('tasks.archive', $task))->assertForbidden();
        $this->post(route('tasks.cancel', $task))->assertSessionHasErrors('cancel_reason');
        $this->post(route('tasks.cancel', $task), ['cancel_reason' => 'Nicht mehr erforderlich.'])->assertRedirect();
        $this->assertSame(TaskStatus::Cancelled, $task->fresh()->status());
        $this->assertNull($task->successor()->first());
        $this->post(route('tasks.complete', $task))->assertForbidden();
        $this->put(route('tasks.update', $task), $this->data())->assertForbidden();
        $this->post(route('tasks.archive', $task))->assertRedirect();
        $this->get(route('tasks.index', ['status' => 'cancelled']))->assertOk()->assertViewHas('tasks', fn ($rows) => $rows->isEmpty());
        $this->get(route('tasks.index', ['status' => 'cancelled', 'archived' => 1]))->assertOk()->assertSee($task->title);
        $this->post(route('tasks.restore', $task))->assertRedirect();
        $this->get(route('tasks.show', $task))->assertOk()->assertSee('Nicht mehr erforderlich.');
        $this->delete(route('tasks.show', $task))->assertStatus(405);
    }

    public function test_invalid_reminders_assignees_and_resolution_changes_are_rejected(): void
    {
        $board = $this->board();
        $this->actingAs($board)->post(route('tasks.store'), $this->data(['remind_at' => today()->addWeeks(2)->toDateString()]))->assertSessionHasErrors('remind_at');
        $this->post(route('tasks.store'), $this->data(['assigned_to' => User::factory()->create()->id]))->assertSessionHasErrors('assigned_to');
        $this->post(route('tasks.store'), $this->data(['member_id' => 99999]))->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('board_follow_ups', 0);
        $task = $this->resolutionTask($board);
        $this->put(route('tasks.update', $task), $this->data(['board_resolution_id' => $task->board_resolution_id]))->assertSessionHasErrors('board_resolution_id');
    }

    public function test_document_access_and_disabled_modules_preserve_links_without_granting_downloads(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/task.pdf', 'private');
        $board = $this->board();
        $reader = $this->reader();
        $doc = Document::create(['title' => 'Privater Vertrag', 'type' => DocumentType::Other, 'visibility' => DocumentVisibility::Internal, 'file_path' => 'documents/task.pdf', 'original_name' => 'task.pdf', 'mime_type' => 'application/pdf', 'file_size' => 7, 'uploaded_by' => $board->id]);
        $this->actingAs($board)->post(route('tasks.store'), $this->data(['assigned_to' => $reader->id, 'document_id' => $doc->id]))->assertRedirect();
        $task = Task::firstOrFail();
        $this->get(route('tasks.document', $task))->assertOk();
        $this->actingAs($reader)->get(route('tasks.show', $task))->assertOk()->assertDontSee($doc->title);
        $this->get(route('tasks.document', $task))->assertForbidden();
        config(['modules.documents' => false]);
        $this->actingAs($board)->get(route('tasks.edit', $task))->assertOk()->assertDontSee($doc->title);
        $this->put(route('tasks.update', $task), $this->data())->assertRedirect();
        $this->assertSame($doc->id, $task->fresh()->document_id);
        $this->get(route('tasks.document', $task))->assertNotFound();
        $this->put(route('tasks.update', $task), $this->data(['document_id' => null]))->assertSessionHasErrors('document_id');
    }

    public function test_disabled_tasks_preserve_legacy_board_work_but_cannot_drop_recurrences(): void
    {
        $board = $this->board();
        $task = $this->resolutionTask($board);
        $general = Task::factory()->create();
        $this->actingAs($board)->post(route('board-follow-ups.complete', $general))->assertForbidden();
        config(['modules.tasks' => false]);
        $this->get(route('tasks.index'))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee(route('tasks.index'));
        $this->assertSame(0, app(ActionIndicatorService::class)->forUser($board)['tasks']);
        $this->post(route('board-follow-ups.complete', $task))->assertRedirect();
        $repeating = $this->resolutionTask($board);
        $repeating->update(['recurrence' => 'monthly']);
        $this->post(route('board-follow-ups.complete', $repeating))->assertForbidden();
        $this->assertNull($repeating->fresh()->completed_at);
        config(['modules.tasks' => true, 'modules.board_work' => false]);
        $this->get(route('tasks.show', $repeating))->assertForbidden();
        $this->get(route('tasks.show', $general))->assertOk();
    }

    public function test_exports_include_relevant_tasks_and_own_events_without_other_users_credentials(): void
    {
        $reader = $this->reader();
        $member = Member::factory()->create(['user_id' => $reader->id]);
        $task = Task::factory()->create(['assigned_to' => $reader->id]);
        $this->actingAs($reader)->post(route('tasks.start', $task))->assertRedirect();
        $export = app(PrivacyDataExportService::class)->build($member);
        $this->assertCount(1, $export['tasks']);
        $this->assertCount(1, $export['task_events']);
        $this->assertStringNotContainsString('password', json_encode($export['tasks']));
    }

    public function test_additive_migrations_preserve_legacy_rows_and_are_reversible_in_isolated_sqlite(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $schema = require database_path('migrations/2026_09_10_100000_extend_board_follow_ups_for_tasks.php');
        $events = require database_path('migrations/2026_09_10_100100_create_task_events_table.php');
        $events->down();
        $schema->down();
        $meeting = BoardMeeting::factory()->create();
        $resolution = $meeting->resolutions()->create(['title' => 'Alt', 'body' => 'Historie', 'result' => 'adopted']);
        $id = DB::table('board_follow_ups')->insertGetId(['board_resolution_id' => $resolution->id, 'title' => 'Bestehende Aufgabe', 'due_at' => '2025-12-31', 'completed_at' => '2025-12-30 10:00:00']);
        $schema->up();
        $events->up();
        $this->assertSame('Bestehende Aufgabe', Task::findOrFail($id)->title);
        $this->assertSame(TaskStatus::Completed, Task::findOrFail($id)->status());
        $this->assertTrue(Schema::hasTable('task_events'));
    }

    public function test_legacy_completion_repeats_same_board_task_without_copying_unrelated_relations(): void
    {
        $board = $this->board();
        $task = $this->resolutionTask($board);
        $this->actingAs($board)->put(route('tasks.update', $task), $this->data(['due_at' => '2026-12-31', 'recurrence' => 'monthly', 'assigned_to' => $board->id]))->assertRedirect();
        $this->post(route('board-follow-ups.complete', $task))->assertRedirect();
        $next = $task->successor()->firstOrFail();
        $this->assertSame('2027-01-31', $next->due_at->toDateString());
        $this->assertSame($task->board_resolution_id, $next->board_resolution_id);
        $this->assertCount(1, $next->events);
        $this->assertSame('repeated', $next->events->first()->action);
        $this->get(route('board-meetings.show', $task->resolution->meeting))->assertOk()->assertSee(route('tasks.show', $next), false);
    }

    public function test_revoked_assignment_rights_require_explicit_reassignment_and_links_stay_protected(): void
    {
        $board = $this->board();
        $reader = $this->reader();
        $task = Task::factory()->create(['assigned_to' => $reader->id]);
        $reader->update(['permissions' => []]);
        $this->actingAs($reader)->get(route('tasks.show', $task))->assertForbidden();
        $this->actingAs($board)->get(route('tasks.edit', $task))->assertOk()->assertSee('Bisheriges Konto: Rechte fehlen');
        $this->put(route('tasks.update', $task), $this->data(['assigned_to' => $reader->id]))->assertSessionHasErrors('assigned_to');
        $this->put(route('tasks.update', $task), $this->data(['assigned_to' => null]))->assertRedirect();
        $this->assertNull($task->fresh()->assigned_to);
        $manager = User::factory()->create(['permissions' => [UserPermission::ManageTasks->value]]);
        $member = Member::factory()->create();
        $this->actingAs($manager)->post(route('tasks.store'), $this->data(['member_id' => $member->id]))->assertSessionHasErrors('member_id');
    }

    public function test_rollbacks_refuse_to_discard_new_task_data_and_history(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $task = Task::factory()->create();
        $task->events()->create(['action' => 'created', 'created_at' => now()]);
        foreach (glob(database_path('migrations/2026_09_10_100*.php')) as $path) {
            try {
                (require $path)->down();
                $this->fail('Rollback discarded task data.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Rollback would lose', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('board_follow_ups', 1);
        $this->assertDatabaseCount('task_events', 1);
    }
}
