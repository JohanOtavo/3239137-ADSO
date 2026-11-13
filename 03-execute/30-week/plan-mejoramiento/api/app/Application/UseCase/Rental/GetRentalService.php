<?php

declare(strict_types=1);

namespace App\Application\UseCase\Rental;

use App\Application\Port\RentalRepository;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Rental\Rental;

/** US-08 - A rental with its lines; the state is computed by whoever renders it (AC-08.1). */
final readonly class GetRentalService
{
    public function __construct(private RentalRepository $rentals) {}

    public function handle(string $rentalId): Rental
    {
        $rental = $this->rentals->find($rentalId);

        if ($rental === null) {
            throw new ResourceNotFound('Ese alquiler no existe.');
        }

        return $rental;
    }
}
