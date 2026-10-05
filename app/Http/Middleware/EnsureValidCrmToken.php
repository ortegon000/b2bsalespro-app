<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege un endpoint con un token Bearer compartido, leído de la clave de config indicada
 * (p. ej. `crm.intake_token`). Si el token no está configurado, rechaza todo.
 */
class EnsureValidCrmToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $configKey): Response
    {
        $expected = config($configKey);
        $given = $request->bearerToken();

        if (! is_string($expected) || $expected === '' || ! is_string($given) || ! hash_equals($expected, $given)) {
            abort(401, 'Token inválido.');
        }

        return $next($request);
    }
}
