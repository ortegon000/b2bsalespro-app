<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Da de alta a los primeros invitados de la beta gratuita de Objeción Cero.
 *
 * Reemplazar los correos dummy por los reales antes de invitar. La contraseña
 * es aleatoria y desechable a propósito: el invitado nunca la usa, entra por
 * el link de "¿Olvidaste tu contraseña?" (mismo flujo documentado en
 * docs/beta-onboarding.md).
 */
class ObjecionCeroBetaUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invitados = [
            ['name' => 'Guillermo Colosia', 'email' => 'guillermo@b2bsalespro.mx'],
            ['name' => 'Rodrigo Ayala', 'email' => 'rodrigo@b2bsalespro.mx'],
        ];

        foreach ($invitados as $invitado) {
            User::updateOrCreate(
                ['email' => $invitado['email']],
                [
                    'name' => $invitado['name'],
                    'password' => Hash::make('P4ssw0rd!'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
