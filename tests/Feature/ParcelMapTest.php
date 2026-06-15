<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParcelMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_upload_a_private_background_with_rights_source(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->put(route('parcel-map.background.update'), [
                'background' => UploadedFile::fake()->image(
                    'luftbild.jpg',
                    1600,
                    1000,
                ),
                'source' => 'Eigenes Drohnenfoto des Vereins',
                'rights_confirmed' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = ApplicationSetting::current();
        $this->assertSame(1600, $settings->map_background_width);
        $this->assertSame(1000, $settings->map_background_height);
        $this->assertSame(
            'Eigenes Drohnenfoto des Vereins',
            $settings->map_background_source,
        );
        Storage::disk('local')->assertExists($settings->map_background_path);

        $this->actingAs($administrator)
            ->get(route('parcel-map.background'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parcel_map.background.updated',
            'subject_id' => $settings->id,
        ]);
    }

    public function test_background_requires_rights_confirmation_and_map_permission(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);

        $this->actingAs($administrator)
            ->put(route('parcel-map.background.update'), [
                'background' => UploadedFile::fake()->image('luftbild.jpg'),
                'source' => 'Ungeklärte Quelle',
            ])
            ->assertSessionHasErrors('rights_confirmed');

        $this->actingAs($tenant)
            ->get(route('parcel-map.edit'))
            ->assertForbidden();
    }

    public function test_background_rejects_unsupported_image_dimensions(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->put(route('parcel-map.background.update'), [
                'background' => UploadedFile::fake()->image(
                    'zu-klein.jpg',
                    399,
                    299,
                ),
                'source' => 'Eigenes Bild',
                'rights_confirmed' => '1',
            ])
            ->assertSessionHasErrors('background');

        $this->assertNull(ApplicationSetting::current()->map_background_path);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_replacing_background_rescales_existing_polygons_without_deleting_data(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $settings = ApplicationSetting::current();
        $settings->update([
            'map_background_width' => 1200,
            'map_background_height' => 800,
        ]);
        $parcel = Parcel::factory()->create([
            'map_polygon' => [
                ['x' => 120, 'y' => 80],
                ['x' => 240, 'y' => 80],
                ['x' => 240, 'y' => 160],
            ],
        ]);

        $this->actingAs($administrator)
            ->put(route('parcel-map.background.update'), [
                'background' => UploadedFile::fake()->image(
                    'neuer-plan.png',
                    2400,
                    1600,
                ),
                'source' => 'Neuer eigener Plan',
                'rights_confirmed' => '1',
            ])
            ->assertRedirect();

        $this->assertSame([
            ['x' => 240, 'y' => 160],
            ['x' => 480, 'y' => 160],
            ['x' => 480, 'y' => 320],
        ], $parcel->fresh()->map_polygon);
        $this->assertDatabaseHas('parcels', ['id' => $parcel->id]);
        $this->assertDatabaseHas('users', ['id' => $administrator->id]);
    }

    public function test_administrator_can_draw_move_and_remove_arbitrary_polygon(): void
    {
        $administrator = User::factory()->administrator()->create();
        $parcel = Parcel::factory()->create([
            'parcel_number' => 'PLAN-01',
            'status' => ParcelStatus::Blocked,
        ]);
        $polygon = [
            ['x' => 100, 'y' => 120],
            ['x' => 340, 'y' => 90],
            ['x' => 380, 'y' => 250],
            ['x' => 220, 'y' => 330],
            ['x' => 80, 'y' => 240],
        ];

        $this->actingAs($administrator)
            ->put(route('parcel-map.polygon.update', $parcel), [
                'polygon' => json_encode($polygon, JSON_THROW_ON_ERROR),
                'remove_polygon' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $parcel->refresh();
        $this->assertSame($polygon, $parcel->map_polygon);
        $this->assertTrue($parcel->isPlacedOnMap());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parcel_map.polygon.updated',
            'subject_id' => $parcel->id,
        ]);

        $this->actingAs($administrator)
            ->get(route('parcel-map.index'))
            ->assertOk()
            ->assertSee('PLAN-01')
            ->assertSee('100,120 340,90 380,250 220,330 80,240', false)
            ->assertSee('#C62828');

        $this->actingAs($administrator)
            ->put(route('parcel-map.polygon.update', $parcel), [
                'polygon' => '[]',
                'remove_polygon' => '1',
            ])
            ->assertRedirect();

        $this->assertNull($parcel->fresh()->map_polygon);
        $this->assertDatabaseHas('parcels', ['id' => $parcel->id]);
    }

    public function test_polygon_requires_three_in_bounds_points(): void
    {
        $administrator = User::factory()->administrator()->create();
        $parcel = Parcel::factory()->create();

        $this->actingAs($administrator)
            ->put(route('parcel-map.polygon.update', $parcel), [
                'polygon' => json_encode([
                    ['x' => 10, 'y' => 10],
                    ['x' => 20, 'y' => 20],
                ], JSON_THROW_ON_ERROR),
                'remove_polygon' => '0',
            ])
            ->assertSessionHasErrors('polygon');

        $this->actingAs($administrator)
            ->put(route('parcel-map.polygon.update', $parcel), [
                'polygon' => json_encode([
                    ['x' => 10, 'y' => 10],
                    ['x' => 1300, 'y' => 20],
                    ['x' => 20, 'y' => 30],
                ], JSON_THROW_ON_ERROR),
                'remove_polygon' => '0',
            ])
            ->assertSessionHasErrors('polygon.1.x');

        $this->assertNull($parcel->fresh()->map_polygon);
    }

    public function test_tenant_only_sees_currently_assigned_own_polygons(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $polygon = [
            ['x' => 10, 'y' => 10],
            ['x' => 120, 'y' => 10],
            ['x' => 100, 'y' => 100],
        ];
        $ownParcel = Parcel::factory()->create([
            'parcel_number' => 'EIGEN-01',
            'map_polygon' => $polygon,
        ]);
        $foreignParcel = Parcel::factory()->create([
            'parcel_number' => 'FREMD-99',
            'map_polygon' => $polygon,
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $ownParcel->id,
            'member_id' => $member->id,
            'starts_at' => now()->subYear(),
            'ends_at' => null,
        ]);

        $this->actingAs($tenant)
            ->get(route('parcel-map.index'))
            ->assertOk()
            ->assertSee('EIGEN-01')
            ->assertDontSee('FREMD-99')
            ->assertDontSee('Lageplan bearbeiten');

        $this->assertDatabaseHas('parcels', ['id' => $foreignParcel->id]);
    }
}
