<?php

namespace App\Console\Commands;

use App\Services\DemoDataManager;
use Illuminate\Console\Command;
use Throwable;

class SeedDemoData extends Command
{
    protected $signature = 'okgv:demo-seed {--force : Demo-Daten ohne Rückfrage neu anlegen}';

    protected $description = 'Create the removable OKGV demo dataset';

    public function handle(DemoDataManager $manager): int
    {
        if (! $this->option('force')
            && ! $this->confirm('Vorhandene OKGV-Demo-Daten werden ersetzt. Fortfahren?')) {
            return self::SUCCESS;
        }

        try {
            $counts = $manager->seed();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            "Demo-Daten angelegt: {$counts['users']} Konten, {$counts['parcels']} Parzellen, "
            ."{$counts['meters']} Zähler und {$counts['periods']} Abrechnungsperioden.",
        );

        return self::SUCCESS;
    }
}
