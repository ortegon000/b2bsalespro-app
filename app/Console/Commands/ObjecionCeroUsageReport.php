<?php

namespace App\Console\Commands;

use App\Domain\ObjecionCero\Models\ContentView;
use App\Domain\ObjecionCero\Models\Feedback;
use App\Models\User;
use Illuminate\Console\Command;

class ObjecionCeroUsageReport extends Command
{
    protected $signature = 'objecion-cero:usage-report {--days=7 : Ventana de "usuario activo" en días}';

    protected $description = 'Resumen de uso de Objeción Cero: visitas por sección, usuarios activos y feedback recibido';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = now()->subDays($days);

        $totalUsers = User::count();
        $activeUsers = ContentView::where('created_at', '>=', $since)->distinct('user_id')->count('user_id');

        $this->info("Usuarios activos (últimos {$days} días): {$activeUsers} / {$totalUsers} invitados".
            ($totalUsers > 0 ? ' ('.round($activeUsers / $totalUsers * 100).'%)' : ''));

        $this->newLine();
        $this->line('Visitas por sección (todo el histórico):');
        $this->table(
            ['Sección', 'Visitas', 'Usuarios distintos'],
            ContentView::selectRaw('section, count(*) as visitas, count(distinct user_id) as usuarios')
                ->groupBy('section')
                ->orderByDesc('visitas')
                ->get()
                ->map(fn ($r) => [$r->section, $r->visitas, $r->usuarios]),
        );

        $this->newLine();
        $this->line('Fichas más consultadas:');
        $this->table(
            ['Ficha #', 'Aperturas'],
            ContentView::where('viewable_type', 'ficha')
                ->selectRaw('viewable_id, count(*) as aperturas')
                ->groupBy('viewable_id')
                ->orderByDesc('aperturas')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [$r->viewable_id, $r->aperturas]),
        );

        $this->newLine();
        $feedbackCount = Feedback::count();
        $this->info("Feedback recibido: {$feedbackCount} mensaje(s) — revisar con Feedback::latest()->get() en tinker.");

        return self::SUCCESS;
    }
}
