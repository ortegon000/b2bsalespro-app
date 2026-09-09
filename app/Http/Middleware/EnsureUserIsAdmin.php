<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige a los usuarios normales (beta de Objeción Cero) fuera del
 * dashboard general, que hoy no tiene contenido relevante para ellos.
 */
class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            return redirect()->route('objecion-cero.inicio');
        }

        return $next($request);
    }
}
