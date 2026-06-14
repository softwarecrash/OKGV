<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            'Es wurden keine Daten angelegt. Verwende für den löschbaren Testbestand: php artisan okgv:demo-seed',
        );
    }
}
