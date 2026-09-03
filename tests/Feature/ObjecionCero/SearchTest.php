<?php

use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Cierre;
use App\Domain\ObjecionCero\Models\Ficha;
use App\Domain\ObjecionCero\Models\Frase;
use App\Domain\ObjecionCero\Models\WhatsappScript;
use Livewire\Livewire;

it('finds banco records by natural language aliases with extra punctuation', function () {
    $categoria = Categoria::create([
        'slug' => 'precio',
        'label' => 'Precio',
    ]);

    Ficha::create([
        'number' => 1,
        'category_id' => $categoria->id,
        'type' => 'real',
        'objection' => 'Tengo que consultarlo con mi socio',
        'search_aliases' => ['Lo tengo que ver con mi jefe'],
        'confirm' => 'Confirmar',
        'meaning' => 'Necesita involucrar a otra persona',
    ]);

    Ficha::create([
        'number' => 2,
        'category_id' => $categoria->id,
        'type' => 'duda',
        'objection' => 'Necesito consultarlo',
        'confirm' => 'Confirmar',
        'meaning' => 'Hay otra persona involucrada',
    ]);

    Livewire::test('pages::objecion-cero.banco-fichas')
        ->set('query', 'lo tengo que ver, con mi jefe.')
        ->assertSee('Tengo que consultarlo con mi socio')
        ->assertDontSee('Necesito consultarlo');
});

it('finds cierres without accents and with extra punctuation', function () {
    Cierre::create([
        'objection' => 'Falta una decisión',
        'name' => 'Cierre de avance',
        'script' => 'Definamos el próximo paso',
        'usage' => 'Cuando existe interés',
        'avoid' => 'Cuando no existe interés',
    ]);

    Cierre::create([
        'objection' => 'Sin presupuesto',
        'name' => 'Cierre financiero',
        'script' => 'Revisemos las alternativas',
        'usage' => 'Cuando falta presupuesto',
        'avoid' => 'Cuando el precio no importa',
    ]);

    Livewire::test('pages::objecion-cero.cierres')
        ->set('query', 'proximo, paso.')
        ->assertSee('Cierre de avance')
        ->assertDontSee('Cierre financiero');
});

it('finds frases without accents and with extra punctuation', function () {
    Frase::create([
        'title' => 'Conectar',
        'items' => ['Gracias por tu aclaración'],
    ]);

    Frase::create([
        'title' => 'Cerrar',
        'items' => ['Agendemos la siguiente llamada'],
    ]);

    Livewire::test('pages::objecion-cero.frases')
        ->set('query', 'tu aclaracion,.')
        ->assertSee('Gracias por tu aclaración')
        ->assertDontSee('Agendemos la siguiente llamada');
});

it('finds whatsapp scripts without accents and with extra punctuation', function () {
    WhatsappScript::create([
        'title' => 'Seguimiento',
        'messages' => [['who' => 't', 't' => 'Confirmemos la reunión de mañana']],
    ]);

    WhatsappScript::create([
        'title' => 'Primer contacto',
        'messages' => [['who' => 't', 't' => 'Hola, quiero presentarme']],
    ]);

    Livewire::test('pages::objecion-cero.whatsapp')
        ->set('query', 'reunion, de manana.')
        ->assertSee('Seguimiento')
        ->assertDontSee('Primer contacto');
});
