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
    protected $signature = 'okgv:create-admin {email?} {--name=}';

    protected $description = 'Create the first OKGV administrator account';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('E-Mail-Adresse');
        $name = $this->option('name') ?: $this->ask('Name');
        $password = $this->secret('Passwort');
        $passwordConfirmation = $this->secret('Passwort wiederholen');

        $validator = Validator::make(
            compact('email', 'name', 'password', 'passwordConfirmation'),
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
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

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Administrator,
            'email_verified_at' => now(),
        ]);

        $this->info('Administrator wurde erstellt.');

        return self::SUCCESS;
    }
}
