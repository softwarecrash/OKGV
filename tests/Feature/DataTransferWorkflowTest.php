<?php

namespace Tests\Feature;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\BackupManager;
use App\Services\DatabaseDumpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class DataTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_board_member_can_import_members_and_export_them(): void
    {
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageDataTransfer->value],
        ]);
        $csv = implode("\n", [
            implode(';', [
                'member_number', 'first_name', 'last_name', 'street', 'zip',
                'city', 'phone', 'mobile', 'email', 'joined_at', 'left_at',
                'status', 'notes',
            ]),
            'M-9001;Erika;Muster;Gartenweg 1;99423;Weimar;;;erika@example.de;2024-01-01;;active;Import',
        ]);

        $this->actingAs($board)
            ->post(route('data-transfer.import'), [
                'type' => 'members',
                'file' => UploadedFile::fake()->createWithContent('members.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('members', [
            'member_number' => 'M-9001',
            'first_name' => 'Erika',
        ]);

        $response = $this->actingAs($board)
            ->get(route('data-transfer.export', 'members'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('M-9001;Erika;Muster', $response->streamedContent());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $board->id,
            'action' => 'csv.imported',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $board->id,
            'action' => 'csv.exported',
        ]);

        Member::query()->where('member_number', 'M-9001')->update([
            'first_name' => '=HYPERLINK("https://example.test")',
        ]);
        $safeExport = $this->actingAs($board)
            ->get(route('data-transfer.export', 'members'))
            ->streamedContent();
        $this->assertStringContainsString('"\'=HYPERLINK', $safeExport);
    }

    public function test_invalid_csv_rolls_back_every_row(): void
    {
        $administrator = User::factory()->administrator()->create();
        $csv = implode("\n", [
            'member_number;first_name;last_name;street;zip;city;phone;mobile;email;joined_at;left_at;status;notes',
            'M-9001;Erika;Muster;Gartenweg 1;99423;Weimar;;;;2024-01-01;;active;',
            'M-9002;;Fehler;Gartenweg 2;99423;Weimar;;;;2024-01-01;;active;',
        ]);

        $this->actingAs($administrator)
            ->post(route('data-transfer.import'), [
                'type' => 'members',
                'file' => UploadedFile::fake()->createWithContent('members.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('members', ['member_number' => 'M-9001']);
        $this->assertDatabaseMissing('members', ['member_number' => 'M-9002']);
    }

    public function test_meter_reading_import_is_append_only_and_uses_business_validation(): void
    {
        $administrator = User::factory()->administrator()->create();
        $meter = Meter::factory()->create([
            'installed_at' => '2024-01-01',
            'start_reading' => '10.0000',
        ]);
        $csv = implode("\n", [
            'meter_number;reading_value;reading_date;source;notes',
            "{$meter->meter_number};15.5000;2024-06-30;import;Halbjahr",
        ]);

        $this->actingAs($administrator)
            ->post(route('data-transfer.import'), [
                'type' => 'meter_readings',
                'file' => UploadedFile::fake()->createWithContent('readings.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $meter->id,
            'reading_value' => '15.5000',
            'reading_date' => '2024-06-30 00:00:00',
        ]);

        $this->actingAs($administrator)
            ->post(route('data-transfer.import'), [
                'type' => 'meter_readings',
                'file' => UploadedFile::fake()->createWithContent('readings.csv', $csv),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(1, MeterReading::query()->where('meter_id', $meter->id)->count());
    }

    public function test_users_without_data_transfer_permission_cannot_access_the_area(): void
    {
        $tenant = User::factory()->create([
            'role' => UserRole::Tenant,
            'permissions' => [],
        ]);

        $this->actingAs($tenant)
            ->get(route('data-transfer.index'))
            ->assertForbidden();
    }

    public function test_only_administrators_see_and_use_backup_functions(): void
    {
        Storage::fake('local');
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageDataTransfer->value],
        ]);

        $this->actingAs($board)
            ->get(route('data-transfer.index'))
            ->assertOk()
            ->assertDontSee('Backup und Wiederherstellung');

        $this->actingAs($board)
            ->post(route('backups.create'))
            ->assertForbidden();
    }

    public function test_backup_contains_database_manifest_and_private_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/vertrag.pdf', 'private document');
        $administrator = User::factory()->administrator()->create();
        $database = Mockery::mock(DatabaseDumpService::class);
        $database->shouldReceive('dump')
            ->once()
            ->andReturnUsing(fn (string $path) => file_put_contents($path, 'database dump'));
        $manager = new BackupManager($database);

        $backup = $manager->create($administrator);
        $archive = new ZipArchive;
        $archive->open(Storage::disk('local')->path("backups/{$backup['name']}"));
        $manifest = json_decode($archive->getFromName('manifest.json'), true);

        $this->assertSame('okgv-backup-v1', $manifest['format']);
        $this->assertSame(trim(file_get_contents(base_path('VERSION'))), $manifest['version']);
        $this->assertSame('database dump', $archive->getFromName('database.sql'));
        $this->assertSame('private document', $archive->getFromName('files/documents/vertrag.pdf'));
        $this->assertSame(
            hash('sha256', 'private document'),
            $manifest['checksums']['files/documents/vertrag.pdf'],
        );
        $archive->close();
    }

    public function test_restore_requires_current_password_and_confirmation_phrase(): void
    {
        $administrator = User::factory()->administrator()->create([
            'password' => 'secret-password',
        ]);

        $this->actingAs($administrator)
            ->post(route('backups.restore'), [
                'backup' => UploadedFile::fake()->create('backup.zip', 10, 'application/zip'),
                'password' => 'wrong-password',
                'confirmation' => 'JA',
            ])
            ->assertSessionHasErrors(['password', 'confirmation']);
    }

    public function test_valid_backup_restores_database_and_private_files_through_the_manager(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/vertrag.pdf', 'old document');
        $administrator = User::factory()->administrator()->create();
        $database = Mockery::mock(DatabaseDumpService::class);
        $database->shouldReceive('dump')
            ->twice()
            ->andReturnUsing(fn (string $path) => file_put_contents($path, 'database dump'));
        $database->shouldReceive('restore')
            ->once()
            ->with(Mockery::on(fn (string $path): bool => file_get_contents($path) === 'database dump'));
        $manager = new BackupManager($database);
        $backup = $manager->create($administrator);
        $archive = file_get_contents(Storage::disk('local')->path("backups/{$backup['name']}"));
        Storage::disk('local')->put('documents/vertrag.pdf', 'new document');

        $createdAt = $manager->restore(
            UploadedFile::fake()->createWithContent('restore.zip', $archive),
            $administrator,
        );

        $this->assertNotEmpty($createdAt);
        $this->assertSame(
            'old document',
            Storage::disk('local')->get('documents/vertrag.pdf'),
        );
        $this->assertCount(2, $manager->all());
    }

    public function test_csv_templates_use_the_documented_header(): void
    {
        $administrator = User::factory()->administrator()->create();

        $content = $this->actingAs($administrator)
            ->get(route('data-transfer.template', 'parcels'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(
            'parcel_number;area_sqm;status;location_description;notes',
            $content,
        );
    }
}
