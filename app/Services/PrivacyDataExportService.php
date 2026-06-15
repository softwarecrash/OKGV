<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\TenantTransition;
use Illuminate\Support\Facades\DB;

final class PrivacyDataExportService
{
    /**
     * Build a machine-readable copy of the personal data stored for a member.
     *
     * @return array<string, mixed>
     */
    public function build(Member $member): array
    {
        $member->load([
            'user',
            'privacySetting',
            'parcelTenancies.parcel',
            'invoices.items',
            'invoices.recipients',
            'sepaMandates',
            'documents.versions',
            'workEventParticipations.workEvent',
            'inventoryLoans.inventoryItem',
            'privacyErasureRequests',
        ]);

        return [
            'exported_at' => now()->toIso8601String(),
            'application' => config('app.name'),
            'member' => $member->only([
                'id',
                'member_number',
                'first_name',
                'last_name',
                'street',
                'zip',
                'city',
                'phone',
                'mobile',
                'email',
                'joined_at',
                'left_at',
                'status',
                'notes',
                'archived_at',
                'created_at',
                'updated_at',
            ]),
            'account' => $member->user?->only([
                'id',
                'name',
                'email',
                'role',
                'email_verified_at',
                'created_at',
                'updated_at',
            ]),
            'privacy_settings' => $member->privacySetting?->only([
                'share_name',
                'share_email',
                'share_phone',
                'share_mobile',
                'share_address',
                'consented_at',
                'updated_at',
            ]) ?? [
                'share_name' => false,
                'share_email' => false,
                'share_phone' => false,
                'share_mobile' => false,
                'share_address' => false,
                'consented_at' => null,
            ],
            'parcel_tenancies' => $member->parcelTenancies->map(fn ($tenancy): array => [
                ...$tenancy->only(['id', 'starts_at', 'ends_at', 'is_primary', 'notes']),
                'parcel' => $tenancy->parcel->only([
                    'id',
                    'parcel_number',
                    'area_sqm',
                    'status',
                    'location_description',
                ]),
            ])->all(),
            'invoices' => $member->invoices->map(fn ($invoice): array => [
                ...$invoice->only([
                    'id',
                    'invoice_number',
                    'status',
                    'payment_status',
                    'issued_at',
                    'due_at',
                    'total_amount',
                    'approved_at',
                    'paid_at',
                ]),
                'recipients' => $invoice->recipients->map->only([
                    'member_number',
                    'first_name',
                    'last_name',
                    'street',
                    'zip',
                    'city',
                    'is_primary',
                ])->all(),
                'items' => $invoice->items->map->only([
                    'description',
                    'quantity',
                    'unit',
                    'unit_price',
                    'amount',
                ])->all(),
            ])->all(),
            'sepa_mandates' => $member->sepaMandates->map(fn ($mandate): array => [
                ...$mandate->only([
                    'mandate_reference',
                    'iban',
                    'bic',
                    'account_holder',
                    'signed_at',
                    'valid_from',
                    'valid_until',
                    'mandate_type',
                    'status',
                    'last_used_at',
                ]),
            ])->all(),
            'documents' => $member->documents->map(fn ($document): array => [
                ...$document->only([
                    'id',
                    'title',
                    'description',
                    'type',
                    'visibility',
                    'original_name',
                    'mime_type',
                    'file_size',
                    'published_at',
                    'archived_at',
                    'created_at',
                ]),
                'versions' => $document->versions->map->only([
                    'version_number',
                    'original_name',
                    'mime_type',
                    'file_size',
                    'created_at',
                ])->all(),
            ])->all(),
            'work_event_participations' => $member->workEventParticipations
                ->map(fn ($participation): array => [
                    ...$participation->only([
                        'parcel_id',
                        'status',
                        'hours_credited',
                        'notes',
                    ]),
                    'event' => $participation->workEvent?->only([
                        'title',
                        'starts_at',
                        'ends_at',
                        'location',
                    ]),
                ])->all(),
            'inventory_loans' => $member->inventoryLoans->map(fn ($loan): array => [
                ...$loan->only([
                    'borrower_name',
                    'issued_at',
                    'due_at',
                    'returned_at',
                    'condition_on_issue',
                    'condition_on_return',
                    'notes',
                ]),
                'item' => $loan->inventoryItem?->only([
                    'inventory_number',
                    'name',
                    'category',
                ]),
            ])->all(),
            'meter_reading_submissions' => $this->recordsForUser(
                'meter_reading_submissions',
                $member->user_id,
                [
                    'meter_id',
                    'reading_value',
                    'reading_date',
                    'status',
                    'photo_original_name',
                    'notes',
                    'reviewed_at',
                    'review_note',
                    'created_at',
                ],
            ),
            'work_hour_submissions' => $this->recordsForUser(
                'work_hour_submissions',
                $member->user_id,
                [
                    'billing_period_id',
                    'parcel_id',
                    'worked_at',
                    'hours',
                    'description',
                    'status',
                    'photo_original_name',
                    'reviewed_at',
                    'review_note',
                    'created_at',
                ],
            ),
            'tenant_transitions' => TenantTransition::query()
                ->with('parcel')
                ->orderBy('transfer_date')
                ->get()
                ->map(function (TenantTransition $transition) use ($member): ?array {
                    $snapshots = collect([
                        ...$transition->outgoing_members_snapshot,
                        ...$transition->incoming_members_snapshot,
                    ])->where('member_id', $member->id)->values();

                    if ($snapshots->isEmpty()) {
                        return null;
                    }

                    return [
                        'id' => $transition->id,
                        'transfer_date' => $transition->transfer_date,
                        'parcel_number' => $transition->parcel->parcel_number,
                        'member_snapshots' => $snapshots->all(),
                        'completed_at' => $transition->completed_at,
                    ];
                })
                ->filter()
                ->values()
                ->all(),
            'mail_history' => DB::table('mail_campaign_recipients')
                ->where('member_id', $member->id)
                ->orderBy('created_at')
                ->get([
                    'name',
                    'email',
                    'status',
                    'error_message',
                    'sent_at',
                    'created_at',
                ])
                ->map(fn (object $record): array => (array) $record)
                ->all(),
            'letters' => DB::table('letters')
                ->where('member_id', $member->id)
                ->orderBy('created_at')
                ->get([
                    'recipient_name',
                    'street',
                    'zip',
                    'city',
                    'subject',
                    'body',
                    'created_at',
                ])
                ->map(fn (object $record): array => (array) $record)
                ->all(),
            'registration_requests' => $member->email === null
                ? []
                : DB::table('registration_requests')
                    ->where('email', $member->email)
                    ->orderBy('created_at')
                    ->get([
                        'first_name',
                        'last_name',
                        'email',
                        'parcel_number',
                        'status',
                        'reviewed_at',
                        'review_note',
                        'created_at',
                    ])
                    ->map(fn (object $record): array => (array) $record)
                    ->all(),
            'erasure_requests' => $member->privacyErasureRequests->map->only([
                'status',
                'requested_at',
                'reviewed_at',
                'review_note',
                'blockers',
                'completed_at',
            ])->all(),
            'audit_events' => $this->auditEvents($member),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditEvents(Member $member): array
    {
        return AuditLog::query()
            ->where(function ($query) use ($member): void {
                $query->where(function ($query) use ($member): void {
                    $query->where('subject_type', $member->getMorphClass())
                        ->where('subject_id', $member->id);
                });

                if ($member->user_id !== null) {
                    $query->orWhere('user_id', $member->user_id);
                }
            })
            ->orderBy('created_at')
            ->get()
            ->map->only(['action', 'created_at'])
            ->all();
    }

    /**
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private function recordsForUser(string $table, ?int $userId, array $columns): array
    {
        if ($userId === null) {
            return [];
        }

        return DB::table($table)
            ->where('submitted_by', $userId)
            ->orderBy('created_at')
            ->get($columns)
            ->map(fn (object $record): array => (array) $record)
            ->all();
    }
}
