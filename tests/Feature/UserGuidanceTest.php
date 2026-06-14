<?php

namespace Tests\Feature;

use App\Models\BillingPeriod;
use App\Models\Meter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_offers_password_visibility_toggle_and_shared_device_warning(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Passwort anzeigen"', false)
            ->assertDontSee('Verwende die E-Mail-Adresse, die deinem OKGV-Konto zugeordnet ist.')
            ->assertDontSee('Das Passwort wird verdeckt eingegeben und nicht im Klartext gespeichert.')
            ->assertSee('Nur auf einem persönlichen Gerät verwenden.');
    }

    public function test_master_data_forms_explain_history_and_visibility(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('members.create'))
            ->assertOk()
            ->assertSee('Historien bleiben erhalten.')
            ->assertSee('Nur für berechtigte Vereinskonten sichtbar.');

        $this->actingAs($administrator)
            ->get(route('parcel-tenants.create'))
            ->assertOk()
            ->assertSee('Mehrere Mitglieder können gleichzeitig derselben Parzelle zugeordnet sein')
            ->assertSee('Pro Zeitraum kann genau eine Person Hauptpächter sein.');
    }

    public function test_meter_workflows_explain_append_only_history(): void
    {
        $administrator = User::factory()->administrator()->create();
        $meter = Meter::factory()->create();

        $this->actingAs($administrator)
            ->get(route('meters.replace', $meter))
            ->assertOk()
            ->assertSee('Dauerhafte Historienänderung')
            ->assertSee('Der bisherige Zähler wird abgeschlossen');

        $this->actingAs($administrator)
            ->get(route('meter-readings.create', ['meter_id' => $meter->id]))
            ->assertOk()
            ->assertSee('nicht nachträglich überschrieben')
            ->assertSee('nachvollziehbare Korrektur');
    }

    public function test_billing_forms_explain_calculation_and_irreversibility(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create();

        $this->actingAs($administrator)
            ->get(route('billing-periods.billing-rates.create', $period))
            ->assertOk()
            ->assertSee('Berechnungsart und Geltungsbereich')
            ->assertSee('Leerzeichen werden automatisch ersetzt.');

        $this->actingAs($administrator)
            ->get(route('billing-periods.show', $period))
            ->assertOk()
            ->assertSee('Vorhandene Entwürfe dieser Periode werden durch die aktuelle Berechnung ersetzt.');
    }

    public function test_validation_errors_use_understandable_german_field_names(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)
            ->from(route('members.create'))
            ->post(route('members.store'), []);

        $response->assertRedirect(route('members.create'));
        $response->assertSessionHasErrors([
            'member_number' => 'Bitte fülle das Feld Mitgliedsnummer aus.',
            'first_name' => 'Bitte fülle das Feld Vorname aus.',
        ]);
    }
}
