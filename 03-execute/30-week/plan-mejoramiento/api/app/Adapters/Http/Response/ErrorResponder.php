<?php

declare(strict_types=1);

namespace App\Adapters\Http\Response;

use App\Domain\Exception\BusinessRuleViolation;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * The one place a broken rule becomes a status code.
 *
 * The domain names the contract code; the status lives here. Spreading this
 * table across the controllers is how the same rule ends up answering 409 on
 * one route and 422 on another.
 *
 * Anything that is not a BusinessRuleViolation is a defect, so it answers 500
 * with no detail at all: the exception message may carry a connection string
 * or a server path, and the client has no use for either.
 */
final class ErrorResponder
{
    /** The fourteen codes of the contract and nothing else. */
    private const STATUS_BY_CODE = [
        'INVALID_CREDENTIALS' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'MODEL_CODE_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'EQUIPMENT_HAS_OPEN_RENTALS' => 409,
        'EQUIPMENT_NOT_AVAILABLE' => 409,
        'PRODUCER_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'PRODUCER_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_EQUIPMENT_IN_RENTAL' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    public static function fromViolation(BusinessRuleViolation $violation): JsonResponse
    {
        $code = $violation->code();

        return self::body(
            $code,
            $violation->getMessage(),
            $violation->details(),
            self::STATUS_BY_CODE[$code] ?? 500,
        );
    }

    /** Used by the middleware, which rejects before any use case runs. */
    public static function unauthenticated(string $message): JsonResponse
    {
        return self::body('INVALID_CREDENTIALS', $message, [], 401);
    }

    public static function forbidden(string $message): JsonResponse
    {
        return self::body('FORBIDDEN_ROLE', $message, [], 403);
    }

    public static function notFound(string $message): JsonResponse
    {
        return self::body('NOT_FOUND', $message, [], 404);
    }

    /** @param list<array{field: string, issue: string}> $details */
    public static function invalid(string $message, array $details = []): JsonResponse
    {
        return self::body('VALIDATION_ERROR', $message, $details, 422);
    }

    /**
     * Deliberately says nothing: a defect must not leak the server's internals.
     *
     * INTERNAL_ERROR is outside the table of fourteen on purpose. That table
     * lists business errors, and a defect is not one; giving it a business code
     * would tell the client to handle something it cannot act on.
     */
    public static function unexpected(Throwable $failure): JsonResponse
    {
        unset($failure);

        return self::body('INTERNAL_ERROR', 'Ocurrió un error inesperado.', [], 500);
    }

    /** @param list<array{field: string, issue: string}> $details */
    private static function body(string $code, string $message, array $details, int $status): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $status);
    }
}
