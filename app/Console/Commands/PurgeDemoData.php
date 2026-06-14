<?php

namespace App\Console\Commands;

use App\Services\DemoDataManager;
use Illuminate\Console\Command;

class PurgeDemoData extends Command
{
    protected $signature = 'okgv:demo-purge {--force : Demo-Daten ohne Rückfrage löschen}';

    protected $description = 'Remove only the marked OKGV demo dataset';

    public function handle(DemoDataManager $manager): int
    {
        if (! $this->option('force')
            && ! $this->confirm('Alle eindeutig markierten OKGV-Demo-Daten löschen?')) {
            return self::SUCCESS;
        }

        $counts = $manager->purge();
        $this->info(
            "Demo-Daten entfernt: {$counts['users']} Konten, {$counts['parcels']} Parzellen, "
            ."{$counts['meters']} Zähler und {$counts['periods']} Abrechnungsperioden.",
        );

        return self::SUCCESS;
    }
}
