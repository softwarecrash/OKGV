<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_permission_is_granular(): void
    {
        $withoutPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageCommunication->value],
        ]);
        $withPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageDocuments->value],
        ]);

        $this->actingAs($withoutPermission)
            ->get(route('documents.index'))
            ->assertForbidden();

        $this->actingAs($withPermission)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('Dokumente');
    }

    public function test_upload_replacement_and_archiving_preserve_versions_and_end_public_access(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('documents.store'), [
                'title' => 'Vereinssatzung',
                'description' => 'Beschlossen durch die Mitgliederversammlung.',
                'type' => DocumentType::Statute->value,
                'visibility' => DocumentVisibility::Public->value,
                'published' => true,
                'file' => UploadedFile::fake()->createWithContent(
                    'satzung.pdf',
                    "%PDF-1.4\nErste Fassung",
                ),
            ])
            ->assertRedirect();

        $document = Document::query()->firstOrFail();
        $firstPath = $document->file_path;
        $this->assertNotNull($document->public_token);
        $this->assertSame(1, $document->current_version);
        $this->assertDatabaseCount('document_versions', 1);
        Storage::disk('local')->assertExists($firstPath);

        $this->get(route('documents.public', $document->public_token))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->actingAs($administrator)
            ->put(route('documents.update', $document), [
                'title' => 'Vereinssatzung',
                'description' => 'Neue beschlossene Fassung.',
                'type' => DocumentType::Statute->value,
                'visibility' => DocumentVisibility::Public->value,
                'published' => true,
                'file' => UploadedFile::fake()->createWithContent(
                    'satzung-neu.pdf',
                    "%PDF-1.4\nZweite Fassung",
                ),
            ])
            ->assertRedirect(route('documents.show', $document));

        $document->refresh();
        $this->assertSame(2, $document->current_version);
        $this->assertDatabaseCount('document_versions', 2);
        Storage::disk('local')->assertExists($firstPath);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.updated',
            'subject_id' => $document->id,
        ]);

        $publicToken = $document->public_token;
        $this->actingAs($administrator)
            ->patch(route('documents.archive', $document))
            ->assertRedirect(route('documents.index'));

        $this->assertNotNull($document->fresh()->archived_at);
        $this->assertNull($document->fresh()->public_token);
        $this->get(route('documents.public', $publicToken))->assertNotFound();
        $this->assertDatabaseCount('document_versions', 2);
    }

    public function test_tenant_document_requires_assignment_and_remains_isolated(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $foreignTenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        Member::factory()->create(['user_id' => $foreignTenant->id]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
            'starts_at' => now()->subMonth(),
            'ends_at' => null,
        ]);

        $this->actingAs($administrator)
            ->post(route('documents.store'), [
                'title' => 'Pachtvertrag',
                'type' => DocumentType::LeaseContract->value,
                'visibility' => DocumentVisibility::Tenant->value,
                'published' => true,
                'file' => UploadedFile::fake()->createWithContent('vertrag.pdf', '%PDF-1.4 Vertrag'),
            ])
            ->assertSessionHasErrors('member_id');

        $this->actingAs($administrator)
            ->post(route('documents.store'), [
                'title' => 'Pachtvertrag',
                'type' => DocumentType::LeaseContract->value,
                'visibility' => DocumentVisibility::Tenant->value,
                'parcel_id' => $parcel->id,
                'published' => true,
                'file' => UploadedFile::fake()->createWithContent('vertrag.pdf', '%PDF-1.4 Vertrag'),
            ])
            ->assertRedirect();

        $document = Document::query()->firstOrFail();
        $this->actingAs($tenant)
            ->get(route('tenant-portal.documents.download', $document))
            ->assertOk();
        $this->actingAs($foreignTenant)
            ->get(route('tenant-portal.documents.download', $document))
            ->assertForbidden();
    }

    public function test_executable_upload_is_rejected(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('documents.store'), [
                'title' => 'Unsichere Datei',
                'type' => DocumentType::Other->value,
                'visibility' => DocumentVisibility::Internal->value,
                'published' => false,
                'file' => UploadedFile::fake()->create('programm.php', 10, 'text/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('documents'));
    }

    public function test_documents_and_file_versions_cannot_be_hard_deleted_or_changed(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post(route('documents.store'), [
            'title' => 'Unveränderliches Dokument',
            'type' => DocumentType::Other->value,
            'visibility' => DocumentVisibility::Internal->value,
            'published' => false,
            'file' => UploadedFile::fake()->createWithContent('ablage.pdf', '%PDF-1.4 Ablage'),
        ]);

        $document = Document::query()->firstOrFail();
        $version = $document->versions()->firstOrFail();

        try {
            $version->update(['original_name' => 'manipuliert.pdf']);
            $this->fail('Eine Dateiversion durfte verändert werden.');
        } catch (LogicException) {
            $this->assertSame('ablage.pdf', $version->fresh()->original_name);
        }

        $this->expectException(LogicException::class);
        $document->delete();
    }
}
