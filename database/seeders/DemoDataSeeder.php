<?php

namespace Database\Seeders;

use App\Services\DemoDataManager;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        app(DemoDataManager::class)->seed();
    }
}
