<?php

declare(strict_types=1);

namespace App\Adapters\Http\Request;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;

/**
 * Reads the request body and query string into the types a use case expects.
 *
 * It checks shape, not business rules: that a field is present, that it is a
 * string, that a date parses. Whether the date range makes sense or the units
 * are enough is the domain's question, and duplicating it here is how the two
 * answers drift apart.
 */
final class Input
{
    public static function requiredString(Request $request, string $field): string
    {
        $value = $request->input($field);

        if (! is_string($value) || trim($value) === '') {
            throw new ValidationFailed(sprintf('El campo %s es obligatorio.', $field), [
                ['field' => $field, 'issue' => 'falta o está vacío'],
            ]);
        }

        return $value;
    }

    public static function requiredInt(Request $request, string $field): int
    {
        $value = $request->input($field);

        if (! is_int($value) && ! (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
            throw new ValidationFailed(sprintf('El campo %s debe ser un número entero.', $field), [
                ['field' => $field, 'issue' => 'no es un entero'],
            ]);
        }

        return (int) $value;
    }

    /** @return list<string> */
    public static function requiredStringList(Request $request, string $field): array
    {
        $value = $request->input($field);

        if (! is_array($value) || $value === []) {
            throw new ValidationFailed(sprintf('El campo %s debe traer al menos un elemento.', $field), [
                ['field' => $field, 'issue' => 'lista vacía o ausente'],
            ]);
        }

        $items = [];

        foreach (array_values($value) as $index => $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new ValidationFailed(sprintf('El campo %s trae un elemento vacío.', $field), [
                    ['field' => sprintf('%s[%d]', $field, $index), 'issue' => 'no es un identificador'],
                ]);
            }

            $items[] = $item;
        }

        return $items;
    }

    public static function optionalString(Request $request, string $field): ?string
    {
        $value = $request->query($field);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    public static function optionalInt(Request $request, string $field): ?int
    {
        $value = $request->query($field);

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || preg_match('/^\d+$/', $value) !== 1) {
            throw new ValidationFailed(sprintf('El parámetro %s debe ser un número.', $field), [
                ['field' => $field, 'issue' => 'no es un número'],
            ]);
        }

        return (int) $value;
    }

    public static function optionalInstant(Request $request, string $field): ?DateTimeImmutable
    {
        $raw = self::optionalString($request, $field);

        return $raw === null ? null : self::parseInstant($raw, $field);
    }

    public static function requiredInstant(Request $request, string $field): DateTimeImmutable
    {
        $raw = self::optionalString($request, $field) ?? self::requiredString($request, $field);

        return self::parseInstant($raw, $field);
    }

    public static function optionalStatus(Request $request, string $field = 'status'): ?RentalStatus
    {
        $raw = self::optionalString($request, $field);

        if ($raw === null) {
            return null;
        }

        $status = RentalStatus::tryFrom($raw);

        if ($status === null) {
            throw new ValidationFailed('El estado debe ser open, overdue o closed.', [
                ['field' => $field, 'issue' => 'estado desconocido'],
            ]);
        }

        return $status;
    }

    private static function parseInstant(string $raw, string $field): DateTimeImmutable
    {
        try {
            return (new DateTimeImmutable($raw))->setTimezone(new DateTimeZone('UTC'));
        } catch (Exception) {
            throw new ValidationFailed(sprintf('El campo %s no es una fecha válida.', $field), [
                ['field' => $field, 'issue' => 'fecha ilegible'],
            ]);
        }
    }
}
