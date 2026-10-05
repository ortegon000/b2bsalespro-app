<?php

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->member = TeamMember::factory()->create();
    $this->actingAs($this->member->user);

    Stage::factory()->lost()->create(['name' => 'Perdido', 'slug' => 'lost', 'position' => 0]);
    $this->new = Stage::factory()->create(['name' => 'Nuevo', 'slug' => 'new', 'position' => 1]);
    $this->diagnosis = Stage::factory()->create(['name' => 'Diagnóstico', 'slug' => 'diagnosis', 'position' => 2]);
});

function companiesPage(string $csv)
{
    return Livewire::test('pages::crm.companies-import')->set('file', UploadedFile::fake()->createWithContent('empresas.csv', $csv));
}

function importCompanies(string $csv)
{
    return companiesPage($csv)->call('import');
}

const HEADER = "empresa,giro,sitio_web,etapa,responsable,origen,notas,contacto,email,telefono,puesto,principal\n";

test('the template downloads as a utf-8 csv with the expected columns', function () {
    $response = $this->get(route('crm.companies.template'));

    $response->assertOk()->assertDownload('plantilla-empresas.csv');

    expect($response->streamedContent())
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain('empresa,giro,sitio_web,etapa,responsable,origen,notas,contacto,email,telefono,puesto,principal')
        ->toContain('Distribuidora Norte');
});

test('only crm team members can open the import page or download the template', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('crm.companies.template'))->assertForbidden();
    $this->get(route('crm.companies.import'))->assertForbidden();
});

test('the downloaded template can be imported as is', function () {
    $template = $this->get(route('crm.companies.template'))->streamedContent();

    importCompanies($template)->assertRedirect(route('crm.pipeline'));

    $norte = Company::firstWhere('name', 'Distribuidora Norte');
    $prisma = Company::firstWhere('name', 'Seguros Prisma');

    expect(Company::count())->toBe(2)
        ->and($norte->contacts()->pluck('email')->all())->toEqualCanonicalizing(['rosa@distribuidoranorte.example', 'jorge@distribuidoranorte.example'])
        ->and($norte->contacts()->where('is_primary', true)->sole()->email)->toBe('rosa@distribuidoranorte.example')
        ->and($norte->stage_id)->toBe($this->new->id)
        ->and($norte->source)->toBe(LeadSource::Form)
        ->and($norte->industry)->toBe('Distribución')
        ->and($prisma->stage_id)->toBe($this->diagnosis->id)
        ->and($prisma->source)->toBe(LeadSource::Whatsapp)
        ->and($prisma->contacts()->count())->toBe(0);
});

test('the preview summarizes what will happen before anything is saved', function () {
    Company::factory()->for($this->new)->create(['name' => 'Ya Existe']);
    Contact::factory()->create(['email' => 'ajeno@otra.test']);
    [$companies, $contacts] = [Company::count(), Contact::count()];

    $csv = HEADER."Nueva Uno,,,,,,,Rosa,rosa@uno.test,,,\nNueva Uno,,,,,,,Luis,luis@uno.test,,,\nYa Existe,,,,,,,Ana,ana@existe.test,,,\nNueva Dos,,,,,,,Ajeno,ajeno@otra.test,,,\n";

    $page = companiesPage($csv);

    expect($page->instance()->summary)->toBe([
        'companies_create' => 1, 'companies_update' => 1, 'contacts_create' => 3, 'contacts_update' => 0, 'errors' => 1,
    ]);
    $page->assertSee('Ese email ya pertenece a otra empresa.');

    expect(Company::count())->toBe($companies)->and(Contact::count())->toBe($contacts);
});

test('companies are matched by name ignoring case, accents and extra spaces', function () {
    $existing = Company::factory()->for($this->new)->create(['name' => 'Constructora Meridiana', 'industry' => 'Construcción']);

    importCompanies(HEADER."  CONSTRUCTORA   meridiána ,,,,,,,Luis,luis@meridiana.test,,,\n");

    expect(Company::count())->toBe(1)
        ->and($existing->contacts()->pluck('email')->all())->toBe(['luis@meridiana.test'])
        ->and($existing->fresh()->industry)->toBe('Construcción');
});

