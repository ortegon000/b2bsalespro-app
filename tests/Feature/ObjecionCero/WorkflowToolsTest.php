<?php

use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Ficha;
use App\Domain\ObjecionCero\Models\PlantillaPaso;
use App\Domain\ObjecionCero\Models\PlantillaRespuesta;
use App\Domain\ObjecionCero\Models\WhatsappScript;
use App\Models\User;
use Livewire\Livewire;

it('offers copy actions for whatsapp conversations and ficha dialogues', function () {
    WhatsappScript::create([
        'title' => 'Seguimiento',
        'messages' => [['who' => 't', 't' => 'Confirmemos la reunión']],
    ]);

    $categoria = Categoria::create([
        'slug' => 'precio',
        'label' => 'Precio',
    ]);

    Ficha::create([
        'number' => 1,
        'category_id' => $categoria->id,
        'type' => 'real',
        'objection' => 'Está muy caro',
        'confirm' => 'Confirmar',
        'meaning' => 'Falta demostrar el valor',
        'dialogue' => [['who' => 't', 't' => 'Comparemos el resultado']],
    ]);

    Livewire::test('pages::objecion-cero.whatsapp')
        ->assertSee('Copiar conversación')
        ->assertSee('Tú: Confirmemos la reunión');

    Livewire::test('pages::objecion-cero.banco-fichas')
        ->call('open', 1)
        ->assertSee('Copiar diálogo')
        ->assertSee('Tú: Comparemos el resultado');
});

it('offers copy and pdf export actions for the personal template', function () {
    $user = User::factory()->create();

    PlantillaPaso::create([
        'title' => '1 · Identidad comercial',
        'fields' => [['label' => 'Mi producto', 'ej' => 'Consultoría']],
    ]);

    $this->actingAs($user);

    Livewire::test('pages::objecion-cero.plantilla')
        ->assertSee('Copiar resumen')
        ->assertSee('Imprimir / guardar PDF');
});

it('requires authentication to export the personal template', function () {
    $this->get(route('objecion-cero.plantilla.exportar'))
        ->assertRedirect(route('login'));
});

it('exports only the authenticated users template answers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $step = PlantillaPaso::create([
        'title' => '1 · Identidad comercial',
        'fields' => [
            ['label' => 'Mi producto', 'ej' => 'Consultoría'],
            ['label' => 'Campo todavía vacío', 'ej' => 'Ejemplo'],
        ],
    ]);

    PlantillaRespuesta::create([
        'user_id' => $user->id,
        'template_step_id' => $step->id,
        'field_index' => 0,
        'value' => 'Automatización comercial para empresas B2B',
    ]);

    PlantillaRespuesta::create([
        'user_id' => $otherUser->id,
        'template_step_id' => $step->id,
        'field_index' => 0,
        'value' => 'Respuesta privada de otra persona',
    ]);

    $this->actingAs($user)
        ->get(route('objecion-cero.plantilla.exportar'))
        ->assertSee('Automatización comercial para empresas B2B')
        ->assertSee('Guardar como PDF')
        ->assertDontSee('Campo todavía vacío')
        ->assertDontSee('Respuesta privada de otra persona');
});

it('saves current template values before opening the export', function () {
    $user = User::factory()->create();
    $step = PlantillaPaso::create([
        'title' => '1 · Identidad comercial',
        'fields' => [['label' => 'Mi producto', 'ej' => 'Consultoría']],
    ]);

    $this->actingAs($user);

    Livewire::test('pages::objecion-cero.plantilla')
        ->set("valores.{$step->id}.0", 'Prospección automatizada')
        ->call('exportar')
        ->assertRedirect(route('objecion-cero.plantilla.exportar'));

    $this->assertDatabaseHas('oc_template_answers', [
        'user_id' => $user->id,
        'template_step_id' => $step->id,
        'field_index' => 0,
        'value' => 'Prospección automatizada',
    ]);
});
