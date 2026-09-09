<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudience;
use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\Member;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Services\ActionIndicatorService;
use App\Services\PrivacyDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function announcement(array $attributes = []): Announcement
    {
        return Announcement::create([
            'title' => 'Wasser wird abgestellt',
            'body' => 'Bitte die Leitungen bis Freitag entleeren.',
            'audience' => AnnouncementAudience::All,
            'starts_at' => now()->subDay(),
            'published_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_board_can_create_preview_publish_and_archive_a_contribution(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $this->actingAs($board)->get(route('announcements.create'))->assertOk();
        $this->post(route('announcements.store'), [
            'title' => 'Arbeitseinsatz am Samstag',
            'body' => '<script>alert(1)</script> Bitte Werkzeug mitbringen.',
            'audience' => 'all',
            'starts_at' => now()->toDateTimeString(),
            'is_highlighted' => '1',
            'requires_confirmation' => '1',
        ])->assertRedirect();
        $announcement = Announcement::query()->firstOrFail();
        $this->assertNull($announcement->published_at);
        $this->get(route('announcements.show', $announcement))->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('announcement_reads', 0);
        $this->post(route('announcements.publish', $announcement))->assertRedirect();
        $this->assertNotNull($announcement->fresh()->published_at);
        $this->put(route('announcements.update', $announcement), ['title' => 'Nachträglich geändert'])->assertForbidden();
        $this->get(route('announcements.index', ['manage' => 1]))->assertOk()->assertSee($announcement->title);
        $this->post(route('announcements.archive', $announcement))->assertRedirect();
        $this->assertNotNull($announcement->fresh()->archived_at);
        $this->actingAs(User::factory()->create())->get(route('announcements.show', $announcement))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'announcement.published', 'subject_id' => $announcement->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'announcement.archived', 'subject_id' => $announcement->id]);
    }

    public function test_custom_board_permissions_and_tenants_cannot_publish(): void
    {
        $announcement = $this->announcement(['published_at' => null]);
        foreach ([UserRole::Tenant, UserRole::Board] as $role) {
            $user = User::factory()->create(['role' => $role, 'permissions' => []]);
            $this->actingAs($user)->get(route('announcements.create'))->assertForbidden();
            $this->post(route('announcements.publish', $announcement))->assertForbidden();
            $this->get(route('announcements.show', $announcement))->assertForbidden();
            $this->get(route('announcements.index', ['manage' => 1]))->assertOk()->assertDontSee($announcement->title);
        }
        $user->update(['permissions' => [UserPermission::ManageAnnouncements->value]]);
        $this->get(route('announcements.create'))->assertOk();
    }

    public function test_public_visibility_excludes_drafts_future_expired_and_internal_posts(): void
    {
        $this->freezeTime();
        $visible = $this->announcement(['title' => 'Öffentliche News', 'audience' => AnnouncementAudience::Public]);
        $hidden = [
            $this->announcement(['title' => 'Interne News']),
            $this->announcement(['audience' => AnnouncementAudience::Public, 'published_at' => null]),
            $this->announcement(['audience' => AnnouncementAudience::Public, 'starts_at' => now()->addMinute()]),
            $this->announcement(['audience' => AnnouncementAudience::Public, 'ends_at' => now()]),
            $this->announcement(['audience' => AnnouncementAudience::Public, 'archived_at' => now()]),
        ];
        $this->get(route('announcements.public.index'))->assertOk()
            ->assertViewHas('announcements', fn ($rows) => $rows->modelKeys() === [$visible->id]);
        $this->get(route('announcements.public.show', $visible))->assertOk()->assertDontSee('Kenntnisnahmen');
        foreach ($hidden as $announcement) {
            $this->get(route('announcements.public.show', $announcement))->assertNotFound();
        }
        $this->assertDatabaseCount('announcement_reads', 0);
    }

    public function test_audiences_respect_roles_and_current_tenancies_including_board_tenants(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board, 'permissions' => []]);
        $member = Member::factory()->create(['user_id' => $board->id]);
        $tenancy = ParcelTenant::factory()->create(['member_id' => $member->id, 'starts_at' => now()->subMonth()]);
        $tenants = $this->announcement(['audience' => AnnouncementAudience::Tenants]);
        $roles = $this->announcement(['audience' => AnnouncementAudience::Roles, 'roles' => ['board']]);
        $foreign = $this->announcement(['audience' => AnnouncementAudience::Roles, 'roles' => ['treasurer']]);
        $this->actingAs($board)->get(route('announcements.show', $tenants))->assertOk();
        $this->get(route('announcements.show', $roles))->assertOk();
        $this->get(route('announcements.show', $foreign))->assertForbidden();
        $tenancy->update(['ends_at' => now()->subDay()]);
        $this->get(route('announcements.show', $tenants))->assertForbidden();
        $board->update(['role' => UserRole::Tenant]);
        $this->get(route('announcements.show', $roles))->assertForbidden();
    }

    public function test_explicit_confirmation_is_idempotent_and_clears_matching_indicators(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $announcement = $this->announcement(['requires_confirmation' => true]);
        $indicators = app(ActionIndicatorService::class);
        $this->assertSame(1, $indicators->forUser($user)['announcements']);
        $this->assertSame(1, $indicators->forTenantPortal($user)['announcements']);
        $this->actingAs($user)->get(route('announcements.show', $announcement))->assertOk()->assertSee('Lesen bestätigen');
        $this->assertDatabaseCount('announcement_reads', 0);
        $this->post(route('announcements.acknowledge', $announcement))->assertSessionHasErrors('confirmation');
        $this->post(route('announcements.acknowledge', $announcement), ['confirmation' => 1])->assertRedirect();
        $timestamp = $announcement->reads()->firstOrFail()->read_at;
        $this->travel(1)->minute();
        $this->post(route('announcements.acknowledge', $announcement), ['confirmation' => 1])->assertRedirect();
        $this->assertDatabaseCount('announcement_reads', 1);
        $this->assertTrue($timestamp->eq($announcement->reads()->firstOrFail()->read_at));
        $this->assertSame(0, $indicators->forUser($user)['announcements']);
        $this->assertSame(0, $indicators->forTenantPortal($user)['announcements']);
        $this->assertCount(1, app(PrivacyDataExportService::class)->build($member)['announcement_reads']);
        $this->get(route('announcements.index', ['unread' => 1]))->assertOk()->assertViewHas('announcements', fn ($rows) => $rows->isEmpty());
    }

    public function test_receipts_are_private_and_out_of_audience_acknowledgements_are_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Privater Leser']);
        $announcement = $this->announcement();
        $announcement->reads()->create(['user_id' => $user->id, 'read_at' => now()]);
        $this->actingAs(User::factory()->create())->get(route('announcements.show', $announcement))
            ->assertOk()->assertDontSee('Privater Leser');
        $this->actingAs(User::factory()->administrator()->create())->get(route('announcements.show', $announcement))
            ->assertOk()->assertSee('Privater Leser');
        $restricted = $this->announcement(['audience' => AnnouncementAudience::Roles, 'roles' => ['board']]);
        $this->actingAs($user)->post(route('announcements.acknowledge', $restricted), ['confirmation' => 1])->assertForbidden();
    }

    public function test_document_links_do_not_grant_access_and_public_posts_reject_private_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.pdf', 'PDF');
        $admin = User::factory()->administrator()->create();
        $document = Document::create([
            'title' => 'Vertraulicher Anhang', 'type' => DocumentType::Other,
            'visibility' => DocumentVisibility::Internal, 'published_at' => now(),
            'uploaded_by' => $admin->id, 'file_path' => 'documents/test.pdf',
            'original_name' => 'test.pdf', 'mime_type' => 'application/pdf', 'file_size' => 3,
        ]);
        $announcement = $this->announcement();
        $announcement->documents()->attach($document);
        $this->actingAs(User::factory()->create())->get(route('announcements.show', $announcement))
            ->assertOk()->assertDontSee($document->title);
        $this->get(route('announcements.document', [$announcement, $document]))->assertNotFound();
        $this->actingAs($admin)->get(route('announcements.document', [$announcement, $document]))->assertOk();
        $this->post(route('announcements.store'), [
            'title' => 'Öffentlicher Beitrag', 'body' => 'Test', 'audience' => 'public',
            'starts_at' => now()->toDateTimeString(), 'document_ids' => [$document->id],
        ])->assertSessionHasErrors('document_ids');
        $this->assertDatabaseCount('announcements', 1);
        $document->update(['visibility' => DocumentVisibility::Public, 'public_token' => str_repeat('a', 64)]);
        $public = $this->announcement(['audience' => AnnouncementAudience::Public]);
        $public->documents()->attach($document);
        $this->get(route('announcements.public.document', [$public, $document]))->assertOk();
        $document->update(['archived_at' => now()]);
        $this->get(route('announcements.public.document', [$public, $document]))->assertNotFound();
    }

    public function test_invalid_roles_periods_and_missing_documents_do_not_create_partial_posts(): void
    {
        $this->actingAs(User::factory()->administrator()->create())->post(route('announcements.store'), [
            'title' => 'Test', 'body' => 'Text', 'audience' => 'roles',
            'roles' => [], 'starts_at' => '2026-09-10', 'ends_at' => '2026-09-09', 'document_ids' => [999],
        ])->assertSessionHasErrors(['roles', 'ends_at', 'document_ids.0']);
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_module_switch_preserves_posts_and_removes_routes_and_indicators(): void
    {
        $announcement = $this->announcement();
        $user = User::factory()->administrator()->create();
        config(['modules.announcements' => false]);
        $this->actingAs($user)->get(route('announcements.index'))->assertNotFound();
        $this->get(route('announcements.public.index'))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee(route('announcements.index'));
        $this->assertSame(0, app(ActionIndicatorService::class)->forUser($user)['announcements']);
        $this->assertFalse($user->hasPermission(UserPermission::ManageAnnouncements));
        config(['modules.announcements' => true]);
        $this->get(route('announcements.show', $announcement))->assertOk();
        $this->assertDatabaseCount('announcements', 1);
    }

    public function test_new_migrations_are_reversible_only_on_isolated_test_database(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $migrations = array_map(fn ($path) => require $path, glob(database_path('migrations/2026_09_09_12000*.php')));
        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }
        $this->assertFalse(Schema::hasTable('announcements'));
        foreach ($migrations as $migration) {
            $migration->up();
        }
        $this->assertTrue(Schema::hasTable('announcement_reads'));
    }

    public function test_expiry_removes_unread_indicators_and_prevents_late_confirmation(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $announcement = $this->announcement(['starts_at' => now(), 'ends_at' => now()->addMinute()]);
        $this->assertSame(1, app(ActionIndicatorService::class)->forUser($user)['announcements']);
        $this->travel(1)->minute();
        $this->assertSame(0, app(ActionIndicatorService::class)->forUser($user)['announcements']);
        $this->actingAs($user)->post(route('announcements.acknowledge', $announcement), ['confirmation' => 1])->assertForbidden();
        $this->assertDatabaseCount('announcement_reads', 0);
    }

    public function test_disabled_documents_preserve_links_without_exposing_downloads(): void
    {
        $admin = User::factory()->administrator()->create();
        $document = Document::create([
            'title' => 'Vereinsinfo', 'type' => DocumentType::Other,
            'visibility' => DocumentVisibility::Public, 'published_at' => now(),
            'public_token' => str_repeat('b', 64), 'uploaded_by' => $admin->id,
            'file_path' => 'documents/test.pdf', 'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 3,
        ]);
        $announcement = $this->announcement(['published_at' => null]);
        $announcement->documents()->attach($document);
        config(['modules.documents' => false]);
        $this->actingAs($admin)->put(route('announcements.update', $announcement), [
            'title' => 'Neuer Titel', 'body' => 'Text', 'audience' => 'all', 'starts_at' => now()->toDateTimeString(),
        ])->assertRedirect();
        $this->assertDatabaseHas('announcement_document', ['announcement_id' => $announcement->id, 'document_id' => $document->id]);
        $this->get(route('announcements.show', $announcement))->assertOk()->assertDontSee('Vereinsinfo');
        $this->get(route('announcements.document', [$announcement, $document]))->assertNotFound();
    }
}
