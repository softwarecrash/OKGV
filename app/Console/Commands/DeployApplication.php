<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployApplication extends Command
{
    protected $signature = 'okgv:deploy
        {--skip-clear : Vorherige Laravel-Caches nicht leeren}
        {--skip-admin : Administrator aus OKGV_ADMIN_* nicht anlegen oder aktualisieren}
        {--skip-optimize : Laravel-Caches am Ende nicht neu aufbauen}
        {--demo-purge : Löschbare Demo-Daten vor dem Deployment entfernen}
        {--demo-seed : Löschbare Demo-Daten nach dem Deployment neu anlegen}';

    protected $description = 'Run the safe OKGV deployment steps for Plesk and webhosting.';

    public function handle(): int
    {
        if (! $this->option('skip-clear')) {
            if ($this->call('optimize:clear') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if ($this->option('demo-purge')) {
            if ($this->call('okgv:demo-purge', ['--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('skip-admin')) {
            if (config('admin.email') && config('admin.password')) {
                if ($this->call('okgv:create-admin') !== self::SUCCESS) {
                    return self::FAILURE;
                }
            } else {
                $this->warn('OKGV_ADMIN_EMAIL oder OKGV_ADMIN_PASSWORD fehlt. Administrator-Bootstrap wurde übersprungen.');
            }
        }

        if ($this->option('demo-seed')) {
            if ($this->call('okgv:demo-seed', ['--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if (! $this->option('skip-optimize')) {
            if ($this->call('optimize') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $this->info('OKGV-Bereitstellung abgeschlossen.');

        return self::SUCCESS;
    }
}
