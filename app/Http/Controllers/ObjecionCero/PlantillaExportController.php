<?php

namespace App\Http\Controllers\ObjecionCero;

use App\Domain\ObjecionCero\Models\PlantillaPaso;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlantillaExportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sections = PlantillaPaso::query()
            ->with(['respuestas' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->orderBy('sort_order')
            ->get()
            ->map(function (PlantillaPaso $step): array {
                $answers = $step->respuestas->keyBy('field_index');

                $fields = collect($step->fields)
                    ->map(function (array $field, int $index) use ($answers): ?array {
                        $value = trim((string) $answers->get($index)?->value);

                        if ($value === '') {
                            return null;
                        }

                        return [
                            'label' => $field['label'],
                            'value' => $value,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'title' => $step->title,
                    'fields' => $fields,
                ];
            })
            ->filter(fn (array $section) => $section['fields']->isNotEmpty())
            ->values();

        return view('pages.objecion-cero.plantilla-exportar', [
            'sections' => $sections,
            'user' => $request->user(),
        ]);
    }
}
