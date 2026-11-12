<?php

declare(strict_types=1);

namespace App\Domain\Rental;

use App\Domain\Exception\ProducerHasOverdue;
use App\Domain\Exception\ProducerLimitExceeded;
use App\Domain\Shared\RentalPolicy;

/**
 * The two rules that look at the whole producer rather than one rental
 * (AC-06.4 and AC-06.5).
 *
 * They receive the count and the overdue flag already resolved, so they can be
 * tested with numbers and no database. Whoever calls them owns the query; this
 * class owns the rule.
 */
final readonly class ProducerBorrowingLimit
{
    public function __construct(private RentalPolicy $policy) {}

    public function ensureCanRent(int $openEquipment, int $requestedEquipment, bool $hasOverdue): void
    {
        // An overdue item outweighs the count, so it is reported first: telling
        // a producer they are at the limit when the real problem is an overdue
        // item sends them to return nothing.
        if ($hasOverdue) {
            throw new ProducerHasOverdue('El productor tiene equipos vencidos: debe devolverlos antes de alquilar otro.');
        }

        if ($openEquipment + $requestedEquipment > $this->policy->maxOpenEquipmentPerProducer) {
            throw new ProducerLimitExceeded(
                sprintf(
                    'El productor ya tiene %d equipo(s) sin devolver y el máximo es %d.',
                    $openEquipment,
                    $this->policy->maxOpenEquipmentPerProducer,
                ),
                [['field' => 'items', 'issue' => sprintf('sin devolver: %d', $openEquipment)]],
            );
        }
    }
}
