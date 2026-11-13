<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Http;

use App\Adapters\Http\Response\ErrorResponder;
use App\Domain\Exception\DocumentTaken;
use App\Domain\Exception\EquipmentNotAvailable;
use App\Domain\Exception\ForbiddenRole;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\Exception\InvalidDateRange;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ProducerLimitExceeded;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** T-20 - The map from business code to status, which the contract fixes. */
final class ErrorResponderTest extends TestCase
{
    public function test_invalid_credentials_answer_401(): void
    {
        // Arrange
        $violation = new InvalidCredentials('Usuario o contraseña incorrectos.');

        // Act
        $response = ErrorResponder::fromViolation($violation);

        // Assert
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('INVALID_CREDENTIALS', $this->bodyOf($response)['code']);
    }

    public function test_a_wrong_role_answers_403(): void
    {
        $response = ErrorResponder::fromViolation(new ForbiddenRole('Su rol no permite esta operación.'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_a_missing_resource_answers_404(): void
    {
        $response = ErrorResponder::fromViolation(new ResourceNotFound('Ese equipo no existe.'));

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * A state conflict is a 409 and not a 422: the request is well formed and
     * the client can retry it later, which is the difference the client acts on.
     */
    public function test_state_conflicts_answer_409(): void
    {
        $conflicts = [
            new DocumentTaken('Ya hay un productor con ese documento.'),
            new EquipmentNotAvailable('No quedan unidades disponibles.'),
            new ItemAlreadyReturned('Esa línea ya fue devuelta.'),
        ];

        foreach ($conflicts as $conflict) {
            $this->assertSame(409, ErrorResponder::fromViolation($conflict)->getStatusCode(), $conflict->code());
        }
    }

    public function test_broken_input_rules_answer_422(): void
    {
        $invalid = [
            new ProducerLimitExceeded('Ese productor ya tiene dos equipos afuera.'),
            new InvalidDateRange('La fecha inicial no puede ser mayor que la final.'),
            new ValidationFailed('Falta el nombre.'),
        ];

        foreach ($invalid as $failure) {
            $this->assertSame(422, ErrorResponder::fromViolation($failure)->getStatusCode(), $failure->code());
        }
    }

    public function test_the_body_carries_the_details_of_the_violation(): void
    {
        $violation = new ValidationFailed('Falta el nombre.', [
            ['field' => 'name', 'issue' => 'falta o está vacío'],
        ]);

        $body = $this->bodyOf(ErrorResponder::fromViolation($violation));

        $this->assertSame('VALIDATION_ERROR', $body['code']);
        $this->assertSame('Falta el nombre.', $body['message']);
        $this->assertSame([['field' => 'name', 'issue' => 'falta o está vacío']], $body['details']);
    }

    /** A defect must not hand the client a connection string or a server path. */
    public function test_an_unexpected_failure_says_nothing_about_itself(): void
    {
        $response = ErrorResponder::unexpected(
            new RuntimeException('SQLSTATE[08006] could not connect to host 10.0.0.4 as filmgear')
        );

        $body = $this->bodyOf($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Ocurrió un error inesperado.', $body['message']);
        $this->assertStringNotContainsStringIgnoringCase('sqlstate', json_encode($body, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('10.0.0.4', json_encode($body, JSON_THROW_ON_ERROR));
    }

    public function test_every_error_body_has_the_same_three_fields(): void
    {
        $bodies = [
            $this->bodyOf(ErrorResponder::notFound('No existe.')),
            $this->bodyOf(ErrorResponder::forbidden('Sin permiso.')),
            $this->bodyOf(ErrorResponder::unauthenticated('Falta el token.')),
            $this->bodyOf(ErrorResponder::invalid('Campo inválido.')),
        ];

        foreach ($bodies as $body) {
            $this->assertSame(['code', 'message', 'details'], array_keys($body));
        }
    }

    /** @return array<string, mixed> */
    private function bodyOf(object $response): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
