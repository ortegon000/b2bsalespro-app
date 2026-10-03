<?php

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(TeamMember::factory()->create()->user);
});

test('dropping a company in another column changes its stage and resets its age', function () {
    $from = Stage::factory()->create();
    $to = Stage::factory()->create();
    $company = Company::factory()->for($from)->create(['stage_changed_at' => now()->subDays(10)]);

    Livewire::test('pages::crm.pipeline')->call('moveCompany', $company->id, 0, $to->id);

    expect($company->fresh()->stage_id)->toBe($to->id)
        ->and($company->fresh()->stage_changed_at->isToday())->toBeTrue();
});

test('dropping a company in its own column does not touch it', function () {
    $stage = Stage::factory()->create();
    $changedAt = now()->subDays(10)->startOfSecond();
    $company = Company::factory()->for($stage)->create(['stage_changed_at' => $changedAt]);

    Livewire::test('pages::crm.pipeline')->call('moveCompany', $company->id, 2, $stage->id);

    expect($company->fresh()->stage_changed_at->equalTo($changedAt))->toBeTrue();
});

test('leaving the lost stage clears the lost reason', function () {
    $lost = Stage::factory()->lost()->create();
    $open = Stage::factory()->create();
    $company = Company::factory()->for($lost)->create(['lost_reason' => 'Sin presupuesto']);

    Livewire::test('pages::crm.pipeline')->call('moveCompany', $company->id, 0, $open->id);

    expect($company->fresh()->lost_reason)->toBeNull();
});
