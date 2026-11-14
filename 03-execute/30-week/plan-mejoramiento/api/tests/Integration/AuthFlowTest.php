<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * T-12 - The door: tokens, roles and the error body of a refusal.
 *
 * The unit tests already cover the middleware in isolation. What they cannot
 * show is that the routes actually carry it, and a route that forgets the
 * middleware is exactly the mistake that opens an endpoint to anybody.
 */
final class AuthFlowTest extends IntegrationTestCase
{
    public function test_an_administrator_logs_in_and_receives_a_bearer_token(): void
    {
        $token = $this->signInAsAdmin();

        $this->assertNotSame('', $token);
    }

    public function test_the_login_answers_the_same_error_for_a_wrong_password(): void
    {
        $this->signInAsAdmin();

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'root',
            'password' => 'not-the-password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_an_unknown_username_is_indistinguishable_from_a_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'nobody',
            'password' => 'not-the-password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_a_protected_route_refuses_a_request_without_a_token(): void
    {
        $response = $this->getJson('/api/v1/equipment');

        $response->assertStatus(401);
    }

    public function test_a_protected_route_refuses_a_forged_token(): void
    {
        $response = $this->getJson('/api/v1/equipment', [
            'Authorization' => 'Bearer not.a.token',
        ]);

        $response->assertStatus(401);
    }

    public function test_an_advisor_cannot_reach_an_administrator_route(): void
    {
        $admin = $this->signInAsAdmin();

        $this->postJson('/api/v1/users', [
            'username' => 'advisor-one',
            'password' => 'advisor-password',
            'role' => 'advisor',
        ], $this->bearer($admin))->assertStatus(201);

        $advisor = $this->tokenOf('advisor-one', 'advisor-password');

        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Cámara A',
            'brand' => 'Marca',
            'model_code' => 'CAM-001',
            'category_id' => self::CAMERAS,
            'total_units' => 2,
        ], $this->bearer($advisor));

        $response->assertStatus(403);
        $response->assertJsonPath('code', 'FORBIDDEN_ROLE');
    }

    public function test_an_unknown_route_answers_the_same_error_body(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonPath('code', 'NOT_FOUND');
    }
}
