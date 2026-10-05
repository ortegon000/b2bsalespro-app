<?php

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->member = TeamMember::factory()->create();
    $this->actingAs($this->member->user);
    $this->company = Company::factory()->create();
});

function csvFile(string $content, string $name = 'contactos.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function importPage(Company $company, string $content)
{
    return Livewire::test('pages::crm.contacts-import', ['company' => $company])->set('file', csvFile($content));
}

test('the template downloads as a utf-8 csv with the expected columns', function () {
    $response = $this->get(route('crm.contacts.template'));

    $response->assertOk()->assertDownload('plantilla-contactos.csv');

    $content = $response->streamedContent();

    expect($content)->toStartWith("\xEF\xBB\xBF")
        ->and($content)->toContain('nombre,email,telefono,puesto,principal')
        ->and($content)->toContain('María López');
});

test('only crm team members can download the template or open the import page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('crm.contacts.template'))->assertForbidden();
    $this->get(route('crm.companies.contacts.import', $this->company))->assertForbidden();
});

test('the downloaded template can be imported as is', function () {
    $template = $this->get(route('crm.contacts.template'))->streamedContent();

    importPage($this->company, $template)->call('import');

    expect($this->company->contacts()->pluck('email')->all())
        ->toEqualCanonicalizing(['maria.lopez@empresa.com', 'carlos.perez@empresa.com'])
        ->and($this->company->contacts()->where('is_primary', true)->sole()->email)->toBe('carlos.perez@empresa.com');
});

test('the preview classifies rows before anything is saved', function () {
    $other = Contact::factory()->create(['email' => 'ajeno@otra.test']);
    Contact::factory()->for($this->company)->create(['email' => 'propio@acme.test']);

    $csv = "nombre,email\nNuevo Uno,nuevo@acme.test\nPropio,propio@acme.test\nAjeno,ajeno@otra.test\nSin correo,no-es-correo\n,sin-nombre@acme.test\nNuevo Uno,NUEVO@acme.test\n";

    $component = importPage($this->company, $csv);

    expect($component->instance()->counts)->toBe(['create' => 1, 'update' => 1, 'error' => 4]);
    $component->assertSee('Ese email ya pertenece a otra empresa.')
        ->assertSee('El email no es válido.')
        ->assertSee('Falta el nombre.')
        ->assertSee('Email repetido en el archivo (fila 2).');

    expect(Contact::count())->toBe(2)
        ->and($other->fresh()->company_id)->not->toBe($this->company->id);
});

test('importing creates new contacts, updates its own and skips the invalid rows', function () {
    $own = Contact::factory()->for($this->company)->create(['email' => 'propio@acme.test', 'name' => 'Nombre viejo', 'phone' => '111', 'job_title' => 'Jefe']);
    Contact::factory()->create(['email' => 'ajeno@otra.test']);

    importPage($this->company, "nombre,email,telefono\nNuevo,nuevo@acme.test,222\nNombre nuevo,propio@acme.test,\nAjeno,ajeno@otra.test,333\nMal,mal\n")
        ->call('import')
        ->assertRedirect(route('crm.companies.show', $this->company));

    expect($this->company->contacts()->count())->toBe(2)
        ->and($own->fresh()->name)->toBe('Nombre nuevo')
        ->and($own->fresh()->phone)->toBe('111')
        ->and($own->fresh()->job_title)->toBe('Jefe')
        ->and(Contact::firstWhere('email', 'nuevo@acme.test')->phone)->toBe('222');
});

test('a contact without company is attached to this one', function () {
    $orphan = Contact::factory()->create(['company_id' => null, 'email' => 'suelto@correo.test']);

    importPage($this->company, "nombre,email\nSuelto,suelto@correo.test\n")->call('import');

    expect($orphan->fresh()->company_id)->toBe($this->company->id);
});

test('semicolon files, accented headers and windows encoding are understood', function () {
    $csv = mb_convert_encoding("Nombre;Correo electrónico;Teléfono;Cargo;Principal\nRosa Díaz;rosa@acme.test;555;Gerente;Sí\n", 'Windows-1252', 'UTF-8');

    importPage($this->company, $csv)->call('import');

    $contact = $this->company->contacts()->sole();

    expect($contact->name)->toBe('Rosa Díaz')
        ->and($contact->phone)->toBe('555')
        ->and($contact->job_title)->toBe('Gerente')
        ->and($contact->is_primary)->toBeTrue();
});

test('an import with a blank primary column keeps the current primary contact', function () {
    $primary = Contact::factory()->for($this->company)->primary()->create(['email' => 'jefe@acme.test']);

    importPage($this->company, "nombre,email,principal\nJefe actualizado,jefe@acme.test,\nOtro,otro@acme.test,\n")->call('import');

    expect($primary->fresh()->is_primary)->toBeTrue()
        ->and($primary->fresh()->name)->toBe('Jefe actualizado')
        ->and($this->company->contacts()->where('is_primary', true)->count())->toBe(1);
});

test('a file without the required columns imports nothing', function () {
    $component = importPage($this->company, "telefono,puesto\n555,Gerente\n");

    $component->assertSee('al menos las columnas')->call('import')->assertHasErrors('file');

    expect(Contact::count())->toBe(0);
});

test('files with more rows than allowed are rejected', function () {
    $rows = collect(range(1, 501))->map(fn ($i) => "Persona {$i},persona{$i}@acme.test")->implode("\n");

    importPage($this->company, "nombre,email\n{$rows}\n")->call('import')->assertHasErrors('file');

    expect(Contact::count())->toBe(0);
});

test('only csv files are accepted', function () {
    Livewire::test('pages::crm.contacts-import', ['company' => $this->company])
        ->set('file', UploadedFile::fake()->create('contactos.pdf', 10, 'application/pdf'))
        ->assertHasErrors('file')
        ->assertSet('file', null);
});

test('importing contacts from a course page also enrolls them', function () {
    $course = Course::factory()->create();
    $csv = UploadedFile::fake()->createWithContent('contactos.csv', "nombre,email\nRosa Díaz,rosa@acme.test\nLuis Soto,luis@acme.test\n");

    Livewire::test('pages::crm.contacts-import', ['company' => $course->company, 'courseId' => $course->id])
        ->set('file', $csv)
        ->call('import')
        ->assertRedirect(route('crm.courses.show', $course));

    expect($course->contacts()->pluck('email')->all())->toEqualCanonicalizing(['rosa@acme.test', 'luis@acme.test']);
});

test('the enrollment on import can be turned off and a foreign course is ignored', function () {
    $course = Course::factory()->create();
    $csv = fn () => UploadedFile::fake()->createWithContent('contactos.csv', "nombre,email\nRosa Díaz,rosa@acme.test\n");

    Livewire::test('pages::crm.contacts-import', ['company' => $course->company, 'courseId' => $course->id])
        ->set('enrollInCourse', false)
        ->set('file', $csv())
        ->call('import');

    expect($course->contacts()->count())->toBe(0);

    $foreign = Course::factory()->create();

    Livewire::test('pages::crm.contacts-import', ['company' => Company::factory()->create(), 'courseId' => $foreign->id])
        ->set('file', $csv())
        ->call('import')
        ->assertRedirect();

    expect($foreign->contacts()->count())->toBe(0);
});
