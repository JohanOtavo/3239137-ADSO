<?php

declare(strict_types=1);

namespace App\Adapters\Http\Middleware;

use App\Adapters\Http\Response\ErrorResponder;
use App\Application\Port\TokenIssuer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * T-16 - Reads the bearer token and puts the authenticated user on the request.
 *
 * It verifies the signature and the expiry and nothing else. Whether a user may
 * reach a given route is the next middleware's question, so that a route can be
 * authenticated without being restricted.
 */
final readonly class AuthenticateToken
{
    public const ATTRIBUTE = 'authenticated_user';

    public function __construct(private TokenIssuer $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return ErrorResponder::unauthenticated('Falta el token de acceso.');
        }

        $user = $this->tokens->verify(substr($header, 7));

        if ($user === null) {
            return ErrorResponder::unauthenticated('El token no es válido o ya expiró.');
        }

        $request->attributes->set(self::ATTRIBUTE, $user);

        return $next($request);
    }
}
