<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ParcelMapManager
{
    public function replaceBackground(
        ApplicationSetting $settings,
        UploadedFile $image,
        string $source,
        User $actor,
    ): ApplicationSetting {
        $dimensions = getimagesize($image->getRealPath());

        if ($dimensions === false) {
            throw new RuntimeException('The uploaded parcel map background is not a readable image.');
        }

        [$width, $height] = $dimensions;

        if ($width < 400 || $height < 300 || $width > 12000 || $height > 12000) {
            throw new RuntimeException('The parcel map background dimensions are outside the supported range.');
        }

        $newPath = $image->store('association/parcel-map', 'local');
        $oldPath = $settings->map_background_path;
        $oldWidth = max(1, (int) ($settings->map_background_width ?: 1200));
        $oldHeight = max(1, (int) ($settings->map_background_height ?: 800));

        try {
            DB::transaction(function () use (
                $settings,
                $image,
                $source,
                $actor,
                $newPath,
                $width,
                $height,
                $oldWidth,
                $oldHeight,
            ): void {
                if ($width !== $oldWidth || $height !== $oldHeight) {
                    Parcel::query()
                        ->whereNotNull('map_polygon')
                        ->each(function (Parcel $parcel) use ($width, $height, $oldWidth, $oldHeight): void {
                            $parcel->update([
                                'map_polygon' => collect($parcel->map_polygon)
                                    ->map(fn (array $point): array => [
                                        'x' => round(((float) $point['x'] / $oldWidth) * $width, 2),
                                        'y' => round(((float) $point['y'] / $oldHeight) * $height, 2),
                                    ])
                                    ->all(),
                            ]);
                        });
                }

                $settings->update([
                    'map_background_path' => $newPath,
                    'map_background_original_name' => $image->getClientOriginalName(),
                    'map_background_mime' => $image->getMimeType(),
                    'map_background_size' => $image->getSize(),
                    'map_background_width' => $width,
                    'map_background_height' => $height,
                    'map_background_source' => $source,
                ]);

                AuditLogger::log('parcel_map.background.updated', $actor, $settings, [
                    'width' => $width,
                    'height' => $height,
                    'source' => $source,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $settings->refresh();
    }

    /**
     * @param  list<array{x: int|float, y: int|float}>|null  $polygon
     */
    public function updatePolygon(
        Parcel $parcel,
        ?array $polygon,
        User $actor,
    ): Parcel {
        $before = $parcel->map_polygon;
        $parcel->update(['map_polygon' => $polygon]);

        AuditLogger::log('parcel_map.polygon.updated', $actor, $parcel, [
            'before' => $before,
            'point_count' => count($polygon ?? []),
        ]);

        return $parcel->refresh();
    }
}
