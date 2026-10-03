<?php

namespace Database\Seeders;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Enums\TeamRole;
use App\Domain\Crm\Models\Activity;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración para ver el CRM completo: una empresa o más en cada
 * etapa, contactos, actividades y vendedores. Solo para entornos no productivos.
 * Es idempotente: se puede correr varias veces sin duplicar registros.
 */
class CrmDemoSeeder extends Seeder
{
    /**
     * @var list<array{name: string, email: string}>
     */
    private const array SELLERS = [
        ['name' => 'Laura Vendedora', 'email' => 'laura.demo@b2bsalespro.test'],
        ['name' => 'Marco Vendedor', 'email' => 'marco.demo@b2bsalespro.test'],
    ];

    /**
     * @var list<array{name: string, industry: string, source: LeadSource, stage: string, days: int, seller: int, lost_reason?: string, contacts: list<array{string, string, string}>, activities: list<array{ActivityType, string, int}>}>
     */
    private const array COMPANIES = [
        [
            'name' => 'Distribuidora Norte', 'industry' => 'Distribución', 'source' => LeadSource::Landing,
            'stage' => 'new', 'days' => 0, 'seller' => 0,
            'contacts' => [['Rosa Elena Díaz', 'rosa.diaz@distribuidoranorte.test', 'Gerente comercial']],
            'activities' => [],
        ],
        [
            'name' => 'Muebles Casa Bella', 'industry' => 'Retail', 'source' => LeadSource::Whatsapp,
            'stage' => 'new', 'days' => 1, 'seller' => 1,
            'contacts' => [['Andrés Quiroz', 'andres@casabella.test', 'Dueño']],
            'activities' => [[ActivityType::Message, 'Escribió por WhatsApp pidiendo información del curso.', 1]],
        ],
        [
            'name' => 'Grupo Aldama', 'industry' => 'Manufactura', 'source' => LeadSource::Form,
            'stage' => 'attended', 'days' => 3, 'seller' => 0,
            'contacts' => [
                ['Patricia Aldama', 'patricia@grupoaldama.test', 'Directora general'],
                ['Jorge Mena', 'jorge.mena@grupoaldama.test', 'Jefe de ventas'],
            ],
            'activities' => [
                [ActivityType::Call, 'Llamada de 20 min. Quieren capacitar a 12 vendedores.', 4],
                [ActivityType::Message, 'Se envió resumen y se agendó Zoom.', 3],
            ],
        ],
        [
            'name' => 'Tecno Soluciones MX', 'industry' => 'Tecnología', 'source' => LeadSource::Website,
            'stage' => 'attended', 'days' => 5, 'seller' => 1,
            'contacts' => [['Daniel Ortiz', 'daniel@tecnosoluciones.test', 'Director comercial']],
            'activities' => [[ActivityType::Zoom, 'Zoom de presentación. Pidió caso de éxito.', 5]],
        ],
        [
            'name' => 'Seguros Prisma', 'industry' => 'Servicios financieros', 'source' => LeadSource::Landing,
            'stage' => 'diagnosis', 'days' => 6, 'seller' => 0,
            'contacts' => [
                ['Mariana Lozano', 'mariana@segurosprisma.test', 'Gerente de capacitación'],
                ['Hugo Ramírez', 'hugo@segurosprisma.test', 'Vendedor'],
                ['Ivette Soto', 'ivette@segurosprisma.test', 'Vendedora'],
            ],
            'activities' => [
                [ActivityType::Call, 'Primera llamada de descubrimiento.', 12],
                [ActivityType::Diagnosis, 'Encuesta en vivo con 3 vendedores. Principal dolor: objeción de precio.', 6],
            ],
        ],
        [
            'name' => 'Constructora Meridiana', 'industry' => 'Construcción', 'source' => LeadSource::Import,
            'stage' => 'diagnosis', 'days' => 9, 'seller' => 1,
            'contacts' => [['Luis Barrera', 'luis@meridiana.test', 'Gerente de ventas']],
            'activities' => [[ActivityType::Diagnosis, 'Diagnóstico privado con 2 vendedores.', 9]],
        ],
        [
            'name' => 'AutoPartes del Bajío', 'industry' => 'Automotriz', 'source' => LeadSource::Form,
            'stage' => 'proposal-sent', 'days' => 8, 'seller' => 0,
            'contacts' => [
                ['Fernando Cruz', 'fernando@autopartesbajio.test', 'Director'],
                ['Sandra Vela', 'sandra@autopartesbajio.test', 'Vendedora'],
            ],
            'activities' => [
                [ActivityType::Diagnosis, 'Diagnóstico en vivo con todo el equipo.', 14],
                [ActivityType::Proposal, 'Propuesta económica con temario de 16 horas enviada por correo.', 8],
            ],
        ],
        [
            'name' => 'Clínica Santa Aurora', 'industry' => 'Salud', 'source' => LeadSource::Whatsapp,
            'stage' => 'proposal-sent', 'days' => 15, 'seller' => 1,
            'contacts' => [['Dra. Verónica Paz', 'veronica@santaaurora.test', 'Directora administrativa']],
            'activities' => [[ActivityType::Proposal, 'Propuesta enviada, sin respuesta. Dar seguimiento.', 15]],
        ],
        [
            'name' => 'Inmobiliaria Horizonte', 'industry' => 'Inmobiliaria', 'source' => LeadSource::Landing,
            'stage' => 'accepted', 'days' => 2, 'seller' => 0,
            'contacts' => [
                ['Claudia Ríos', 'claudia@inmohorizonte.test', 'Directora comercial'],
                ['Tomás Guerra', 'tomas@inmohorizonte.test', 'Asesor'],
                ['Brenda Salas', 'brenda@inmohorizonte.test', 'Asesora'],
                ['Omar Pineda', 'omar@inmohorizonte.test', 'Asesor'],
            ],
            'activities' => [
                [ActivityType::Proposal, 'Propuesta presentada en reunión.', 10],
                [ActivityType::Call, 'Aceptaron. Curso presencial de 2 días a coordinar.', 2],
            ],
        ],
        [
            'name' => 'Alimentos La Cosecha', 'industry' => 'Alimentos', 'source' => LeadSource::Website,
            'stage' => 'course-delivered', 'days' => 20, 'seller' => 1,
            'contacts' => [
                ['Elena Márquez', 'elena@lacosecha.test', 'Gerente de ventas'],
                ['Ricardo Núñez', 'ricardo@lacosecha.test', 'Vendedor'],
                ['Gabriela Torres', 'gabriela@lacosecha.test', 'Vendedora'],
            ],
            'activities' => [
                [ActivityType::Proposal, 'Propuesta aceptada.', 40],
                [ActivityType::Note, 'Curso online de 12 horas impartido. Refuerzo de 30 días pendiente de activar.', 20],
            ],
        ],
        [
            'name' => 'Logística Express', 'industry' => 'Logística', 'source' => LeadSource::Form,
            'stage' => 'lost', 'days' => 25, 'seller' => 0, 'lost_reason' => 'Presupuesto congelado este trimestre',
            'contacts' => [['Víctor Salgado', 'victor@logisticaexpress.test', 'Gerente']],
            'activities' => [[ActivityType::Call, 'Indicó que el presupuesto de capacitación se congeló.', 25]],
        ],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->call(CrmSeeder::class);

        $sellers = collect(self::SELLERS)->map(function (array $data): User {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password'), 'email_verified_at' => now()],
            );
            TeamMember::firstOrCreate(['user_id' => $user->id], ['role' => TeamRole::Seller]);

            return $user;
        })->values();

