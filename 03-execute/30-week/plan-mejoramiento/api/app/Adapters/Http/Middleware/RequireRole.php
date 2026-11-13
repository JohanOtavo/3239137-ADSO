<?php

declare(strict_types=1);

namespace App\Adapters\Http\Middleware;

use App\Adapters\Http\Response\ErrorResponder;
use App\Application\Shared\AuthenticatedUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * T-16 - Restricts a route to a list of roles (AC-01.3).
 *
 * The allowed roles are declared on the route, next to the path, so the
 * contract can be read off routes/api.php without opening a controller. A route
 * that forgets this middleware is a route anybody with a token can reach, which
 * is why the role is never checked inside a use case instead.
 */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get(AuthenticateToken::ATTRIBUTE);

        if (! $user instanceof AuthenticatedUser) {
            return ErrorResponder::unauthenticated('Falta el token de acceso.');
        }

        foreach ($roles as $role) {
            if ($user->role->value === $role) {
                return $next($request);
            }
        }

        return ErrorResponder::forbidden('Su rol no permite esta operación.');
    }
}
