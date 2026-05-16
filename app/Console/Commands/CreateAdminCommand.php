<?php

declare(strict_types=1);


namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'drachme:create-admin
                            {--name= : Nom affiché}
                            {--email= : Adresse e-mail}
                            {--password= : Mot de passe (évite en production)}';

    /**
     * @var string
     */
    protected $description = 'Créer un compte administrateur (interactif)';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $plainPassword = $this->option('password');

        if ($name === null) {
            $name = text(
                label: 'Nom',
                default: 'Admin',
                required: true,
            );
        }

        if ($email === null) {
            $email = text(
                label: 'E-mail',
                required: true,
                validate: fn (string $value) => $this->validateField('email', $value),
            );
        } elseif (($message = $this->validateField('email', $email)) !== null) {
            $this->components->error($message);

            return self::FAILURE;
        }

        if ($plainPassword === null) {
            $plainPassword = promptPassword(
                label: 'Mot de passe',
                required: true,
                validate: fn (string $value) => $this->validateField('password', $value),
            );
        } elseif (($message = $this->validateField('password', $plainPassword)) !== null) {
            $this->components->error($message);

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error("Un utilisateur existe déjà avec l'e-mail {$email}.");

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'email_verified_at' => now(),
        ]);

        $this->components->info("Compte créé : {$user->email} (id {$user->id})");
        $this->line('Connexion : http://localhost:8080/login');

        return self::SUCCESS;
    }

    /**
     * @return string|null Error message for Prompts, null if valid.
     */
    private function validateField(string $field, string $value): ?string
    {
        $validator = Validator::make(
            [$field => $value],
            [
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', Password::default()],
            ],
        );

        if ($validator->fails()) {
            return $validator->errors()->first($field);
        }

        return null;
    }
}
