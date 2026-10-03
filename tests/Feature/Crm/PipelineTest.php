<?php

use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Database\Seeders\CrmSeeder;

test('guests are redirected to the login page', function () {
    $this->get(route('crm.pipeline'))->assertRedirect(route('login'));
});

test('users outside the crm team cannot see the pipeline', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('crm.pipeline'))->assertForbidden();
});

test('team members see companies grouped by stage in order', function () {
    $member = TeamMember::factory()->create();
    $first = Stage::factory()->create(['name' => 'Nuevo', 'position' => 1]);
    $second = Stage::factory()->create(['name' => 'Diagnóstico', 'position' => 2]);
    $company = Company::factory()->for($first)->create(['name' => 'Acme Ventas']);
    Contact::factory()->count(2)->for($company)->create();

    $this->actingAs($member->user);

    $this->get(route('crm.pipeline'))
        ->assertOk()
        ->assertSeeInOrder(['Nuevo', 'Acme Ventas', '2 contactos', 'Diagnóstico']);
});

test('the sidebar link to the crm is only shown to team members', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);
    $this->get(route('admin.feedback'))->assertDontSee(route('crm.pipeline'));

    TeamMember::factory()->for($admin)->create();
    $this->get(route('admin.feedback'))->assertSee(route('crm.pipeline'));
});

test('the seeder creates the default stages once and makes app admins crm admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $client = User::factory()->create(['is_admin' => false]);

    $this->seed(CrmSeeder::class);
    $this->seed(CrmSeeder::class);

    expect(Stage::count())->toBe(7)
        ->and(Stage::where('type', StageType::Won)->count())->toBe(1)
        ->and(Stage::orderBy('position')->first()->slug)->toBe('new')
        ->and(TeamMember::where('user_id', $admin->id)->count())->toBe(1)
        ->and(TeamMember::where('user_id', $client->id)->exists())->toBeFalse();
});
