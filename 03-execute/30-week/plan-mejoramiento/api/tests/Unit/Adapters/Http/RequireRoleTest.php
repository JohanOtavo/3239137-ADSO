<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Http;

use App\Adapters\Http\Middleware\AuthenticateToken;
use App\Adapters\Http\Middleware\RequireRole;
use App\Application\Shared\AuthenticatedUser;
use App\Domain\Shared\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/** T-20 - AC-01.3, the role gate, checked at the edge and not inside a use case. */
final class RequireRoleTest extends TestCase
{
    public function test_it_lets_through_a_user_whose_role_is_listed(): void
    {
        // Arrange
        $request = $this->requestAs(Role::Advisor);

        // Act
        $response = (new RequireRole())->handle($request, $this->reachedTheRoute(), 'admin', 'advisor');

        // Assert
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_it_answers_403_when_the_role_is_not_listed(): void
    {
        $response = (new RequireRole())->handle($this->requestAs(Role::Advisor), $this->reachedTheRoute(), 'admin');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('FORBIDDEN_ROLE', $this->codeOf($response));
    }

    /**
     * A route that forgets the token middleware must not fall open. 401 and not
     * 403 is deliberate: there is no identity to refuse yet.
     */
    public function test_a_request_with_no_authenticated_user_answers_401(): void
    {
        $response = (new RequireRole())->handle(Request::create('/api/v1/equipment'), $this->reachedTheRoute(), 'admin');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('INVALID_CREDENTIALS', $this->codeOf($response));
    }

    private function requestAs(Role $role): Request
    {
        $request = Request::create('/api/v1/equipment');
        $request->attributes->set(AuthenticateToken::ATTRIBUTE, new AuthenticatedUser('user-1', $role));

        return $request;
    }

    private function reachedTheRoute(): callable
    {
        return static fn (): Response => new JsonResponse(null, 204);
    }

    private function codeOf(Response $response): string
    {
        /** @var array{code: string} $body */
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $body['code'];
    }
}
