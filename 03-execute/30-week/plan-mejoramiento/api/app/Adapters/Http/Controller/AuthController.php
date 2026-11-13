<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Request\Input;
use App\Adapters\Http\Response\Presenter;
use App\Application\UseCase\Auth\CreateAdvisorService;
use App\Application\UseCase\Auth\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US-01 and US-02 - Login, and the admin creating an advisor. */
final readonly class AuthController
{
    public function __construct(
        private LoginService $login,
        private CreateAdvisorService $createAdvisor,
    ) {}

    public function login(Request $request): JsonResponse
    {
        // Read as a required string so a missing password answers 422 and not a
        // type error, but never echoed back: the error body must not carry it.
        $result = $this->login->handle(
            Input::requiredString($request, 'username'),
            Input::requiredString($request, 'password'),
        );

        return new JsonResponse([
            'access_token' => $result->token->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $result->token->expiresInSeconds,
            'role' => $result->role->value,
        ]);
    }

    public function createAdvisor(Request $request): JsonResponse
    {
        $user = $this->createAdvisor->handle(
            Input::requiredString($request, 'username'),
            Input::requiredString($request, 'password'),
            Input::requiredString($request, 'role'),
        );

        return new JsonResponse(Presenter::user($user), 201);
    }
}
