<?php

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->member = TeamMember::factory()->create();
    $this->actingAs($this->member->user);
});

test('a new company starts in the first open stage assigned to the current user', function () {
    Stage::factory()->lost()->create(['position' => 1]);
    $first = Stage::factory()->create(['position' => 2]);
    Stage::factory()->create(['position' => 3]);

    Livewire::test('pages::crm.company-form')
        ->assertSet('stageId', $first->id)
        ->assertSet('ownerId', $this->member->user_id)
        ->set('name', 'Acme Ventas')
        ->set('source', LeadSource::Whatsapp->value)
        ->call('save')
        ->assertHasNoErrors();

    $company = Company::firstWhere('name', 'Acme Ventas');

    expect($company->stage_id)->toBe($first->id)
        ->and($company->source)->toBe(LeadSource::Whatsapp);
});

test('the company name is required', function () {
    Stage::factory()->create();

    Livewire::test('pages::crm.company-form')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('the owner must be a crm team member', function () {
    Stage::factory()->create();
    $outsider = User::factory()->create();

    Livewire::test('pages::crm.company-form')
        ->set('name', 'Acme')
        ->set('ownerId', $outsider->id)
        ->call('save')
        ->assertHasErrors('ownerId');
});

test('editing a company into the lost stage keeps the reason, and leaving drops it', function () {
    $open = Stage::factory()->create();
    $lost = Stage::factory()->lost()->create();
    $company = Company::factory()->for($open)->create();

    Livewire::test('pages::crm.company-form', ['company' => $company])
        ->set('stageId', $lost->id)
        ->set('lostReason', 'Eligió a otro proveedor')
        ->call('save');

    expect($company->fresh()->stage_id)->toBe($lost->id)
        ->and($company->fresh()->lost_reason)->toBe('Eligió a otro proveedor');

    Livewire::test('pages::crm.company-form', ['company' => $company->fresh()])
        ->set('stageId', $open->id)
        ->call('save');

    expect($company->fresh()->lost_reason)->toBeNull();
});
