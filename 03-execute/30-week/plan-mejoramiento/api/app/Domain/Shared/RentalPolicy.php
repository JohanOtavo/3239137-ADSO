<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * The numbers from BR-2, held outside the rules that use them.
 *
 * Passing them in rather than writing them into the entity is what lets a test
 * narrow the limits to one item or one day and still exercise the real rule.
 */
final readonly class RentalPolicy
{
    public function __construct(
        public int $rentalDays = 4,
        public int $maxOpenEquipmentPerProducer = 2,
        public int $maxItemsPerRental = 2,
    ) {}
}
