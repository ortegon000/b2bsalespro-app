<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Da de alta la cuenta admin dueña del dashboard general.
 *
 * Misma contraseña aleatoria y desechable que el resto de los seeders de
 * usuarios (ObjecionCeroBetaUsersSeeder): se entra por el link de
 * "¿Olvidaste tu contraseña?", nunca por una contraseña conocida de
 * antemano en el código.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'ortegon000@gmail.com'],
            [
                'name' => 'Eduardo Ortega',
                'password' => Hash::make('P4ssw0rd!'),
                'email_verified_at' => now(),
            ],
        );

        $user->is_admin = true;
        $user->save();
    }
}
