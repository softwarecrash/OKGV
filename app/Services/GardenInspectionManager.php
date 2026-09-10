<?php

namespace App\Services;

use App\Enums\GardenInspectionFindingStatus;
use App\Models\GardenInspection;
use App\Models\GardenInspectionFinding;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class GardenInspectionManager
{
    public function __construct(private readonly GardenInspectionPdfGenerator $pdf) {}

    public function create(array $data, User $actor): GardenInspection
    {
        return DB::transaction(function () use ($data, $actor) {
            Gate::forUser($actor)->authorize('create', GardenInspection::class);
            $model = new GardenInspection($data);
            $model->created_by = $actor->id;
            $model->save();
            AuditLogger::log('garden_inspection.created', $actor, $model);

            return $model;
        });
    }

    public function finding(GardenInspection $inspection, array $data, ?UploadedFile $photo, User $actor): GardenInspectionFinding
    {
        $path = null;
        try {
            return DB::transaction(function () use ($inspection, $data, $photo, $actor, &$path) {
                $inspection = GardenInspection::query()->lockForUpdate()->findOrFail($inspection->id);
                Gate::forUser($actor)->authorize('create', GardenInspection::class);
                if ($inspection->isFinalized()) {
                    throw ValidationException::withMessages(['inspection' => 'Abgeschlossene Begehungen können nicht mehr ergänzt werden.']);
                }
                if (! empty($data['task_id'])) {
                    $task = Task::query()->findOrFail($data['task_id']);
                    if (! $actor->can('view', $task)) {
                        throw ValidationException::withMessages(['task_id' => 'Bitte nur Aufgaben auswählen, auf die du Zugriff hast.']);
                    }
                }
                $model = new GardenInspectionFinding($data);
                $model->garden_inspection_id = $inspection->id;
                $model->status = GardenInspectionFindingStatus::Open;
                $model->recorded_by = $actor->id;
                if ($photo) {
                    $path = 'garden-inspections/'.Str::uuid().'.'.$photo->extension();
                    Storage::disk('local')->put($path, $photo->get());
                    $model->photo_path = $path;
                    $model->photo_original_name = $photo->getClientOriginalName();
                    $model->photo_mime = $photo->getMimeType();
                    $model->photo_size = $photo->getSize();
                }
                $model->save();
                AuditLogger::log('garden_inspection.finding_created', $actor, $model);

                return $model;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function resolve(GardenInspectionFinding $finding, User $actor): void
    {
        DB::transaction(function () use ($finding, $actor) {
            $finding = GardenInspectionFinding::query()->lockForUpdate()->findOrFail($finding->id);
            Gate::forUser($actor)->authorize('resolve', $finding);
            $finding->forceFill(['status' => GardenInspectionFindingStatus::Resolved, 'resolved_at' => now(), 'resolved_by' => $actor->id])->save();
            AuditLogger::log('garden_inspection.finding_resolved', $actor, $finding);
        });
    }

    public function finalize(GardenInspection $inspection, User $actor): void
    {
        $path = null;
        try {
            DB::transaction(function () use ($inspection, $actor, &$path): void {
                $inspection = GardenInspection::query()->lockForUpdate()->findOrFail($inspection->id);
                Gate::forUser($actor)->authorize('finalize', $inspection);
                if (! $inspection->findings()->exists()) {
                    throw ValidationException::withMessages(['finalize' => 'Vor dem Abschluss muss mindestens eine Feststellung erfasst sein.']);
                }

                $inspection->finalized_at = now();
                $inspection->finalized_by = $actor->id;
                $path = 'garden-inspections/'.Str::uuid().'.pdf';
                if (! Storage::disk('local')->put($path, $this->pdf->render($inspection))) {
                    throw ValidationException::withMessages(['finalize' => 'Das PDF-Protokoll konnte nicht gespeichert werden. Bitte Speicherplatz und Dateirechte prüfen lassen.']);
                }
                $inspection->forceFill([
                    'finalized_at' => now(),
                    'finalized_by' => $actor->id,
                    'pdf_path' => $path,
                ])->save();
                AuditLogger::log('garden_inspection.finalized', $actor, $inspection);
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }
}
