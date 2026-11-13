<?php

declare(strict_types=1);

namespace App\Application\UseCase\Rental;

use App\Application\Port\Clock;
use App\Application\Port\EquipmentRepository;
use App\Application\Port\RentalRepository;
use App\Application\Port\TransactionManager;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Rental\Rental;

/** US-07 - Return one line: sets returned_at and gives one unit back (AC-07.1). */
final readonly class ReturnRentalItemService
{
    public function __construct(
        private RentalRepository $rentals,
        private EquipmentRepository $equipment,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {}

    public function handle(string $rentalId, string $itemId): Rental
    {
        return $this->transactions->transactional(
            fn (): Rental => $this->returnOne($rentalId, $itemId),
        );
    }

    private function returnOne(string $rentalId, string $itemId): Rental
    {
        $rental = $this->rentals->find($rentalId);

        if ($rental === null) {
            throw new ResourceNotFound('Ese alquiler no existe.');
        }

        $item = $rental->item($itemId);
        $now = $this->clock->now();

        // The conditional write is what decides. Checking the loaded state
        // alone would let two simultaneous returns both pass and give the unit
        // back twice (AC-07.2).
        if ($item->isReturned() || ! $this->rentals->markItemReturned($rentalId, $itemId, $now)) {
            throw new ItemAlreadyReturned('Esta línea del alquiler ya estaba devuelta.');
        }

        $this->equipment->applyRestock($item->equipmentId);

        return $rental->returnItem($itemId, $now);
    }
}
