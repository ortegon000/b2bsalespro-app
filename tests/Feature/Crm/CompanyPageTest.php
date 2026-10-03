<?php

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\TeamMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->member = TeamMember::factory()->create();
    $this->actingAs($this->member->user);
    $this->company = Company::factory()->create();
});

function companyPage(Company $company)
{
    return Livewire::test('pages::crm.company', ['company' => $company]);
}

test('the first contact of a company becomes the primary one', function () {
    companyPage($this->company)
        ->set('contactName', 'Rosa Díaz')
        ->set('contactEmail', 'rosa@acme.test')
        ->call('saveContact')
        ->assertHasNoErrors();

    expect($this->company->contacts()->first()->is_primary)->toBeTrue();
});

test('marking another contact as primary removes the mark from the previous one', function () {
    $previous = Contact::factory()->for($this->company)->primary()->create();

    companyPage($this->company)
        ->set('contactName', 'Luis Barrera')
        ->set('contactEmail', 'luis@acme.test')
        ->set('contactIsPrimary', true)
        ->call('saveContact');

    expect($previous->fresh()->is_primary)->toBeFalse()
        ->and($this->company->contacts()->where('is_primary', true)->count())->toBe(1);
});

test('a contact email must be unique across the crm', function () {
    Contact::factory()->create(['email' => 'repetido@acme.test']);

    companyPage($this->company)
        ->set('contactName', 'Otra persona')
        ->set('contactEmail', 'repetido@acme.test')
        ->call('saveContact')
        ->assertHasErrors(['contactEmail' => 'unique']);
});

test('editing a contact can keep its own email', function () {
    $contact = Contact::factory()->for($this->company)->create(['email' => 'rosa@acme.test']);

    companyPage($this->company)
        ->call('editContact', $contact->id)
        ->assertSet('contactEmail', 'rosa@acme.test')
        ->set('contactJobTitle', 'Directora')
        ->call('saveContact')
        ->assertHasNoErrors();

    expect($contact->fresh()->job_title)->toBe('Directora');
});

test('contacts of another company cannot be edited or deleted from this page', function () {
    $foreign = Contact::factory()->create();

    expect(fn () => companyPage($this->company)->call('editContact', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
    expect(fn () => companyPage($this->company)->call('deleteContact', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
});

test('logging an activity records its author and type', function () {
    companyPage($this->company)
        ->set('activityType', ActivityType::Zoom->value)
        ->set('activityBody', 'Zoom de diagnóstico con el equipo.')
        ->call('addActivity')
        ->assertHasNoErrors();

    $activity = $this->company->activities()->first();

    expect($activity->type)->toBe(ActivityType::Zoom)
        ->and($activity->user_id)->toBe($this->member->user_id);
});

test('an activity needs a detail', function () {
    companyPage($this->company)->set('activityBody', '')->call('addActivity')->assertHasErrors('activityBody');
});

test('deleting a company keeps its contacts without a company', function () {
    $contact = Contact::factory()->for($this->company)->create();

    companyPage($this->company)->call('deleteCompany');

    expect(Company::count())->toBe(0)
        ->and($contact->fresh()->company_id)->toBeNull();
});