test('an existing company only changes what the file fills in and moves when the stage differs', function () {
    $company = Company::factory()->for($this->new)->create([
        'name' => 'Acme', 'industry' => 'Retail', 'website' => 'https://acme.test', 'notes' => 'Nota vieja', 'stage_changed_at' => now()->subDays(20),
    ]);

    importCompanies(HEADER."Acme,,,Diagnóstico,,,Nota nueva,,,,,\n");

    $company->refresh();

    expect($company->industry)->toBe('Retail')
        ->and($company->website)->toBe('https://acme.test')
        ->and($company->notes)->toBe('Nota nueva')
        ->and($company->stage_id)->toBe($this->diagnosis->id)
        ->and($company->stage_changed_at->isToday())->toBeTrue();
});

test('an existing company stays in its stage when the stage cell is empty', function () {
    $changedAt = now()->subDays(20)->startOfSecond();
    $company = Company::factory()->for($this->diagnosis)->create(['name' => 'Acme', 'stage_changed_at' => $changedAt]);

    importCompanies(HEADER."Acme,Retail,,,,,,,,,,\n");

    expect($company->fresh()->stage_id)->toBe($this->diagnosis->id)
        ->and($company->fresh()->stage_changed_at->equalTo($changedAt))->toBeTrue()
        ->and($company->fresh()->industry)->toBe('Retail');
});

test('a new company defaults to the first open stage, the importer as owner and the import source', function () {
    importCompanies(HEADER."Acme,,,,,,,,,,,\n");

    $company = Company::sole();

    expect($company->stage_id)->toBe($this->new->id)
        ->and($company->owner_id)->toBe($this->member->user_id)
        ->and($company->source)->toBe(LeadSource::Import)
        ->and($company->stage_changed_at->isToday())->toBeTrue();
});

test('stage, owner and source are resolved by name, slug or email', function () {
    $owner = TeamMember::factory()->create();

    importCompanies(HEADER."Acme,,,diagnosis,{$owner->user->email},Sitio web,,,,,,\nBeta,,,DIAGNÓSTICO,,whatsapp,,,,,,\n");

    $acme = Company::firstWhere('name', 'Acme');
    $beta = Company::firstWhere('name', 'Beta');

    expect($acme->stage_id)->toBe($this->diagnosis->id)
        ->and($acme->owner_id)->toBe($owner->user_id)
        ->and($acme->source)->toBe(LeadSource::Website)
        ->and($beta->stage_id)->toBe($this->diagnosis->id)
        ->and($beta->source)->toBe(LeadSource::Whatsapp);
});

test('unknown stages, owners and sources reject their row', function (string $row, string $message) {
    $outsider = User::factory()->create(['email' => 'fuera@equipo.test']);

    $page = companiesPage(HEADER.str_replace('OUTSIDER', $outsider->email, $row));
    $page->assertSee($message)->call('import');

    expect(Company::count())->toBe(0);
})->with([
    'unknown stage' => ['Acme,,,Inexistente,,,,,,,,', 'La etapa "Inexistente" no existe.'],
    'owner outside the team' => ['Acme,,,,OUTSIDER,,,,,,,', 'no es del equipo del CRM'],
    'unknown source' => ['Acme,,,,,Telepatía,,,,,,', 'El origen "Telepatía" no es válido.'],
    'missing company name' => [',Retail,,,,,,,,,,', 'Falta el nombre de la empresa.'],
]);

test('rows of the same company are merged, with the first filled cell winning', function () {
    importCompanies(HEADER."Acme,,,Nuevo,,,,Rosa,rosa@acme.test,,,\nacme,Retail,,Diagnóstico,,,Una nota,Luis,luis@acme.test,,,\n");

    $company = Company::sole();

    expect($company->industry)->toBe('Retail')
        ->and($company->notes)->toBe('Una nota')
        ->and($company->stage_id)->toBe($this->new->id)
        ->and($company->contacts()->count())->toBe(2);
});

test('a row with a bad contact is skipped whole, but the company is created by its next valid row', function () {
    importCompanies(HEADER."Acme,Retail,,,,,,Rosa,no-es-correo,,,\nAcme,,,,,,,Luis,luis@acme.test,,,\n");

    $company = Company::sole();

    expect($company->contacts()->pluck('email')->all())->toBe(['luis@acme.test'])
        ->and($company->industry)->toBeNull();
});

