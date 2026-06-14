<?php

namespace App\Services;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingSubmissionStatus;
use App\Models\Meter;
use App\Models\MeterReadingSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class MeterReadingSubmissionManager
{
    public function __construct(private readonly MeterReadingManager $readingManager) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        Meter $meter,
        array $data,
        ?UploadedFile $photo,
        User $actor,
    ): MeterReadingSubmission {
        $photoPath = null;

        try {
            if ($photo) {
                $photoPath = $photo->store('meter-reading-submissions', 'local');
            }

            return DB::transaction(function () use ($meter, $data, $photo, $photoPath, $actor): MeterReadingSubmission {
                $meter = Meter::query()->lockForUpdate()->findOrFail($meter->id);

                $hasActiveTenancy = $meter->parcel->tenancies()
                    ->activeOn($data['reading_date'])
                    ->whereHas('member', fn ($query) => $query->where('user_id', $actor->id))
                    ->exists();

                if (! $hasActiveTenancy) {
                    throw ValidationException::withMessages([
                        'meter_id' => 'Dieser Zähler gehört am Ablesedatum nicht zu deiner Parzelle.',
                    ]);
                }

                $duplicateExists = MeterReadingSubmission::query()
                    ->where('meter_id', $meter->id)
                    ->where('submitted_by', $actor->id)
                    ->whereDate('reading_date', $data['reading_date'])
                    ->where('status', MeterReadingSubmissionStatus::Pending)
                    ->exists();

                if ($duplicateExists) {
                    throw ValidationException::withMessages([
                        'reading_date' => 'Für diesen Zähler und Tag wird bereits eine Meldung geprüft.',
                    ]);
                }

                $submission = MeterReadingSubmission::create([
                    'meter_id' => $meter->id,
                    'submitted_by' => $actor->id,
                    'reading_value' => $data['reading_value'],
                    'reading_date' => $data['reading_date'],
                    'photo_path' => $photoPath,
                    'photo_original_name' => $photo?->getClientOriginalName(),
                    'photo_mime' => $photo?->getMimeType(),
                    'photo_size' => $photo?->getSize(),
                    'notes' => $data['notes'] ?? null,
                    'status' => MeterReadingSubmissionStatus::Pending,
                ]);

                AuditLogger::log('meter_reading.submission.created', $actor, $submission, [
                    'meter_id' => $meter->id,
                    'has_photo' => $photo !== null,
                ]);

                return $submission;
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }
    }

    public function approve(
        MeterReadingSubmission $submission,
        User $actor,
        ?string $reviewNote = null,
    ): MeterReadingSubmission {
        return DB::transaction(function () use ($submission, $actor, $reviewNote): MeterReadingSubmission {
            $submission = MeterReadingSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            $this->ensurePending($submission);

            $reading = $this->readingManager->create([
                'meter_id' => $submission->meter_id,
                'reading_value' => $submission->reading_value,
                'reading_date' => $submission->reading_date->toDateString(),
                'source' => MeterReadingSource::Tenant,
                'photo_path' => $submission->photo_path,
                'notes' => $submission->notes,
            ]);

            $submission->update([
                'status' => MeterReadingSubmissionStatus::Approved,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'meter_reading_id' => $reading->id,
            ]);

            AuditLogger::log('meter_reading.submission.approved', $actor, $submission, [
                'meter_reading_id' => $reading->id,
            ]);

            return $submission;
        });
    }

    public function reject(
        MeterReadingSubmission $submission,
        User $actor,
        string $reviewNote,
    ): MeterReadingSubmission {
        return DB::transaction(function () use ($submission, $actor, $reviewNote): MeterReadingSubmission {
            $submission = MeterReadingSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            $this->ensurePending($submission);
            $submission->update([
                'status' => MeterReadingSubmissionStatus::Rejected,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);

            AuditLogger::log('meter_reading.submission.rejected', $actor, $submission);

            return $submission;
        });
    }

    private function ensurePending(MeterReadingSubmission $submission): void
    {
        if ($submission->status !== MeterReadingSubmissionStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Diese Zählerstandsmeldung wurde bereits bearbeitet.',
            ]);
        }
    }
}