        $stages = Stage::pluck('id', 'slug');

        foreach (self::COMPANIES as $data) {
            $company = Company::updateOrCreate(['name' => $data['name']], [
                'industry' => $data['industry'],
                'source' => $data['source'],
                'stage_id' => $stages[$data['stage']],
                'owner_id' => $sellers[$data['seller']]->id,
                'lost_reason' => $data['lost_reason'] ?? null,
                'stage_changed_at' => now()->subDays($data['days']),
            ]);

            foreach ($data['contacts'] as $index => [$name, $email, $jobTitle]) {
                Contact::updateOrCreate(['email' => $email], [
                    'company_id' => $company->id,
                    'name' => $name,
                    'job_title' => $jobTitle,
                    'phone' => '55 5'.fake()->numerify('### ####'),
                    'is_primary' => $index === 0,
                ]);
            }

            $company->activities()->delete();

            foreach ($data['activities'] as [$type, $body, $daysAgo]) {
                Activity::create([
                    'company_id' => $company->id,
                    'user_id' => $sellers[$data['seller']]->id,
                    'type' => $type,
                    'body' => $body,
                    'occurred_at' => now()->subDays($daysAgo),
                ]);
            }
        }

        Contact::updateOrCreate(['email' => 'sofia.freelance@correo.test'], [
            'company_id' => null,
            'name' => 'Sofía Herrera',
            'job_title' => 'Consultora independiente',
            'is_primary' => false,
        ]);
    }
}
