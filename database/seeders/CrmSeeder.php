<?php

namespace Database\Seeders;

use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Enums\TeamRole;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    /**
     * Etapas del flujo comercial: atención, diagnóstico, propuesta y curso.
     *
     * @var list<array{slug: string, name: string, type: StageType}>
     */
    private const array STAGES = [
        ['slug' => 'new', 'name' => 'Nuevo', 'type' => StageType::Open],
        ['slug' => 'attended', 'name' => 'Atendido', 'type' => StageType::Open],
        ['slug' => 'diagnosis', 'name' => 'Diagnóstico', 'type' => StageType::Open],
        ['slug' => 'proposal-sent', 'name' => 'Propuesta enviada', 'type' => StageType::Open],
        ['slug' => 'accepted', 'name' => 'Aceptado', 'type' => StageType::Open],
        ['slug' => 'course-delivered', 'name' => 'Curso impartido', 'type' => StageType::Won],
        ['slug' => 'lost', 'name' => 'Perdido', 'type' => StageType::Lost],
    ];

    /**
     * Seed the CRM's default stages and make the app admins CRM admins.
     */
    public function run(): void
    {
        foreach (self::STAGES as $position => $stage) {
            Stage::updateOrCreate(
                ['slug' => $stage['slug']],
                ['name' => $stage['name'], 'type' => $stage['type'], 'position' => $position + 1],
            );
        }

        User::where('is_admin', true)->each(
            fn (User $user) => TeamMember::firstOrCreate(
                ['user_id' => $user->id],
                ['role' => TeamRole::Admin],
            ),
        );
    }
}
