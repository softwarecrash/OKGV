<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReadingSubmission;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Services\ActionIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_navigation_groups_tasks_and_shows_action_indicators(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        RegistrationRequest::factory()->count(2)->create([
            'status' => RegistrationRequestStatus::Pending,
        ]);
        MeterReadingSubmission::factory()->create([
            'status' => MeterReadingSubmissionStatus::Pending,
        ]);

        $indicators = app(ActionIndicatorService::class)->forUser($board);
        $this->assertSame(2, $indicators['registrations']);
        $this->assertSame(1, $indicators['meter_readings']);
        $this->assertSame(3, $indicators['total']);

        $this->actingAs($board)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Mitglieder')
            ->assertSee('Registrierungsanfragen')
            ->assertSee('Zählerstandsmeldungen')
            ->assertSee('Finanzen')
            ->assertSee('2 wartende Registrierungen')
            ->assertSee('1 offene Zählerstandsmeldung')
            ->assertDontSee('Rechteverwaltung');
    }

    public function test_tenant_only_receives_indicators_for_own_required_actions(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $otherTenant = User::factory()->create(['role' => UserRole::Tenant]);
        $otherMember = Member::factory()->create(['user_id' => $otherTenant->id]);

        Invoice::factory()->create([
            'member_id' => $member->id,
            'status' => InvoiceStatus::Approved,
            'payment_status' => InvoicePaymentStatus::Open,
        ]);
        Invoice::factory()->create([
            'member_id' => $otherMember->id,
            'status' => InvoiceStatus::Approved,
            'payment_status' => InvoicePaymentStatus::Open,
        ]);
        MeterReadingSubmission::factory()->create([
            'submitted_by' => $tenant->id,
            'status' => MeterReadingSubmissionStatus::Rejected,
        ]);
        MeterReadingSubmission::factory()->create([
            'submitted_by' => $otherTenant->id,
            'status' => MeterReadingSubmissionStatus::Rejected,
        ]);

        $indicators = app(ActionIndicatorService::class)->forUser($tenant);
        $this->assertSame(1, $indicators['invoices']);
        $this->assertSame(1, $indicators['meter_readings']);
        $this->assertSame(2, $indicators['total']);

        $this->actingAs($tenant)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('2 offene Aufgaben')
            ->assertSee('1 offene Rechnungen')
            ->assertDontSee('Registrierungsanfragen');
    }

    public function test_administrator_finds_rights_management_in_account_menu(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Rechteverwaltung')
            ->assertSee('data-theme-toggle', false)
            ->assertSee('js/theme-init.js')
            ->assertSee('Darstellungsmodus wechseln');
    }

    public function test_newer_meter_submission_resolves_tenant_action_indicator(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $meter = Meter::factory()->create();
        MeterReadingSubmission::factory()->create([
            'meter_id' => $meter->id,
            'submitted_by' => $tenant->id,
            'status' => MeterReadingSubmissionStatus::Rejected,
        ]);

        $this->assertSame(
            1,
            app(ActionIndicatorService::class)->forUser($tenant)['meter_readings'],
        );

        MeterReadingSubmission::factory()->create([
            'meter_id' => $meter->id,
            'submitted_by' => $tenant->id,
            'status' => MeterReadingSubmissionStatus::Pending,
        ]);

        $indicators = app(ActionIndicatorService::class)->forUser($tenant);
        $this->assertSame(0, $indicators['meter_readings']);
        $this->assertSame(0, $indicators['total']);
    }

    public function test_board_member_logs_out_through_a_native_post_form(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);

        $this->actingAs($board)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-logout-form', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST" data-logout-form', false)
            ->assertDontSee('href="'.route('logout').'"', false);

        $this->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
