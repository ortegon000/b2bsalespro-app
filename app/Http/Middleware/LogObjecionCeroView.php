<?php

namespace App\Http\Middleware;

use App\Domain\ObjecionCero\Models\ContentView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogObjecionCeroView
{
    public function handle(Request $request, Closure $next): Response
    {
        $section = str($request->route()?->getName())->after('objecion-cero.')->value();

        if (filled($section)) {
            ContentView::log($section);
        }

        return $next($request);
    }
}
