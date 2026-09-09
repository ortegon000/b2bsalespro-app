<?php

use App\Domain\ObjecionCero\Models\Feedback;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.feedback'));
    $response->assertRedirect(route('login'));
});

test('non-admin users are redirected to objecion cero', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $response = $this->get(route('admin.feedback'));
    $response->assertRedirect(route('objecion-cero.inicio'));
});

test('admin users can see submitted feedback', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create(['name' => 'Guillermo']);

    Feedback::create([
        'user_id' => $author->id,
        'page' => 'objecion-cero.banco',
        'message' => 'Faltan fichas de la objeción de garantía.',
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('admin.feedback'));

    $response
        ->assertOk()
        ->assertSee('Guillermo')
        ->assertSee('Faltan fichas de la objeción de garantía.');
});
