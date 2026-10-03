<?php

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;

beforeEach(function () {
    config(['crm.intake_token' => 'secret-token']);
    $this->first = Stage::factory()->create(['slug' => 'new', 'position' => 1]);
    Stage::factory()->create(['position' => 2]);

    $this->payload = [
        'source' => 'landing',
        'name' => 'Rosa Díaz',
        'email' => 'rosa@acme.test',
        'company' => 'Acme Ventas',
        'phone' => '55 1234 5678',
        'message' => 'Queremos capacitar a 12 vendedores.',
    ];
});

function postLead(array $payload, ?string $token = 'secret-token')
{
    return test()->postJson(
        '/api/crm/leads',
        $payload,
        $token ? ['Authorization' => "Bearer {$token}"] : [],
    );
}

test('requests without a valid token are rejected', function (?string $token) {
    postLead($this->payload, $token)->assertUnauthorized();

    expect(Company::count())->toBe(0);
})->with([
    'no token' => [null],
    'wrong token' => ['nope'],
]);

test('every request is rejected while no token is configured', function () {
    config(['crm.intake_token' => null]);

    postLead($this->payload, '')->assertUnauthorized();
});

test('a new lead creates a company in the first open stage with its primary contact and message', function () {
    postLead($this->payload)
        ->assertCreated()
        ->assertJsonPath('data.company', 'Acme Ventas')
        ->assertJsonPath('data.stage', 'new')
        ->assertJsonPath('duplicate', false);

    $company = Company::firstWhere('name', 'Acme Ventas');
    $contact = $company->contacts()->sole();
    $activity = $company->activities()->sole();

    expect($company->stage_id)->toBe($this->first->id)
        ->and($company->source)->toBe(LeadSource::Landing)
        ->and($contact->email)->toBe('rosa@acme.test')
        ->and($contact->is_primary)->toBeTrue()
        ->and($activity->type)->toBe(ActivityType::Message)
        ->and($activity->body)->toBe('Queremos capacitar a 12 vendedores.');
});

test('a lead without company name uses the person name', function () {
    postLead(['company' => null] + $this->payload)->assertCreated();

    expect(Company::where('name', 'Rosa Díaz')->exists())->toBeTrue();
});

test('an existing email does not duplicate anything and only adds the message', function () {
    postLead($this->payload)->assertCreated();

    postLead(['message' => 'Volvimos a escribir.'] + $this->payload)
        ->assertOk()
        ->assertJsonPath('duplicate', true);

    expect(Company::count())->toBe(1)
        ->and(Contact::count())->toBe(1)
        ->and(Company::first()->activities()->count())->toBe(2);
});

test('an existing contact without company gets a new company', function () {
    $contact = Contact::factory()->create(['company_id' => null, 'email' => 'rosa@acme.test']);

    postLead($this->payload)->assertOk()->assertJsonPath('duplicate', true);

    expect(Company::count())->toBe(1)
        ->and($contact->fresh()->company->name)->toBe('Acme Ventas');
});

test('invalid leads are rejected with validation errors', function (array $override, string $field) {
    postLead($override + $this->payload)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'missing email' => [['email' => null], 'email'],
    'malformed email' => [['email' => 'no-es-email'], 'email'],
    'missing name' => [['name' => null], 'name'],
    'internal source' => [['source' => 'import'], 'source'],
]);
