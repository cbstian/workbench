<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates a user from the console as the super admin of a new account (F-01, RN-01).
 * There is no open registration.
 */
class CreateSuperAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'workbench:super-admin
                            {--name= : Nombre del usuario}
                            {--email= : Correo del usuario}
                            {--password= : Contraseña (mínimo 8 caracteres)}
                            {--account= : Nombre de la cuenta (por defecto, el nombre del usuario)}';

    /**
     * @var string
     */
    protected $description = 'Crea un usuario Super admin con su propia cuenta';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? text('Nombre', required: true),
            'email' => $this->option('email') ?? text('Correo', required: true),
            'password' => $this->option('password') ?? password('Contraseña', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $validator->after(function ($validator) use ($data): void {
            if (User::withTrashed()->whereRaw('lower(email) = ?', [mb_strtolower((string) $data['email'])])->exists()) {
                $validator->errors()->add('email', 'Ya existe un usuario con ese correo.');
            }
        });

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            Account::openFor($user, $this->option('account'));

            return $user;
        });

        $this->components->info("Super admin {$user->email} creado. Ya puede iniciar sesión y crear sus espacios de trabajo.");

        return self::SUCCESS;
    }
}
