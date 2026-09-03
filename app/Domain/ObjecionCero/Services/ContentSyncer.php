<?php

namespace App\Domain\ObjecionCero\Services;

use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Checklist;
use App\Domain\ObjecionCero\Models\Cierre;
use App\Domain\ObjecionCero\Models\Ficha;
use App\Domain\ObjecionCero\Models\Frase;
use App\Domain\ObjecionCero\Models\PlantillaPaso;
use App\Domain\ObjecionCero\Models\PreguntaGrupo;
use App\Domain\ObjecionCero\Models\UsoItem;
use App\Domain\ObjecionCero\Models\WhatsappScript;

/**
 * Sincroniza el contenido maestro de Objeción Cero contra el JSON fuente:
 * agrega, actualiza y elimina filas para que la BD refleje exactamente el JSON.
 *
 * Checklists y pasos de plantilla se dan de baja (soft delete) en vez de
 * borrarse: tienen progreso/respuestas de usuarios colgando por FK con
 * cascadeOnDelete, y un borrado duro se llevaría esos datos con ellos.
 */
class ContentSyncer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function sync(array $data): void
    {
        $categoriaIds = $this->syncCategorias($data['CATS']);
        $this->syncFichas($data['FICHAS'], $categoriaIds);
        $this->syncCierres($data['CIERRES']);
        $this->syncFrases($data['FRASES']);
        $this->syncWhatsapp($data['WHATSAPP']);
        $this->syncChecklists($data['CHECKLISTS']);
        $this->syncPlantilla($data['PLANTILLA']);
        $this->syncPreguntas($data['PREGUNTAS']);
        $this->syncUso($data['USO']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cats
     * @return array<string,int> slug => id
     */
    private function syncCategorias(array $cats): array
    {
        $ids = [];
        foreach ($cats as $c) {
            $ids[$c['id']] = Categoria::updateOrCreate(
                ['slug' => $c['id']],
                ['label' => $c['label'], 'icon' => $c['icon']],
            )->id;
        }

        Categoria::whereNotIn('slug', array_keys($ids))->delete();

        return $ids;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fichas
     * @param  array<string, int>  $categoriaIds
     */
    private function syncFichas(array $fichas, array $categoriaIds): void
    {
        $numeros = [];
        foreach ($fichas as $f) {
            $numeros[] = $f['n'];
            Ficha::updateOrCreate(
                ['number' => $f['n']],
                [
                    'category_id' => $categoriaIds[$f['cat']],
                    'type' => $f['tipo'],
                    'objection' => $f['obj'],
                    'search_aliases' => $f['aliases'] ?? [],
                    'confirm' => $f['confirmar'],
                    'meaning' => $f['significa'],
                    'worst_case' => $f['peor'] ?? [],
                    'dialogue' => $f['dialogo'] ?? [],
                    'question' => $f['pregunta'] ?? null,
                    'closing' => $f['cierre'] ?? null,
                    'error' => $f['error'] ?? null,
                    'tip' => $f['consejo'] ?? null,
                    'branches' => $f['ramas'] ?? [],
                ],
            );
        }

        Ficha::whereNotIn('number', $numeros)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $cierres
     */
    private function syncCierres(array $cierres): void
    {
        $nombres = [];
        foreach ($cierres as $i => $c) {
            $nombres[] = $c['nombre'];
            Cierre::updateOrCreate(
                ['name' => $c['nombre']],
                [
                    'objection' => $c['obj'],
                    'script' => $c['script'],
                    'usage' => $c['usar'],
                    'avoid' => $c['noUsar'],
                    'sort_order' => $i,
                ],
            );
        }

        Cierre::whereNotIn('name', $nombres)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $frases
     */
    private function syncFrases(array $frases): void
    {
        $titulos = [];
        foreach ($frases as $i => $f) {
            $titulos[] = $f['t'];
            Frase::updateOrCreate(
                ['title' => $f['t']],
                ['items' => $f['items'], 'sort_order' => $i],
            );
        }

        Frase::whereNotIn('title', $titulos)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $scripts
     */
    private function syncWhatsapp(array $scripts): void
    {
        $titulos = [];
        foreach ($scripts as $i => $w) {
            $titulos[] = $w['titulo'];
            WhatsappScript::updateOrCreate(
                ['title' => $w['titulo']],
                ['messages' => $w['msgs'], 'sort_order' => $i],
            );
        }

        WhatsappScript::whereNotIn('title', $titulos)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $checklists
     */
    private function syncChecklists(array $checklists): void
    {
        $slugs = [];
        foreach ($checklists as $i => $cl) {
            $slugs[] = $cl['id'];
            $checklist = Checklist::withTrashed()->firstOrNew(['slug' => $cl['id']]);
            $checklist->deleted_at = null;
            $checklist->fill([
                'title' => $cl['titulo'],
                'subtitle' => $cl['sub'] ?? null,
                'note' => $cl['nota'] ?? null,
                'blocks' => $cl['bloques'],
                'sort_order' => $i,
            ])->save();
        }

        Checklist::whereNotIn('slug', $slugs)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $pasos
     */
    private function syncPlantilla(array $pasos): void
    {
        $titulosPaso = [];
        foreach ($pasos as $i => $p) {
            $titulosPaso[] = $p['paso'];
            $paso = PlantillaPaso::withTrashed()->firstOrNew(['title' => $p['paso']]);
            $paso->deleted_at = null;
            $paso->fill(['fields' => $p['campos'], 'sort_order' => $i])->save();
        }

        PlantillaPaso::whereNotIn('title', $titulosPaso)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $grupos
     */
    private function syncPreguntas(array $grupos): void
    {
        $titulos = [];
        foreach ($grupos as $i => $g) {
            $titulos[] = $g['t'];
            PreguntaGrupo::updateOrCreate(
                ['title' => $g['t']],
                ['items' => $g['items'], 'sort_order' => $i],
            );
        }

        PreguntaGrupo::whereNotIn('title', $titulos)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncUso(array $items): void
    {
        $titulos = [];
        foreach ($items as $i => $u) {
            $titulos[] = $u['t'];
            UsoItem::updateOrCreate(
                ['title' => $u['t']],
                ['description' => $u['d'], 'sort_order' => $i],
            );
        }

        UsoItem::whereNotIn('title', $titulos)->delete();
    }
}
