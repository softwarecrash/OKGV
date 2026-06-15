<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\MemberStatus;
use App\Enums\NumberSequenceType;
use App\Models\Document;
use App\Models\Member;
use App\Models\NumberSequence;
use App\Models\SepaMandate;
use App\Models\User;
use App\Services\NumberSequenceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NumberSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_configure_number_sequences(): void
    {
        $administrator = User::factory()->administrator()->create();
        $tenant = User::factory()->create();

        $this->actingAs($tenant)
            ->get(route('number-sequences.edit'))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->get(route('number-sequences.edit'))
            ->assertOk()
            ->assertSee('Nummernkreise')
            ->assertSee('{NUMMER}');

        $payload = $this->sequencePayload();
        $payload['sequences']['member'] = [
            'format' => 'mit {nummer}',
            'padding' => 6,
            'next_value' => 42,
            'reset_yearly' => 0,
        ];

        $this->actingAs($administrator)
            ->put(route('number-sequences.update'), $payload)
            ->assertRedirect();

        $sequence = NumberSequence::query()
            ->where('type', NumberSequenceType::Member)
            ->firstOrFail();
        $this->assertSame('MIT-{NUMMER}', $sequence->format);
        $this->assertSame(6, $sequence->padding);
        $this->assertSame(42, $sequence->next_value);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'number_sequence.updated',
            'subject_id' => $sequence->id,
        ]);
    }

    public function test_yearly_sequences_require_the_year_placeholder(): void
    {
        $administrator = User::factory()->administrator()->create();
        $payload = $this->sequencePayload();
        $payload['sequences']['invoice']['format'] = 'RE-{NUMMER}';
        $payload['sequences']['invoice']['reset_yearly'] = 1;

        $this->actingAs($administrator)
            ->put(route('number-sequences.update'), $payload)
            ->assertSessionHasErrors('sequences.invoice.format');
    }

    public function test_generator_resets_annually_and_skips_existing_numbers(): void
    {
        Member::factory()->create(['member_number' => 'M-0001']);
        $manager = app(NumberSequenceManager::class);

        $this->assertSame(
            'M-0002',
            $manager->next(NumberSequenceType::Member, '2026-01-01'),
        );

        $sequence = NumberSequence::query()
            ->where('type', NumberSequenceType::Document)
            ->firstOrFail();
        $sequence->update([
            'format' => 'D-{JAHR}-{NUMMER}',
            'padding' => 3,
            'next_value' => 77,
            'reset_yearly' => true,
            'last_year' => 2025,
        ]);

        $this->assertSame(
            'D-2026-001',
            $manager->next(NumberSequenceType::Document, '2026-03-01'),
        );
        $this->assertSame(
            'D-2026-002',
            $manager->next(NumberSequenceType::Document, '2026-04-01'),
        );
        $this->assertDatabaseHas('members', ['member_number' => 'M-0001']);
    }

    public function test_new_records_receive_automatic_numbers_when_left_empty(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('members.store'), [
                'member_number' => '',
                'first_name' => 'Erika',
                'last_name' => 'Automatik',
                'street' => 'Gartenweg 1',
                'zip' => '12345',
                'city' => 'Musterstadt',
                'joined_at' => '2026-01-01',
                'status' => MemberStatus::Active->value,
            ])
            ->assertRedirect();

        $member = Member::query()->firstOrFail();
        $this->assertSame('M-0001', $member->member_number);

        $this->actingAs($administrator)
            ->post(route('sepa-mandates.store'), [
                'member_id' => $member->id,
                'mandate_reference' => '',
                'iban' => 'DE89370400440532013000',
                'account_holder' => 'Erika Automatik',
                'signed_at' => '2026-02-01',
                'valid_from' => '2026-02-01',
                'mandate_type' => 'recurring',
                'status' => 'active',
            ])
            ->assertRedirect(route('sepa-mandates.index'));

        $this->assertSame(
            'MANDAT-2026-0001',
            SepaMandate::query()->firstOrFail()->mandate_reference,
        );

        $this->actingAs($administrator)
            ->post(route('documents.store'), [
                'title' => 'Automatisch nummeriertes Dokument',
                'type' => DocumentType::Other->value,
                'visibility' => DocumentVisibility::Internal->value,
                'published' => false,
                'file' => UploadedFile::fake()->createWithContent(
                    'dokument.pdf',
                    "%PDF-1.4\nTest",
                ),
            ])
            ->assertRedirect();

        $this->assertMatchesRegularExpression(
            '/^DOK-\d{4}-00001$/',
            Document::query()->firstOrFail()->document_number,
        );
    }

    /**
     * @return array{sequences: array<string, array<string, mixed>>}
     */
    private function sequencePayload(): array
    {
        return [
            'sequences' => NumberSequence::query()
                ->get()
                ->mapWithKeys(fn (NumberSequence $sequence): array => [
                    $sequence->type->value => [
                        'format' => $sequence->format,
                        'padding' => $sequence->padding,
                        'next_value' => $sequence->next_value,
                        'reset_yearly' => $sequence->reset_yearly ? 1 : 0,
                    ],
                ])
                ->all(),
        ];
    }
}
