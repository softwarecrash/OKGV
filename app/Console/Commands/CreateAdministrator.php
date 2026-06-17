<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'okgv:create-admin {email?} {--name=} {--password=}';

    protected $description = 'Create the first OKGV administrator account';

    public function handle(): int
    {
        $email = $this->argument('email') ?: config('admin.email') ?: $this->ask('E-Mail-Adresse');
        $name = $this->option('name') ?: config('admin.name') ?: $this->ask('Name');
        $password = $this->option('password') ?: config('admin.password');
        $passwordConfirmation = $password ?: null;

        if ($password === null) {
            $password = $this->secret('Passwort');
            $passwordConfirmation = $this->secret('Passwort wiederholen');
        }

        $validator = Validator::make(
            compact('email', 'name', 'password', 'passwordConfirmation'),
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $created = ! $user->exists;

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'role' => $user->role ?? UserRole::Tenant,
            'is_system_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->info($created
            ? 'Administrator wurde erstellt.'
            : 'Administrator wurde aktualisiert.');

        return self::SUCCESS;
    }
}
