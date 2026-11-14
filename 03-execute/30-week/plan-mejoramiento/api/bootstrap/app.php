<?php

use App\Adapters\Console\EnsureAdminCommand;
use App\Adapters\Http\Middleware\AuthenticateToken;
use App\Adapters\Http\Middleware\RequireRole;
use App\Adapters\Http\Response\ErrorResponder;
use App\Domain\Exception\BusinessRuleViolation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
    )
    ->withCommands([EnsureAdminCommand::class])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'token' => AuthenticateToken::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Every error leaves through the same body (api-contract 1). A broken
         * rule carries its own code; an unknown route is a 404 in the same
         * shape, so a client parses one thing and not two; anything else is a
         * defect and says nothing, because the message may carry a connection
         * string or a server path.
         */
        $exceptions->render(fn (BusinessRuleViolation $violation) => ErrorResponder::fromViolation($violation));

        $exceptions->render(fn (NotFoundHttpException $missing) => ErrorResponder::notFound('Ese recurso no existe.'));

        $exceptions->render(function (Throwable $failure, Request $request) {
            return $request->is('api/*') ? ErrorResponder::unexpected($failure) : null;
        });
    })
    ->create();
