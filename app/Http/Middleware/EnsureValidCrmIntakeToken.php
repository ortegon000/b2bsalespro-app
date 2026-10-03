<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege la entrada de leads con un token Bearer compartido (config crm.intake_token).
 */
class EnsureValidCrmIntakeToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('crm.intake_token');
        $given = $request->bearerToken();

        if (! is_string($expected) || $expected === '' || ! is_string($given) || ! hash_equals($expected, $given)) {
            abort(401, 'Token de entrada inválido.');
        }

        return $next($request);
    }
}
