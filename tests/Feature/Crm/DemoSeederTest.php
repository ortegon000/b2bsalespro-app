<?php

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Stage;
use Database\Seeders\CrmDemoSeeder;

test('the demo seeder fills every stage and can be run repeatedly without duplicates', function () {
    $this->seed(CrmDemoSeeder::class);
    $companies = Company::count();
    $this->seed(CrmDemoSeeder::class);

    expect(Company::count())->toBe($companies)
        ->and(Stage::whereDoesntHave('companies')->count())->toBe(0)
        ->and(Course::count())->toBe(1)
        ->and(Course::first()->contacts()->count())->toBe(3);
});

test('the demo seeder does nothing in production', function () {
    $this->app['env'] = 'production';

    (new CrmDemoSeeder)->run();

    expect(Company::count())->toBe(0);
});
