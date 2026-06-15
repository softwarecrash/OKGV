<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

final class BackupManager
{
    private const FORMAT = 'okgv-backup-v1';

    private const PRIVATE_DIRECTORIES = [
        'documents',
        'meter-reading-submissions',
        'work-hour-submissions',
    ];

    public function __construct(
        private readonly DatabaseDumpService $database,
    ) {}

    /**
     * @return array{name: string, size: int, modified_at: int}
     */
    public function create(User $actor, string $reason = 'manual'): array
    {
        $workDirectory = $this->workDirectory();
        try {
            $databasePath = "{$workDirectory}/database.sql";
            $this->database->dump($databasePath);
            Storage::disk('local')->makeDirectory('backups');
            $name = sprintf('okgv-backup-%s-%s.zip', now()->format('Ymd-His'), Str::lower(Str::random(6)));
            $archivePath = Storage::disk('local')->path("backups/{$name}");
            $zip = new ZipArchive;

            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Die Backup-Datei konnte nicht angelegt werden.');
            }

            $zip->addFile($databasePath, 'database.sql');
            $checksums = ['database.sql' => hash_file('sha256', $databasePath)];
            $privateRoot = Storage::disk('local')->path('');

            foreach (self::PRIVATE_DIRECTORIES as $directory) {
                $source = "{$privateRoot}/{$directory}";

                if (! is_dir($source)) {
                    continue;
                }

                foreach (File::allFiles($source) as $file) {
                    $relative = $directory.'/'.str_replace('\\', '/', $file->getRelativePathname());
                    $archiveName = "files/{$relative}";
                    $zip->addFile($file->getPathname(), $archiveName);
                    $checksums[$archiveName] = hash_file('sha256', $file->getPathname());
                }
            }

            $manifest = [
                'format' => self::FORMAT,
                'version' => trim((string) file_get_contents(base_path('VERSION'))),
                'created_at' => now()->toIso8601String(),
                'reason' => $reason,
                'private_directories' => self::PRIVATE_DIRECTORIES,
                'checksums' => $checksums,
            ];
            $zip->addFromString(
                'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            );
            $zip->close();
            chmod($archivePath, 0600);

            AuditLogger::log('backup.created', $actor, metadata: [
                'filename' => $name,
                'reason' => $reason,
                'size' => filesize($archivePath),
            ]);

            return $this->metadata($name);
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    /**
     * @return list<array{name: string, size: int, modified_at: int}>
     */
    public function all(): array
    {
        return collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.zip'))
            ->map(fn (string $path): array => $this->metadata(basename($path)))
            ->sortByDesc('modified_at')
            ->values()
            ->all();
    }

    public function path(string $name): string
    {
        $this->ensureSafeName($name);
        $path = "backups/{$name}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->path($path);
    }

    public function delete(string $name, User $actor): void
    {
        $this->ensureSafeName($name);
        abort_unless(Storage::disk('local')->delete("backups/{$name}"), 404);
        AuditLogger::log('backup.deleted', $actor, metadata: ['filename' => $name]);
    }

    public function restore(UploadedFile $upload, User $actor): string
    {
        $workDirectory = $this->workDirectory();
        try {
            $archivePath = "{$workDirectory}/restore.zip";
            File::copy($upload->getRealPath(), $archivePath);
            $zip = new ZipArchive;

            if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
                throw ValidationException::withMessages(['backup' => 'Die Datei ist kein lesbares ZIP-Archiv.']);
            }

            try {
                $manifest = $this->validateArchive($zip);
                $zip->extractTo("{$workDirectory}/extracted");
            } finally {
                $zip->close();
            }

            $this->create($actor, 'pre-restore');
            $databasePath = "{$workDirectory}/extracted/database.sql";
            $this->database->restore($databasePath);
            $this->restorePrivateFiles("{$workDirectory}/extracted/files", $manifest['private_directories']);

            $restoredActor = User::query()->find($actor->id);
            AuditLogger::log('backup.restored', $restoredActor, metadata: [
                'source_name' => $upload->getClientOriginalName(),
                'backup_version' => $manifest['version'],
            ]);

            return $manifest['created_at'];
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArchive(ZipArchive $zip): array
    {
        $uncompressedSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            $uncompressedSize += (int) ($stat['size'] ?? 0);
            $operatingSystem = 0;
            $attributes = 0;
            $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
            $fileType = ($attributes >> 16) & 0170000;

            if ($name === false
                || str_contains($name, "\0")
                || str_starts_with($name, '/')
                || in_array('..', explode('/', $name), true)
                || $fileType === 0120000) {
                throw ValidationException::withMessages([
                    'backup' => 'Das Backup enthält einen unzulässigen Dateipfad.',
                ]);
            }
        }

        if ($uncompressedSize > 2 * 1024 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'backup' => 'Das entpackte Backup überschreitet die zulässige Größe von 2 GiB.',
            ]);
        }

        $manifestJson = $zip->getFromName('manifest.json');
        $manifest = is_string($manifestJson) ? json_decode($manifestJson, true) : null;

        if (! is_array($manifest)
            || ($manifest['format'] ?? null) !== self::FORMAT
            || ! isset($manifest['version'], $manifest['created_at'], $manifest['checksums'])) {
            throw ValidationException::withMessages([
                'backup' => 'Die Datei ist kein gültiges OKGV-Backup.',
            ]);
        }

        $currentVersion = trim((string) file_get_contents(base_path('VERSION')));

        if ($manifest['version'] !== $currentVersion) {
            throw ValidationException::withMessages([
                'backup' => "Das Backup gehört zu Version {$manifest['version']}. Für diesen sicheren Restore ist Version {$currentVersion} erforderlich.",
            ]);
        }

        foreach ($manifest['checksums'] as $name => $expected) {
            $contents = $zip->getFromName($name);

            if (! is_string($contents) || ! hash_equals($expected, hash('sha256', $contents))) {
                throw ValidationException::withMessages([
                    'backup' => "Die Prüfsumme von {$name} ist ungültig.",
                ]);
            }
        }

        $allowedFiles = [
            'manifest.json',
            ...array_keys($manifest['checksums']),
        ];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name !== false && ! str_ends_with($name, '/') && ! in_array($name, $allowedFiles, true)) {
                throw ValidationException::withMessages([
                    'backup' => "Das Backup enthält die nicht registrierte Datei {$name}.",
                ]);
            }
        }

        $manifest['private_directories'] = array_values(array_intersect(
            $manifest['private_directories'] ?? [],
            self::PRIVATE_DIRECTORIES,
        ));

        return $manifest;
    }

    /**
     * @param  list<string>  $directories
     */
    private function restorePrivateFiles(string $sourceRoot, array $directories): void
    {
        $targetRoot = Storage::disk('local')->path('');

        foreach ($directories as $directory) {
            File::deleteDirectory("{$targetRoot}/{$directory}");
            $source = "{$sourceRoot}/{$directory}";

            if (is_dir($source)) {
                File::copyDirectory($source, "{$targetRoot}/{$directory}");
            }
        }
    }

    private function workDirectory(): string
    {
        $path = storage_path('app/backup-work/'.Str::uuid());
        File::ensureDirectoryExists($path, 0700, true);

        return $path;
    }

    /**
     * @return array{name: string, size: int, modified_at: int}
     */
    private function metadata(string $name): array
    {
        $path = "backups/{$name}";

        return [
            'name' => $name,
            'size' => Storage::disk('local')->size($path),
            'modified_at' => Storage::disk('local')->lastModified($path),
        ];
    }

    private function ensureSafeName(string $name): void
    {
        abort_unless(
            preg_match('/\Aokgv-backup-\d{8}-\d{6}-[a-z0-9]{6}\.zip\z/', $name) === 1,
            404,
        );
    }
}
