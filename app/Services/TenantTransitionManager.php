<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\FeatureModule;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSource;
use App\Enums\NumberSequenceType;
use App\Enums\ParcelStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Meter;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\TenantTransition;
use App\Models\TenantTransitionMeterReading;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class TenantTransitionManager
{
    public function __construct(
        private readonly ParcelTenancyManager $tenancyManager,
        private readonly MeterReadingManager $meterReadingManager,
        private readonly WorkHourManager $workHourManager,
        private readonly NumberSequenceManager $numberSequenceManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $photos
     * @param  list<UploadedFile>  $documents
     */
    public function complete(
        array $data,
        array $photos,
        array $documents,
        User $actor,
    ): TenantTransition {
        $storedFiles = [];

        try {
            foreach ([...$photos, ...$documents] as $file) {
                $storedFiles[spl_object_id($file)] = $file->store('tenant-transitions', 'local');
            }

            return DB::transaction(function () use (
                $data,
                $photos,
                $documents,
                $actor,
                $storedFiles,
            ): TenantTransition {
                $parcel = Parcel::query()->lockForUpdate()->findOrFail($data['parcel_id']);
                $transferDate = CarbonImmutable::parse($data['transfer_date'])->startOfDay();
                $previousDay = $transferDate->copy()->subDay();

                $outgoingTenancies = ParcelTenant::query()
                    ->where('parcel_id', $parcel->id)
                    ->activeOn($previousDay)
                    ->with('member')
                    ->orderByDesc('is_primary')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $outgoingPrimary = $outgoingTenancies->firstWhere('is_primary', true);

                if (! $outgoingPrimary) {
                    throw ValidationException::withMessages([
                        'transfer_date' => 'Am Tag vor der Übergabe ist kein Hauptpächter eingetragen.',
                    ]);
                }

                $incomingMemberIds = collect([
                    $data['incoming_primary_member_id'],
                    ...($data['incoming_co_member_ids'] ?? []),
                ])->map(fn ($id) => (int) $id)->unique()->values();
                $incomingMembers = Member::query()
                    ->whereKey($incomingMemberIds)
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($outgoingTenancies as $tenancy) {
                    $tenancy->update(['ends_at' => $previousDay]);
                }

                $incomingPrimary = $this->tenancyManager->save([
                    'parcel_id' => $parcel->id,
                    'member_id' => (int) $data['incoming_primary_member_id'],
                    'starts_at' => $transferDate,
                    'ends_at' => null,
                    'is_primary' => true,
                    'notes' => 'Angelegt durch Pächterwechsel.',
                ]);

                foreach ($incomingMemberIds->reject(
                    fn (int $id) => $id === (int) $data['incoming_primary_member_id'],
                ) as $memberId) {
                    $this->tenancyManager->save([
                        'parcel_id' => $parcel->id,
                        'member_id' => $memberId,
                        'starts_at' => $transferDate,
                        'ends_at' => null,
                        'is_primary' => false,
                        'notes' => 'Angelegt durch Pächterwechsel.',
                    ]);
                }

                $transition = TenantTransition::create([
                    'parcel_id' => $parcel->id,
                    'outgoing_primary_tenancy_id' => $outgoingPrimary->id,
                    'incoming_primary_tenancy_id' => $incomingPrimary->id,
                    'transfer_date' => $transferDate,
                    'outgoing_members_snapshot' => $this->memberSnapshots(
                        $outgoingTenancies->pluck('member'),
                        $outgoingPrimary->member_id,
                    ),
                    'incoming_members_snapshot' => $this->memberSnapshots(
                        $incomingMemberIds->map(fn (int $id) => $incomingMembers->get($id)),
                        (int) $data['incoming_primary_member_id'],
                    ),
                    'open_claims_snapshot' => $this->openClaimsSnapshot(
                        $outgoingTenancies->pluck('member_id'),
                    ),
                    'notes' => $data['notes'] ?? null,
                    'completed_by' => $actor->id,
                    'completed_at' => now(),
                ]);

                $meters = Meter::query()
                    ->where('parcel_id', $parcel->id)
                    ->whereKey(array_keys($data['meter_readings'] ?? []))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($data['meter_readings'] ?? [] as $meterId => $value) {
                    $meter = $meters->get((int) $meterId);
                    $reading = $meter->readings()
                        ->whereDate('reading_date', $transferDate)
                        ->first();

                    if (! $reading) {
                        try {
                            $reading = $this->meterReadingManager->create([
                                'meter_id' => $meter->id,
                                'reading_value' => $value,
                                'reading_date' => $transferDate->toDateString(),
                                'source' => MeterReadingSource::Board,
                                'notes' => 'Übergabestand aus Pächterwechsel #'.$transition->id,
                            ]);
                        } catch (ValidationException $exception) {
                            throw ValidationException::withMessages([
                                "meter_readings.{$meter->id}" => collect($exception->errors())
                                    ->flatten()
                                    ->first() ?? 'Der Übergabezählerstand ist nicht zulässig.',
                            ]);
                        }
                    } elseif (bccomp(
                        (string) $reading->effective_reading_value,
                        (string) $value,
                        4,
                    ) !== 0) {
                        throw ValidationException::withMessages([
                            "meter_readings.{$meter->id}" => 'Für diesen Tag ist bereits ein abweichender Zählerstand gespeichert.',
                        ]);
                    }

                    TenantTransitionMeterReading::create([
                        'tenant_transition_id' => $transition->id,
                        'meter_id' => $meter->id,
                        'meter_reading_id' => $reading->id,
                        'reading_value' => $reading->effective_reading_value,
                    ]);
                }

                foreach ($photos as $file) {
                    $this->attachDocument(
                        $transition,
                        $file,
                        $storedFiles[spl_object_id($file)],
                        'photo',
                        DocumentType::Photo,
                        $actor,
                    );
                }
                foreach ($documents as $file) {
                    $this->attachDocument(
                        $transition,
                        $file,
                        $storedFiles[spl_object_id($file)],
                        'document',
                        DocumentType::HandoverProtocol,
                        $actor,
                    );
                }

                $parcel->update(['status' => ParcelStatus::Assigned]);

                if (FeatureModule::WorkHours->enabled()) {
                    $this->workHourManager->synchronizeParcels([$parcel->id], $actor);
                }

                AuditLogger::log('tenant_transition.completed', $actor, $transition, [
                    'parcel_id' => $parcel->id,
                    'transfer_date' => $transferDate->toDateString(),
                    'outgoing_member_ids' => $outgoingTenancies->pluck('member_id')->all(),
                    'incoming_member_ids' => $incomingMemberIds->all(),
                    'meter_reading_ids' => $transition->meterReadings()->pluck('meter_reading_id')->all(),
                    'document_ids' => $transition->documents()->pluck('documents.id')->all(),
                ]);

                return $transition->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values($storedFiles));
            throw $exception;
        }
    }

    /**
     * @param  Collection<int, Member|null>  $members
     * @return list<array<string, mixed>>
     */
    private function memberSnapshots(Collection $members, int $primaryMemberId): array
    {
        return $members->filter()->map(fn (Member $member) => [
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'street' => $member->street,
            'zip' => $member->zip,
            'city' => $member->city,
            'is_primary' => $member->id === $primaryMemberId,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, int>  $memberIds
     * @return list<array<string, mixed>>
     */
    private function openClaimsSnapshot(Collection $memberIds): array
    {
        return Invoice::query()
            ->whereIn('member_id', $memberIds)
            ->where('status', InvoiceStatus::Approved)
            ->whereIn('payment_status', [
                InvoicePaymentStatus::Open,
                InvoicePaymentStatus::Pending,
                InvoicePaymentStatus::Returned,
            ])
            ->with('member')
            ->orderBy('due_at')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'member_id' => $invoice->member_id,
                'member_name' => $invoice->member->full_name,
                'due_at' => $invoice->due_at->toDateString(),
                'total_amount' => $invoice->total_amount,
                'payment_status' => $invoice->payment_status->value,
                'payment_status_label' => $invoice->payment_status->label(),
            ])
            ->all();
    }

    private function attachDocument(
        TenantTransition $transition,
        UploadedFile $file,
        string $path,
        string $category,
        DocumentType $type,
        User $actor,
    ): void {
        $document = Document::create([
            'document_number' => $this->numberSequenceManager->next(NumberSequenceType::Document),
            'parcel_id' => $transition->parcel_id,
            'uploaded_by' => $actor->id,
            'title' => ($category === 'photo' ? 'Übergabefoto ' : 'Übergabeprotokoll ')
                .$transition->parcel->parcel_number.' vom '.$transition->transfer_date->format('d.m.Y'),
            'description' => 'Nachweis aus Pächterwechsel #'.$transition->id,
            'type' => $type,
            'visibility' => DocumentVisibility::Internal,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize(),
            'current_version' => 1,
        ]);

        DocumentVersion::create([
            'document_id' => $document->id,
            'uploaded_by' => $actor->id,
            'version_number' => 1,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize(),
        ]);

        $transition->documents()->attach($document->id, ['category' => $category]);
    }
}
