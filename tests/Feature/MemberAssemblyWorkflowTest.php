<?php

namespace Tests\Feature;

use App\Enums\MemberAssemblyMode;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\MemberAssembly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberAssemblyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_can_publish_hybrid_assembly_and_member_can_attend_and_vote(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $member = User::factory()->create();
        Member::factory()->create(['user_id' => $member->id, 'joined_at' => now()->subYear()]);
        $this->actingAs($board)->post(route('member-assemblies.store'), [
            'title' => 'Jahreshauptversammlung', 'mode' => MemberAssemblyMode::Hybrid->value,
            'scheduled_at' => now()->addMonth()->format('Y-m-d H:i:s'), 'location' => 'Vereinshaus',
            'electronic_rights_notice' => 'Teilnahme und Wortmeldungen erfolgen im Vereinsportal.',
            'agenda' => 'TOP 1 Begrüßung', 'resolutions' => [['title' => 'Entlastung', 'body' => 'Der Vorstand wird entlastet.']],
        ])->assertRedirect();
        $assembly = MemberAssembly::firstOrFail();
        $this->post(route('member-assemblies.publish', $assembly), ['confirmed' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($assembly->fresh()->published_at);
        $this->assertTrue($member->fresh()->can('view', $assembly->fresh()));
        $this->actingAs($member)->get(route('member-assemblies.show', $assembly))->assertOk();
        $this->post(route('member-assemblies.attend', $assembly))->assertRedirect();
        $this->post(route('member-assemblies.vote', $assembly), ['resolution_id' => $assembly->resolutions()->first()->id, 'choice' => 'yes', 'confirmed' => 1])->assertRedirect();
        $this->post(route('member-assemblies.vote', $assembly), ['resolution_id' => $assembly->resolutions()->first()->id, 'choice' => 'no', 'confirmed' => 1])->assertSessionHasErrors('resolution_id');
    }

    public function test_virtual_assembly_requires_documented_authorization(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $assembly = MemberAssembly::create(['title' => 'Online', 'mode' => MemberAssemblyMode::Virtual, 'scheduled_at' => now()->addMonth(), 'agenda' => 'TOP']);
        $assembly->resolutions()->create(['position' => 0, 'title' => 'Beschluss', 'body' => 'Text']);
        $this->actingAs($board)->post(route('member-assemblies.publish', $assembly), ['confirmed' => 1])->assertSessionHasErrors('electronic_rights_notice');
        $assembly->update(['electronic_rights_notice' => 'Portal']);
        $this->post(route('member-assemblies.publish', $assembly), ['confirmed' => 1])->assertSessionHasErrors('virtual_authorization_reference');
    }
}
