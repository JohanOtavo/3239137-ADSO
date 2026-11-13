<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Table names and instant conversion, in one place.
 *
 * The schema is named explicitly instead of trusting the search_path: a
 * misconfigured connection then fails loudly rather than quietly writing into
 * another schema.
 */
final class Tables
{
    public const CATEGORY = 'filmgear.category';
    public const EQUIPMENT = 'filmgear.equipment';
    public const PRODUCER = 'filmgear.producer';
    public const RENTAL = 'filmgear.rental';
    public const RENTAL_ITEM = 'filmgear.rental_item';
    public const USER = 'filmgear.user';

    /** Stored as UTC instants with an explicit offset (BR-6). */
    public static function instant(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');
    }

    public static function toInstant(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return self::requiredInstant($value);
    }

    /** For columns the schema declares NOT NULL, so the caller keeps a plain instant. */
    public static function requiredInstant(string $value): DateTimeImmutable
    {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
    }
}
