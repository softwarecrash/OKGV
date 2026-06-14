<?php

namespace App\Services;

use App\Enums\WorkHourSubmissionStatus;
use App\Models\BillingPeriod;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Models\WorkHourSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class WorkHourSubmissionManager
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
        private readonly WorkHourManager $workHourManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        array $data,
        ?UploadedFile $photo,
        User $actor,
    ): WorkHourSubmission {
        $photoPath = null;

        try {
            if ($photo) {
                $photoPath = $photo->store('work-hour-submissions', 'local');
            }

            return DB::transaction(function () use ($data, $photo, $photoPath, $actor): WorkHourSubmission {
                $activeTenancy = ParcelTenant::query()
                    ->where('parcel_id', $data['parcel_id'])
                    ->whereHas('member', fn ($query) => $query->where('user_id', $actor->id))
                    ->activeOn($data['worked_at'])
                    ->exists();

                if (! $activeTenancy) {
                    throw ValidationException::withMessages([
                        'parcel_id' => 'Du bist am angegebenen Datum nicht dieser Parzelle zugeordnet.',
                    ]);
                }

                $period = BillingPeriod::query()
                    ->whereDate('starts_at', '<=', $data['worked_at'])
                    ->whereDate('ends_at', '>=', $data['worked_at'])
                    ->first();

                if (! $period || ! $period->isEditable()) {
                    throw ValidationException::withMessages([
                        'worked_at' => 'Für dieses Datum gibt es keine bearbeitbare Abrechnungsperiode.',
                    ]);
                }

                $submission = WorkHourSubmission::create([
                    'billing_period_id' => $period->id,
                    'parcel_id' => $data['parcel_id'],
                    'submitted_by' => $actor->id,
                    'worked_at' => $data['worked_at'],
                    'hours' => $data['hours'],
                    'description' => $data['description'],
                    'status' => WorkHourSubmissionStatus::Pending,
                    'photo_path' => $photoPath,
                    'photo_original_name' => $photo?->getClientOriginalName(),
                    'photo_mime' => $photo?->getMimeType(),
                    'photo_size' => $photo?->getSize(),
                ]);

                AuditLogger::log('work_hour_submission.created', $actor, $submission, [
                    'parcel_id' => $submission->parcel_id,
                    'hours' => $submission->hours,
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

    public function review(
        WorkHourSubmission $submission,
        User $actor,
        WorkHourSubmissionStatus $status,
        ?string $note,
    ): WorkHourSubmission {
        return $this->periodManager->changeCalculationInputs(
            $submission->billingPeriod,
            $actor,
            'work_hour_submission_reviewed',
            function ($period) use ($submission, $actor, $status, $note): WorkHourSubmission {
                $record = WorkHourSubmission::query()->lockForUpdate()->findOrFail($submission->id);

                if ($record->status !== WorkHourSubmissionStatus::Pending) {
                    throw ValidationException::withMessages([
                        'status' => 'Diese Arbeitsstundenmeldung wurde bereits bearbeitet.',
                    ]);
                }

                $record->update([
                    'status' => $status,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                    'review_note' => $note,
                ]);
                $this->workHourManager->synchronizeParcel($period, $record->parcel_id, $actor);
                AuditLogger::log(
                    $status === WorkHourSubmissionStatus::Approved
                        ? 'work_hour_submission.approved'
                        : 'work_hour_submission.rejected',
                    $actor,
                    $record,
                );

                return $record->refresh();
            },
        );
    }
}
