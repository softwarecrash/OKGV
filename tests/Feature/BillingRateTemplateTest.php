<?php

namespace Tests\Feature;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\BillingRateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingRateTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_can_create_and_update_template(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);

        $this->actingAs($board)
            ->post(route('billing-rate-templates.store'), [
                'code' => 'Pacht pro qm',
                'name' => 'Pacht pro Quadratmeter',
                'calculation_type' => BillingRateType::PerSquareMeter->value,
                'scope' => BillingRateScope::Parcel->value,
                'default_amount' => '0.5000',
                'description' => 'Jährlich prüfen',
                'is_active' => '1',
            ])
            ->assertRedirect(route('billing-rate-templates.index'));

        $template = BillingRateTemplate::query()->firstOrFail();

        $this->assertSame('PACHT_PRO_QM', $template->code);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billing.rate_template.created',
            'subject_id' => $template->id,
        ]);

        $this->actingAs($board)
            ->put(route('billing-rate-templates.update', $template), [
                'code' => 'PACHT_PRO_QM',
                'name' => 'Pacht je Quadratmeter',
                'calculation_type' => BillingRateType::PerSquareMeter->value,
                'scope' => BillingRateScope::Parcel->value,
                'default_amount' => '0.6000',
                'is_active' => '1',
            ])
            ->assertRedirect(route('billing-rate-templates.index'));

        $this->assertSame('Pacht je Quadratmeter', $template->fresh()->name);
    }

    public function test_treasurer_can_use_but_not_manage_templates(): void
    {
        $treasurer = User::factory()->create(['role' => UserRole::Treasurer]);

        $this->actingAs($treasurer)
            ->get(route('billing-rate-templates.index'))
            ->assertOk();

        $this->actingAs($treasurer)
            ->get(route('billing-rate-templates.create'))
            ->assertForbidden();
    }

    public function test_template_creates_period_snapshot_with_editable_amount(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $period = BillingPeriod::factory()->create();
        $template = BillingRateTemplate::factory()->create([
            'code' => 'WATER_PER_M3',
            'name' => 'Wasserverbrauch',
            'description' => 'Preis je Kubikmeter',
            'calculation_type' => BillingRateType::PerCubicMeter,
            'scope' => BillingRateScope::Parcel,
            'default_amount' => '2.5000',
        ]);

        $this->actingAs($board)
            ->get(route('billing-periods.billing-rates.create', [
                $period,
                'template' => $template->id,
            ]))
            ->assertOk()
            ->assertSee('passe unten nur den Betrag')
            ->assertSee('2.5000');

        $this->actingAs($board)
            ->post(route('billing-periods.billing-rates.store', $period), [
                'billing_rate_template_id' => $template->id,
                'code' => 'MANIPULIERT',
                'name' => 'Manipuliert',
                'calculation_type' => BillingRateType::Fixed->value,
                'scope' => BillingRateScope::Member->value,
                'amount' => '2.7500',
                'is_active' => '1',
            ])
            ->assertRedirect(route('billing-periods.show', $period));

        $rate = BillingRate::query()->firstOrFail();

        $this->assertSame($template->id, $rate->billing_rate_template_id);
        $this->assertSame('WATER_PER_M3', $rate->code);
        $this->assertSame('Wasserverbrauch', $rate->name);
        $this->assertSame(BillingRateType::PerCubicMeter, $rate->calculation_type);
        $this->assertSame(BillingRateScope::Parcel, $rate->scope);
        $this->assertSame('2.7500', $rate->amount);

        $template->update([
            'name' => 'Geänderte Vorlage',
            'default_amount' => '9.0000',
        ]);

        $this->assertSame('Wasserverbrauch', $rate->fresh()->name);
        $this->assertSame('2.7500', $rate->fresh()->amount);
    }
}