test('a company whose only row is invalid is not created', function () {
    importCompanies(HEADER."Acme,Retail,,,,,,Rosa,no-es-correo,,,\n");

    expect(Company::count())->toBe(0);
});

test('contacts are validated: name and email go together, emails are not repeated or taken by another company', function (string $row, string $message) {
    Contact::factory()->create(['email' => 'ajeno@otra.test']);

    $page = companiesPage(HEADER."Acme,,,,,,,Primero,primero@acme.test,,,\n".$row);
    $page->assertSee($message);

    expect($page->instance()->summary['errors'])->toBe(1);
})->with([
    'name without email' => ["Acme,,,,,,,Solo Nombre,,,,\n", 'Falta el email del contacto.'],
    'email without name' => ["Acme,,,,,,,,solo@acme.test,,,\n", 'Falta el nombre del contacto.'],
    'repeated in the file' => ["Beta,,,,,,,Otro,PRIMERO@acme.test,,,\n", 'Email repetido en el archivo (fila 2).'],
    'taken by another company' => ["Beta,,,,,,,Ajeno,ajeno@otra.test,,,\n", 'Ese email ya pertenece a otra empresa.'],
]);

test('a contact without company is attached and an existing one of the same company is updated', function () {
    $company = Company::factory()->for($this->new)->create(['name' => 'Acme']);
    $own = Contact::factory()->for($company)->create(['email' => 'propio@acme.test', 'name' => 'Viejo', 'phone' => '111']);
    $orphan = Contact::factory()->create(['company_id' => null, 'email' => 'suelto@correo.test']);

    importCompanies(HEADER."Acme,,,,,,,Nuevo Nombre,propio@acme.test,,,\nAcme,,,,,,,Suelto,suelto@correo.test,,,\n");

    expect($own->fresh()->name)->toBe('Nuevo Nombre')
        ->and($own->fresh()->phone)->toBe('111')
        ->and($orphan->fresh()->company_id)->toBe($company->id);
});

test('the first contact of a new company is primary unless another one is marked', function () {
    importCompanies(HEADER."Acme,,,,,,,Rosa,rosa@acme.test,,,\nAcme,,,,,,,Luis,luis@acme.test,,,si\nBeta,,,,,,,Ana,ana@beta.test,,,\nBeta,,,,,,,Eva,eva@beta.test,,,\n");

    expect(Company::firstWhere('name', 'Acme')->contacts()->where('is_primary', true)->sole()->email)->toBe('luis@acme.test')
        ->and(Company::firstWhere('name', 'Beta')->contacts()->where('is_primary', true)->sole()->email)->toBe('ana@beta.test');
});

test('semicolon files with accented headers, blank lines and windows encoding are understood', function () {
    $csv = mb_convert_encoding("Empresa;Sitio Web;Etapa;Correo electrónico;Contacto\nMuebles Peña;https://pena.test;Diagnóstico;ana@pena.test;Ana Peña\n\n;;;;\n", 'Windows-1252', 'UTF-8');

    importCompanies($csv);

    $company = Company::sole();

    expect($company->name)->toBe('Muebles Peña')
        ->and($company->stage_id)->toBe($this->diagnosis->id)
        ->and($company->website)->toBe('https://pena.test')
        ->and($company->contacts()->sole()->name)->toBe('Ana Peña');
});

test('a file without the company column imports nothing', function () {
    companiesPage("giro,etapa\nRetail,Nuevo\n")->assertSee('al menos la columna "empresa"')->call('import')->assertHasErrors('file');

    expect(Company::count())->toBe(0);
});

test('files with more rows than allowed are rejected', function () {
    $rows = collect(range(1, 501))->map(fn ($i) => "Empresa {$i}")->implode("\n");

    companiesPage("empresa\n{$rows}\n")->call('import')->assertHasErrors('file');

    expect(Company::count())->toBe(0);
});

test('only csv files are accepted', function () {
    Livewire::test('pages::crm.companies-import')
        ->set('file', UploadedFile::fake()->create('empresas.pdf', 10, 'application/pdf'))
        ->assertHasErrors('file')
        ->assertSet('file', null);
});
