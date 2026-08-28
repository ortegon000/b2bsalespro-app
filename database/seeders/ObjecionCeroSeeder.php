<?php

namespace Database\Seeders;

use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Checklist;
use App\Domain\ObjecionCero\Models\Cierre;
use App\Domain\ObjecionCero\Models\Ficha;
use App\Domain\ObjecionCero\Models\Frase;
use App\Domain\ObjecionCero\Models\PlantillaPaso;
use App\Domain\ObjecionCero\Models\PreguntaGrupo;
use App\Domain\ObjecionCero\Models\UsoItem;
use App\Domain\ObjecionCero\Models\WhatsappScript;
use Illuminate\Database\Seeder;

/**
 * Carga el contenido maestro de Objeción Cero desde el JSON extraído
 * del bundle original (resources/legacy/objecion-cero-origen.html).
 *
 * Fuente: database/seeders/data/objecion-cero.json
 */
class ObjecionCeroSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/objecion-cero.json')),
            associative: true,
        );

        $categoriaIds = $this->seedCategorias($data['CATS']);
        $this->seedFichas($data['FICHAS'], $categoriaIds);
        $this->seedCierres($data['CIERRES']);
        $this->seedFrases($data['FRASES']);
        $this->seedWhatsapp($data['WHATSAPP']);
        $this->seedChecklists($data['CHECKLISTS']);
        $this->seedPlantilla($data['PLANTILLA']);
        $this->seedPreguntas($data['PREGUNTAS']);
        $this->seedUso($data['USO']);
    }

    /** @return array<string,int> slug => id */
    private function seedCategorias(array $cats): array
    {
        $ids = [];
        foreach ($cats as $c) {
            $ids[$c['id']] = Categoria::updateOrCreate(
                ['slug' => $c['id']],
                ['label' => $c['label'], 'icon' => $c['icon']],
            )->id;
        }

        return $ids;
    }

    private function seedFichas(array $fichas, array $categoriaIds): void
    {
        foreach ($fichas as $f) {
            Ficha::updateOrCreate(
                ['numero' => $f['n']],
                [
                    'categoria_id' => $categoriaIds[$f['cat']],
                    'tipo' => $f['tipo'],
                    'objecion' => $f['obj'],
                    'confirmar' => $f['confirmar'],
                    'significa' => $f['significa'],
                    'peor' => $f['peor'] ?? [],
                    'dialogo' => $f['dialogo'] ?? [],
                    'pregunta' => $f['pregunta'] ?? null,
                    'cierre' => $f['cierre'] ?? null,
                    'error' => $f['error'] ?? null,
                    'consejo' => $f['consejo'] ?? null,
                    'ramas' => $f['ramas'] ?? [],
                ],
            );
        }
    }

    private function seedCierres(array $cierres): void
    {
        foreach ($cierres as $i => $c) {
            Cierre::updateOrCreate(
                ['nombre' => $c['nombre']],
                [
                    'objecion' => $c['obj'],
                    'script' => $c['script'],
                    'usar' => $c['usar'],
                    'no_usar' => $c['noUsar'],
                    'orden' => $i,
                ],
            );
        }
    }

    private function seedFrases(array $frases): void
    {
        foreach ($frases as $i => $f) {
            Frase::updateOrCreate(
                ['titulo' => $f['t']],
                ['items' => $f['items'], 'orden' => $i],
            );
        }
    }

    private function seedWhatsapp(array $scripts): void
    {
        foreach ($scripts as $i => $w) {
            WhatsappScript::updateOrCreate(
                ['titulo' => $w['titulo']],
                ['mensajes' => $w['msgs'], 'orden' => $i],
            );
        }
    }

    private function seedChecklists(array $checklists): void
    {
        foreach ($checklists as $i => $cl) {
            Checklist::updateOrCreate(
                ['slug' => $cl['id']],
                [
                    'titulo' => $cl['titulo'],
                    'sub' => $cl['sub'] ?? null,
                    'nota' => $cl['nota'] ?? null,
                    'bloques' => $cl['bloques'],
                    'orden' => $i,
                ],
            );
        }
    }

    private function seedPlantilla(array $pasos): void
    {
        foreach ($pasos as $i => $p) {
            PlantillaPaso::updateOrCreate(
                ['paso' => $p['paso']],
                ['campos' => $p['campos'], 'orden' => $i],
            );
        }
    }

    private function seedPreguntas(array $grupos): void
    {
        foreach ($grupos as $i => $g) {
            PreguntaGrupo::updateOrCreate(
                ['titulo' => $g['t']],
                ['items' => $g['items'], 'orden' => $i],
            );
        }
    }

    private function seedUso(array $items): void
    {
        foreach ($items as $i => $u) {
            UsoItem::updateOrCreate(
                ['titulo' => $u['t']],
                ['descripcion' => $u['d'], 'orden' => $i],
            );
        }
    }
}
